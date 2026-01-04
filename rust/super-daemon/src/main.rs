use tokio::net::UnixListener;
use tokio::io::{AsyncBufReadExt, AsyncWriteExt, BufReader};
use serde_json::{json, Value};
use std::fs;
use std::os::unix::fs::PermissionsExt;
use std::path::Path;
use sysinfo::{System, Disks};
use std::sync::Arc;
use tokio::sync::Mutex;

struct DaemonState {
    firewall_active: bool,
}

async fn user_exists(username: &str) -> Result<bool, Box<dyn std::error::Error>> {
    let output = std::process::Command::new("id")
        .arg(username)
        .output()?;
    
    Ok(output.status.success())
}

async fn create_vhost(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let domain = params["domain"].as_str().ok_or("Missing domain")?;
    let user = params["user"].as_str().ok_or("Missing user")?;
    let root = params["root"].as_str().ok_or("Missing root")?;
    let php_version = params["php_version"].as_str().ok_or("Missing php_version")?;

    // Validate that the user exists in /etc/passwd
    if !user_exists(user).await? {
        return Err(format!("User '{}' does not exist in the system", user).into());
    }

    // 1. Create directories
    let root_path = Path::new(root);
    if !root_path.exists() {
        std::process::Command::new("sudo").arg("-n").arg("mkdir").arg("-p").arg(root).status()?;
        // In a real system, we would chown to the user here
    }

    // 2. Load stubs
    let nginx_stub = fs::read_to_string("/home/super/getsupercp/resources/templates/system/nginx_vhost.conf.stub")?;
    let php_stub = fs::read_to_string("/home/super/getsupercp/resources/templates/system/php_fpm_pool.conf.stub")?;

    // 3. Replace placeholders
    let has_ssl = params["has_ssl"].as_bool().unwrap_or(false);
    let ssl_cert = params["ssl_certificate_path"].as_str().unwrap_or("");
    let ssl_key = params["ssl_key_path"].as_str().unwrap_or("");

    let ssl_redirect = if has_ssl {
        "return 301 https://$host$request_uri;"
    } else {
        ""
    };

    let ssl_config = if has_ssl {
        format!(
            "listen 443 ssl;\n    ssl_certificate {};\n    ssl_certificate_key {};",
            ssl_cert, ssl_key
        )
    } else {
        "".to_string()
    };

    let nginx_conf = nginx_stub
        .replace("{{DOMAIN}}", domain)
        .replace("{{ALIASES}}", "")
        .replace("{{ROOT}}", root)
        .replace("{{PHP_VERSION}}", php_version)
        .replace("{{USER}}", user)
        .replace("{{SSL_REDIRECT}}", ssl_redirect)
        .replace("{{SSL_CONFIG}}", &ssl_config);

    let php_conf = php_stub
        .replace("{{USER}}", user)
        .replace("{{PHP_VERSION}}", php_version);

    // 4. Write configs
    let nginx_available = format!("/etc/nginx/sites-available/{}", domain);
    let nginx_enabled = format!("/etc/nginx/sites-enabled/{}", domain);
    let php_pool_dir = format!("/etc/php/{}/fpm/pool.d", php_version);
    let php_pool = format!("{}/{}.conf", php_pool_dir, user);

    if !Path::new(&php_pool_dir).exists() {
        return Err(format!("PHP-FPM pool directory {} does not exist. Is PHP {} installed?", php_pool_dir, php_version).into());
    }

    let temp_nginx = format!("/tmp/nginx_{}.conf", domain);
    let temp_php = format!("/tmp/php_{}.conf", user.replace(" ", "_"));

    fs::write(&temp_nginx, nginx_conf)?;
    fs::write(&temp_php, php_conf)?;

    let status = std::process::Command::new("sudo").arg("-n").arg("mv").arg(&temp_nginx).arg(&nginx_available).status()?;
    if !status.success() {
        return Err(format!("Failed to move Nginx config to {}. Ensure daemon has sudo access.", nginx_available).into());
    }

    let status = std::process::Command::new("sudo").arg("-n").arg("ln").arg("-sf").arg(&nginx_available).arg(&nginx_enabled).status()?;
    if !status.success() {
        return Err(format!("Failed to enable Nginx config at {}. Ensure daemon has sudo access.", nginx_enabled).into());
    }

    let status = std::process::Command::new("sudo").arg("-n").arg("mv").arg(&temp_php).arg(&php_pool).status()?;
    if !status.success() {
        return Err(format!("Failed to move PHP pool config to {}. Ensure daemon has sudo access.", php_pool).into());
    }

    reload_services().await?;

    Ok(format!("VHost created for {}. Configs: {}, {}", domain, nginx_available, php_pool))
}

async fn delete_vhost(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let domain = params["domain"].as_str().ok_or("Missing domain")?;
    let user = params["user"].as_str().ok_or("Missing user")?;
    let php_version = params["php_version"].as_str().unwrap_or("8.4");

    let nginx_available = format!("/etc/nginx/sites-available/{}", domain);
    let nginx_enabled = format!("/etc/nginx/sites-enabled/{}", domain);
    let php_pool = format!("/etc/php/{}/fpm/pool.d/{}.conf", php_version, user);

    std::process::Command::new("sudo").arg("-n").arg("rm").arg("-f").arg(&nginx_enabled).status()?;
    std::process::Command::new("sudo").arg("-n").arg("rm").arg("-f").arg(&nginx_available).status()?;
    std::process::Command::new("sudo").arg("-n").arg("rm").arg("-f").arg(&php_pool).status()?;

    reload_services().await?;

    Ok(format!("VHost deleted for {}", domain))
}

async fn list_vhosts() -> Result<Value, Box<dyn std::error::Error>> {
    let nginx_dir = "/etc/nginx/sites-available";
    let mut domains = Vec::new();

    if let Ok(entries) = fs::read_dir(nginx_dir) {
        for entry in entries {
            if let Ok(entry) = entry {
                let path = entry.path();
                if let Some(name) = path.file_name().and_then(|s| s.to_str()) {
                    if name != "default" {
                        domains.push(name.to_string());
                    }
                }
            }
        }
    }

    Ok(json!(domains))
}

async fn get_status() -> Result<Value, Box<dyn std::error::Error>> {
    let services = vec!["nginx", "php8.4-fpm", "mysql", "redis-server"];
    let mut status = serde_json::Map::new();

    for service in services {
        let output = std::process::Command::new("systemctl")
            .arg("is-active")
            .arg(service)
            .output();

        let is_active = match output {
            Ok(out) => String::from_utf8_lossy(&out.stdout).trim() == "active",
            Err(_) => false,
        };

        status.insert(service.to_string(), json!(if is_active { "running" } else { "stopped" }));
    }

    status.insert("daemon".to_string(), json!("running"));
    
    Ok(Value::Object(status))
}

async fn restart_service(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let service = params["service"].as_str().ok_or("Missing service")?;
    
    // Security: only allow specific services
    let allowed = vec!["nginx", "php8.4-fpm", "mysql", "redis-server"];
    if !allowed.contains(&service) {
        return Err("Service not allowed".into());
    }

    let status = std::process::Command::new("systemctl")
        .arg("restart")
        .arg(service)
        .status()?;

    if status.success() {
        Ok(format!("Service {} restarted successfully", service))
    } else {
        Err(format!("Failed to restart service {}", service).into())
    }
}

async fn create_backup(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let name = params["name"].as_str().ok_or("Missing name")?;
    let source_path = params["source_path"].as_str().ok_or("Missing source_path")?;
    let backup_dir = "/var/lib/supercp/backups";
    fs::create_dir_all(backup_dir)?;

    let target_path = format!("{}/{}.tar.gz", backup_dir, name);

    // In a real system, we would use tar crate or Command::new("tar")
    let status = std::process::Command::new("tar")
        .arg("-czf")
        .arg(&target_path)
        .arg("-C")
        .arg(Path::new(source_path).parent().unwrap_or(Path::new("/")))
        .arg(Path::new(source_path).file_name().unwrap_or_default())
        .status()?;

    if status.success() {
        // Ensure the web server can read the backup for download
        let _ = fs::set_permissions(&target_path, fs::Permissions::from_mode(0o644));
        Ok(target_path)
    } else {
        Err("Failed to create backup archive".into())
    }
}

async fn create_db_backup(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let db_name = params["db_name"].as_str().ok_or("Missing db_name")?;
    let backup_dir = "/var/lib/supercp/backups";
    fs::create_dir_all(backup_dir)?;

    let timestamp = std::time::SystemTime::now()
        .duration_since(std::time::UNIX_EPOCH)?
        .as_secs();
    let target_path = format!("{}/{}_{}.sql", backup_dir, db_name, timestamp);

    // Use mysqldump. Since we are root, we can dump any database.
    let output = std::process::Command::new("mysqldump")
        .arg(db_name)
        .output()?;

    if output.status.success() {
        fs::write(&target_path, output.stdout)?;
        // Ensure the web server can read the backup for download
        let _ = fs::set_permissions(&target_path, fs::Permissions::from_mode(0o644));
        Ok(target_path)
    } else {
        let error = String::from_utf8_lossy(&output.stderr);
        Err(format!("Failed to create database backup: {}", error).into())
    }
}

async fn restore_backup(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let path = params["path"].as_str().ok_or("Missing path")?;
    let target_path = params["target_path"].as_str().ok_or("Missing target_path")?;

    if !Path::new(path).exists() {
        return Err("Backup file not found".into());
    }

    let status = std::process::Command::new("tar")
        .arg("-xzf")
        .arg(path)
        .arg("-C")
        .arg(target_path)
        .status()?;

    if status.success() {
        Ok(format!("Backup restored to {}", target_path))
    } else {
        Err("Failed to restore backup archive".into())
    }
}

async fn restore_db_backup(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let path = params["path"].as_str().ok_or("Missing path")?;
    let db_name = params["db_name"].as_str().ok_or("Missing db_name")?;

    if !Path::new(path).exists() {
        return Err("Backup file not found".into());
    }

    // Restore using mysql command
    let file = std::fs::File::open(path)?;
    let status = std::process::Command::new("mysql")
        .arg(db_name)
        .stdin(std::process::Stdio::from(file))
        .status()?;
    
    if status.success() {
        Ok(format!("Database {} restored from {}", db_name, path))
    } else {
        Err(format!("Failed to restore database {}", db_name).into())
    }
}

async fn reload_services() -> Result<String, Box<dyn std::error::Error>> {
    let nginx_status = std::process::Command::new("sudo")
        .arg("-n")
        .arg("systemctl")
        .arg("reload")
        .arg("nginx")
        .status()?;
        
    let php_status = std::process::Command::new("sudo")
        .arg("-n")
        .arg("systemctl")
        .arg("reload")
        .arg("php8.4-fpm")
        .status()?;
    
    if nginx_status.success() && php_status.success() {
        Ok("Services reloaded successfully".to_string())
    } else {
        Err("Failed to reload one or more services".into())
    }
}

async fn create_database(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let name = params["name"].as_str().ok_or("Missing name")?;
    let user = params["user"].as_str().ok_or("Missing user")?;
    let password = params["password"].as_str().ok_or("Missing password")?;
    let db_type = params["type"].as_str().unwrap_or("mysql");

    if db_type != "mysql" {
        return Err("Only MySQL is supported for now".into());
    }

    // 1. Create Database
    let status = std::process::Command::new("mysql")
        .arg("-e")
        .arg(format!("CREATE DATABASE IF NOT EXISTS `{}`", name))
        .status()?;

    if !status.success() {
        return Err(format!("Failed to create database {}", name).into());
    }

    // 2. Create User and Grant Privileges
    // We use 'localhost' for now. In a real system, this might be configurable.
    let sql = format!(
        "CREATE USER IF NOT EXISTS '{}'@'localhost' IDENTIFIED BY '{}'; \
         GRANT ALL PRIVILEGES ON `{}`.* TO '{}'@'localhost'; \
         FLUSH PRIVILEGES;",
        user, password, name, user
    );

    let status = std::process::Command::new("mysql")
        .arg("-e")
        .arg(sql)
        .status()?;

    if !status.success() {
        return Err(format!("Failed to create user or grant privileges for {}", user).into());
    }

    // 3. Save metadata
    let db_dir = "/var/lib/supercp/databases";
    fs::create_dir_all(db_dir)?;

    let meta_path = format!("{}/{}.json", db_dir, name);
    let meta = json!({
        "name": name,
        "user": user,
        "type": db_type,
    });

    fs::write(&meta_path, serde_json::to_string_pretty(&meta)?)?;

    Ok(format!("Database {} created and user {} granted access", name, user))
}

async fn delete_database(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let name = params["name"].as_str().ok_or("Missing name")?;
    
    // 1. Drop Database
    let status = std::process::Command::new("mysql")
        .arg("-e")
        .arg(format!("DROP DATABASE IF EXISTS `{}`", name))
        .status()?;

    if !status.success() {
        return Err(format!("Failed to drop database {}", name).into());
    }

    // Note: We don't automatically drop the user because multiple databases might use the same user.
    // In a more advanced system, we would track user-database relationships.

    // 2. Remove metadata
    let meta_path = format!("/var/lib/supercp/databases/{}.json", name);
    if Path::new(&meta_path).exists() {
        fs::remove_file(&meta_path)?;
    }

    Ok(format!("Database {} deleted", name))
}

async fn list_databases() -> Result<Value, Box<dyn std::error::Error>> {
    let db_dir = "/var/lib/supercp/databases";
    let mut dbs = Vec::new();

    if let Ok(entries) = fs::read_dir(db_dir) {
        for entry in entries {
            if let Ok(entry) = entry {
                let path = entry.path();
                if path.extension().and_then(|s| s.to_str()) == Some("json") {
                    if let Some(name) = path.file_stem().and_then(|s| s.to_str()) {
                        dbs.push(name.to_string());
                    }
                }
            }
        }
    }

    Ok(json!(dbs))
}

async fn create_ftp_user(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let username = params["username"].as_str().ok_or("Missing username")?;
    let _password = params["password"].as_str().ok_or("Missing password")?;
    let homedir = params["homedir"].as_str().ok_or("Missing homedir")?;

    let ftp_dir = "/etc/supercp/ftp_users";
    fs::create_dir_all(ftp_dir)?;

    let meta_path = format!("{}/{}.json", ftp_dir, username);
    let meta = json!({
        "username": username,
        "homedir": homedir,
    });

    fs::write(&meta_path, serde_json::to_string_pretty(&meta)?)?;

    // In a real system, we would add the user to /etc/passwd or a virtual user DB
    // and ensure the homedir exists with correct permissions.
    let homedir_path = Path::new(homedir);
    if !homedir_path.exists() {
        fs::create_dir_all(homedir_path)?;
    }

    Ok(format!("FTP user {} created with homedir {}", username, homedir))
}

async fn delete_ftp_user(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let username = params["username"].as_str().ok_or("Missing username")?;
    let meta_path = format!("/etc/supercp/ftp_users/{}.json", username);

    if Path::new(&meta_path).exists() {
        fs::remove_file(&meta_path)?;
    }

    Ok(format!("FTP user {} deleted", username))
}

async fn list_ftp_users() -> Result<Value, Box<dyn std::error::Error>> {
    let ftp_dir = "/etc/supercp/ftp_users";
    let mut users = Vec::new();

    if let Ok(entries) = fs::read_dir(ftp_dir) {
        for entry in entries {
            if let Ok(entry) = entry {
                let path = entry.path();
                if path.extension().and_then(|s| s.to_str()) == Some("json") {
                    if let Some(name) = path.file_stem().and_then(|s| s.to_str()) {
                        users.push(name.to_string());
                    }
                }
            }
        }
    }

    Ok(json!(users))
}

async fn get_database_size(params: &Value) -> Result<u64, Box<dyn std::error::Error>> {
    let name = params["name"].as_str().ok_or("Missing name")?;
    
    let sql = format!(
        "SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = '{}'",
        name
    );

    let output = std::process::Command::new("mysql")
        .arg("-N")
        .arg("-s")
        .arg("-e")
        .arg(sql)
        .output()?;

    if output.status.success() {
        let size_str = String::from_utf8_lossy(&output.stdout).trim().to_string();
        if size_str == "NULL" || size_str.is_empty() {
            Ok(0)
        } else {
            Ok(size_str.parse::<u64>().unwrap_or(0))
        }
    } else {
        let error = String::from_utf8_lossy(&output.stderr);
        Err(format!("Failed to get database size: {}", error).into())
    }
}

async fn get_directory_size(params: &Value) -> Result<u64, Box<dyn std::error::Error>> {
    let path_str = params["path"].as_str().ok_or("Missing path")?;
    let target_path = resolve_safe_path(path_str)?;

    if !target_path.exists() {
        return Err("Path does not exist".into());
    }

    let output = std::process::Command::new("du")
        .arg("-sb")
        .arg(&target_path)
        .output()?;

    if output.status.success() {
        let stdout = String::from_utf8_lossy(&output.stdout);
        let size = stdout.split_whitespace().next().unwrap_or("0").parse::<u64>().unwrap_or(0);
        Ok(size)
    } else {
        let error = String::from_utf8_lossy(&output.stderr);
        Err(format!("Failed to get directory size: {}", error).into())
    }
}

async fn update_cron_jobs(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let user = params["user"].as_str().ok_or("Missing user")?;
    let jobs = params["jobs"].as_array().ok_or("Missing jobs")?;

    let cron_dir = "/var/spool/supercp/cron";
    fs::create_dir_all(cron_dir)?;

    let cron_path = format!("{}/{}.cron", cron_dir, user);
    let mut content = String::new();

    for job in jobs {
        let command = job["command"].as_str().unwrap_or("");
        let schedule = job["schedule"].as_str().unwrap_or("");
        content.push_str(&format!("{} {}\n", schedule, command));
    }

    fs::write(&cron_path, content)?;

    // Apply the crontab for the user
    let status = std::process::Command::new("crontab")
        .arg("-u")
        .arg(user)
        .arg(&cron_path)
        .status()?;

    if status.success() {
        Ok(format!("Cron jobs updated and applied for {}", user))
    } else {
        Err(format!("Failed to apply crontab for {}", user).into())
    }
}

async fn list_cron_jobs(params: &Value) -> Result<Value, Box<dyn std::error::Error>> {
    let user = params["user"].as_str().ok_or("Missing user")?;
    let cron_path = format!("/var/spool/supercp/cron/{}.cron", user);

    if !Path::new(&cron_path).exists() {
        return Ok(json!([]));
    }

    let content = fs::read_to_string(&cron_path)?;
    let mut jobs = Vec::new();

    for line in content.lines() {
        if !line.trim().is_empty() {
            jobs.push(line.to_string());
        }
    }

    Ok(json!(jobs))
}

async fn update_dns_zone(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let domain = params["domain"].as_str().ok_or("Missing domain")?;
    let records = params["records"].as_array().ok_or("Missing records")?;

    let dns_dir = "/etc/supercp/dns";
    fs::create_dir_all(dns_dir)?;

    let zone_path = format!("{}/{}.zone", dns_dir, domain);
    let mut content = format!("$ORIGIN {}.\n", domain);
    content.push_str("$TTL 3600\n");
    content.push_str("@ IN SOA ns1.supercp.com. admin.supercp.com. ( 2026010301 3600 600 1209600 3600 )\n");

    for record in records {
        let name = record["name"].as_str().unwrap_or("@");
        let rtype = record["type"].as_str().unwrap_or("A");
        let value = record["value"].as_str().unwrap_or("");
        let ttl = record["ttl"].as_u64().unwrap_or(3600);
        let priority = record["priority"].as_u64();

        let line = if let Some(p) = priority {
            format!("{} {} IN {} {} {}\n", name, ttl, rtype, p, value)
        } else {
            format!("{} {} IN {} {}\n", name, ttl, rtype, value)
        };
        content.push_str(&line);
    }

    fs::write(&zone_path, content)?;

    Ok(format!("DNS zone updated for {}", domain))
}

async fn delete_dns_zone(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let domain = params["domain"].as_str().ok_or("Missing domain")?;
    let zone_path = format!("/etc/supercp/dns/{}.zone", domain);

    if Path::new(&zone_path).exists() {
        fs::remove_file(&zone_path)?;
    }

    Ok(format!("DNS zone deleted for {}", domain))
}

async fn request_ssl_cert(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let domain = params["domain"].as_str().ok_or("Missing domain")?;
    let email = params["email"].as_str().unwrap_or("admin@example.com");
    
    // Request certificate via Let's Encrypt using certbot
    let output = std::process::Command::new("sudo")
        .arg("-n")
        .arg("certbot")
        .arg("certonly")
        .arg("--non-interactive")
        .arg("--agree-tos")
        .arg("--nginx")
        .arg("-d")
        .arg(domain)
        .arg("-m")
        .arg(email)
        .arg("--register-unsafely-without-email")
        .output()?;
    
    if !output.status.success() {
        let error = String::from_utf8_lossy(&output.stderr);
        // If cert already exists, that's fine
        if !error.contains("Certificate not due for renewal") && !error.contains("Cert already exists") {
            return Err(format!("Failed to request SSL certificate for {}: {}", domain, error).into());
        }
    }
    
    // Verify the certificate was created
    let cert_dir = format!("/etc/letsencrypt/live/{}", domain);
    let cert_path = format!("{}/fullchain.pem", cert_dir);
    let key_path = format!("{}/privkey.pem", cert_dir);
    
    if !Path::new(&cert_path).exists() || !Path::new(&key_path).exists() {
        return Err(format!("Certificate files not found after certbot execution for {}", domain).into());
    }

    Ok(format!("SSL certificate requested and configured for {} via Let's Encrypt", domain))
}

async fn update_email_account(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let email = params["email"].as_str().ok_or("Missing email")?;
    let _password = params["password"].as_str().ok_or("Missing password")?;
    let quota_mb = params["quota_mb"].as_u64().unwrap_or(0);

    let email_dir = "/var/mail/supercp";
    fs::create_dir_all(email_dir)?;

    let account_path = format!("{}/{}.json", email_dir, email);
    let meta = json!({
        "email": email,
        "quota_mb": quota_mb,
    });

    fs::write(&account_path, serde_json::to_string_pretty(&meta)?)?;

    // In a real system, we would:
    // 1. Update Dovecot/Postfix virtual user maps
    // 2. Create maildir: /var/mail/vhosts/domain/user
    // 3. Set quota in Dovecot
    
    Ok(format!("Email account {} updated (quota: {}MB)", email, quota_mb))
}

async fn delete_email_account(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let email = params["email"].as_str().ok_or("Missing email")?;
    let account_path = format!("/var/mail/supercp/{}.json", email);

    if Path::new(&account_path).exists() {
        fs::remove_file(&account_path)?;
    }

    // In a real system, we would also remove the maildir and update maps
    Ok(format!("Email account {} deleted", email))
}

async fn get_system_stats() -> Result<Value, Box<dyn std::error::Error>> {
    let mut sys = System::new_all();
    sys.refresh_all();
    
    let mut networks = sysinfo::Networks::new_with_refreshed_list();

    // Wait a bit for CPU usage to be calculated correctly
    tokio::time::sleep(std::time::Duration::from_millis(200)).await;
    sys.refresh_cpu_all();
    networks.refresh(true);

    let cpu_usage = sys.global_cpu_usage();
    
    let memory = json!({
        "total": sys.total_memory() / 1024 / 1024, // MB
        "used": sys.used_memory() / 1024 / 1024,
        "free": sys.free_memory() / 1024 / 1024,
    });

    let mut disks_info = Vec::new();
    let disks = Disks::new_with_refreshed_list();
    for disk in &disks {
        disks_info.push(json!({
            "name": disk.name().to_string_lossy(),
            "mount_point": disk.mount_point().to_string_lossy(),
            "total": disk.total_space() / 1024 / 1024, // MB
            "available": disk.available_space() / 1024 / 1024,
        }));
    }

    let mut network_info = Vec::new();
    for (interface_name, data) in &networks {
        network_info.push(json!({
            "interface": interface_name,
            "received": data.received(),
            "transmitted": data.transmitted(),
            "total_received": data.total_received(),
            "total_transmitted": data.total_transmitted(),
        }));
    }

    let load_avg = System::load_average();

    Ok(json!({
        "cpu_usage": cpu_usage,
        "memory": memory,
        "disks": disks_info,
        "networks": network_info,
        "uptime": System::uptime(),
        "load_average": [load_avg.one, load_avg.five, load_avg.fifteen]
    }))
}

fn resolve_safe_path(path_str: &str) -> Result<std::path::PathBuf, Box<dyn std::error::Error>> {
    let path = Path::new(path_str);
    let target_path = if path.is_absolute() {
        path.to_path_buf()
    } else {
        Path::new("/home").join(path_str.trim_start_matches('/'))
    };

    if !target_path.starts_with("/home") {
        return Err("Access denied: Path must be within /home".into());
    }

    Ok(target_path)
}

async fn list_files(params: &Value) -> Result<Value, Box<dyn std::error::Error>> {
    let path_str = params["path"].as_str().ok_or("Missing path")?;
    let target_path = resolve_safe_path(path_str)?;

    if !target_path.exists() {
        return Err("Path does not exist".into());
    }

    let mut files = Vec::new();
    if let Ok(entries) = fs::read_dir(target_path) {
        for entry in entries {
            if let Ok(entry) = entry {
                let metadata = entry.metadata()?;
                let file_type = if metadata.is_dir() { "directory" } else { "file" };
                files.push(json!({
                    "name": entry.file_name().to_string_lossy(),
                    "type": file_type,
                    "size": metadata.len(),
                    "modified": metadata.modified()?.duration_since(std::time::UNIX_EPOCH)?.as_secs(),
                    "permissions": format!("{:o}", metadata.permissions().mode() & 0o777),
                }));
            }
        }
    }

    Ok(json!(files))
}

async fn read_file_content(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let path_str = params["path"].as_str().ok_or("Missing path")?;
    let target_path = resolve_safe_path(path_str)?;

    let content = fs::read_to_string(target_path)?;
    Ok(content)
}

async fn write_file_content(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let path_str = params["path"].as_str().ok_or("Missing path")?;
    let content = params["content"].as_str().ok_or("Missing content")?;
    let target_path = resolve_safe_path(path_str)?;

    if let Some(parent) = target_path.parent() {
        fs::create_dir_all(parent)?;
    }

    fs::write(target_path, content)?;
    Ok("File written successfully".to_string())
}

async fn delete_file_item(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let path_str = params["path"].as_str().ok_or("Missing path")?;
    let target_path = resolve_safe_path(path_str)?;

    if target_path.is_dir() {
        fs::remove_dir_all(target_path)?;
    } else {
        fs::remove_file(target_path)?;
    }

    Ok("Item deleted successfully".to_string())
}

async fn create_directory_item(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let path_str = params["path"].as_str().ok_or("Missing path")?;
    let target_path = resolve_safe_path(path_str)?;

    fs::create_dir_all(target_path)?;
    Ok("Directory created successfully".to_string())
}

async fn rename_file_item(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let from_str = params["from"].as_str().ok_or("Missing from path")?;
    let to_str = params["to"].as_str().ok_or("Missing to path")?;
    
    let from_path = resolve_safe_path(from_str)?;
    let to_path = resolve_safe_path(to_str)?;

    fs::rename(from_path, to_path)?;
    Ok("Item renamed successfully".to_string())
}

async fn get_logs(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let log_type = params["type"].as_str().unwrap_or("daemon");
    let lines = params["lines"].as_u64().unwrap_or(50) as usize;

    let log_path = match log_type {
        "nginx_access" => "/var/log/supercp/nginx_access.log",
        "nginx_error" => "/var/log/supercp/nginx_error.log",
        "php_error" => "/var/log/supercp/php_error.log",
        _ => "/home/super/getsupercp/storage/logs/super-daemon.log",
    };

    if !Path::new(log_path).exists() {
        return Ok(format!("Log file {} not found", log_path));
    }

    // Use 'tail' command for efficient reading of the end of the file
    let output = std::process::Command::new("tail")
        .arg("-n")
        .arg(lines.to_string())
        .arg(log_path)
        .output()?;

    if output.status.success() {
        let result = String::from_utf8_lossy(&output.stdout).to_string();
        if result.is_empty() {
            Ok("Log is empty".to_string())
        } else {
            Ok(result)
        }
    } else {
        let error = String::from_utf8_lossy(&output.stderr);
        Err(format!("Failed to read logs: {}", error).into())
    }
}

async fn get_service_logs(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let service = params["service"].as_str().ok_or("Missing service")?;
    let lines = params["lines"].as_u64().unwrap_or(50) as usize;

    let log_path = match service {
        "nginx" => "/var/log/supercp/nginx_error.log",
        "php8.4-fpm" => "/var/log/supercp/php_error.log",
        "mysql" => "/var/log/mysql/error.log",
        "redis-server" => "/var/log/redis/redis-server.log",
        _ => return Err(format!("Unknown service: {}", service).into()),
    };

    if !Path::new(log_path).exists() {
        return Ok(format!("Log file {} not found", log_path));
    }

    // Use 'tail' command for efficient reading
    let output = std::process::Command::new("tail")
        .arg("-n")
        .arg(lines.to_string())
        .arg(log_path)
        .output()?;

    if output.status.success() {
        let result = String::from_utf8_lossy(&output.stdout).to_string();
        Ok(if result.is_empty() { "Log is empty".to_string() } else { result })
    } else {
        let error = String::from_utf8_lossy(&output.stderr);
        Err(format!("Failed to read service logs: {}", error).into())
    }
}

async fn apply_firewall_rule(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let port = params["port"].as_u64().ok_or("Missing port")?;
    let protocol = params["protocol"].as_str().unwrap_or("tcp");
    let action = params["action"].as_str().unwrap_or("allow");
    let source = params["source"].as_str().unwrap_or("any");

    let mut cmd = std::process::Command::new("sudo");
    cmd.arg("-n").arg("ufw").arg(action);
    
    if source != "any" {
        cmd.arg("from").arg(source);
    }
    
    cmd.arg("to").arg("any").arg("port").arg(port.to_string()).arg("proto").arg(protocol);

    let status = cmd.status()?;
    
    if status.success() {
        Ok(format!("Firewall rule applied: {} {}/{} from {}", action, port, protocol, source))
    } else {
        Err(format!("Failed to apply firewall rule for port {}", port).into())
    }
}

async fn delete_firewall_rule(params: &Value) -> Result<String, Box<dyn std::error::Error>> {
    let port = params["port"].as_u64().ok_or("Missing port")?;
    let protocol = params["protocol"].as_str().unwrap_or("tcp");
    let action = params["action"].as_str().unwrap_or("allow");

    let status = std::process::Command::new("sudo")
        .arg("-n")
        .arg("ufw")
        .arg("delete")
        .arg(action)
        .arg(format!("{}/{}", port, protocol))
        .status()?;

    if status.success() {
        Ok(format!("Firewall rule deleted: {} {}/{}", action, port, protocol))
    } else {
        Err(format!("Failed to delete firewall rule for port {}", port).into())
    }
}

async fn toggle_firewall(params: &Value, state: Arc<Mutex<DaemonState>>) -> Result<String, Box<dyn std::error::Error>> {
    let enable = params["enable"].as_bool().ok_or("Missing enable parameter")?;
    
    let mut cmd = std::process::Command::new("sudo");
    cmd.arg("-n").arg("ufw");
    if enable {
        cmd.arg("--force").arg("enable");
    } else {
        cmd.arg("disable");
    }
    
    let status = cmd.status()?;
    
    if status.success() {
        let mut state = state.lock().await;
        state.firewall_active = enable;
        Ok(format!("Firewall {}", if enable { "enabled" } else { "disabled" }))
    } else {
        Err(format!("Failed to {} firewall", if enable { "enable" } else { "disable" }).into())
    }
}

async fn get_firewall_status(state: Arc<Mutex<DaemonState>>) -> Result<Value, Box<dyn std::error::Error>> {
    let output = std::process::Command::new("sudo").arg("-n").arg("ufw").arg("status").output()?;
    let status_str = String::from_utf8_lossy(&output.stdout);
    
    let active = status_str.contains("Status: active");
    
    // Update state
    {
        let mut state = state.lock().await;
        state.firewall_active = active;
    }

    // Parse rules from ufw status
    let mut rules = Vec::new();
    for line in status_str.lines() {
        if line.contains("/") && (line.contains("ALLOW") || line.contains("DENY")) {
            let parts: Vec<&str> = line.split_whitespace().collect();
            if parts.len() >= 2 {
                let port_proto: Vec<&str> = parts[0].split('/').collect();
                if port_proto.len() == 2 {
                    rules.push(json!({
                        "port": port_proto[0].parse::<u64>().unwrap_or(0),
                        "protocol": port_proto[1],
                        "action": parts[1].to_lowercase(),
                        "source": if parts.len() > 2 { parts[2] } else { "any" }
                    }));
                }
            }
        }
    }
    
    Ok(json!({
        "status": if active { "active" } else { "inactive" },
        "rules": rules
    }))
}

#[tokio::main]
async fn main() -> Result<(), Box<dyn std::error::Error>> {
    let socket_path = "/home/super/getsupercp/storage/framework/sockets/super-daemon.sock";

    // Clean up existing socket
    if fs::metadata(socket_path).is_ok() {
        fs::remove_file(socket_path)?;
    }

    let listener = UnixListener::bind(socket_path)?;
    // Ensure the web server (www-data) can write to the socket
    fs::set_permissions(socket_path, fs::Permissions::from_mode(0o666))?;

    println!("Super Daemon listening on {}", socket_path);

    let state = Arc::new(Mutex::new(DaemonState { firewall_active: true }));

    loop {
        let (stream, _) = listener.accept().await?;
        let state = Arc::clone(&state);
        
        tokio::spawn(async move {
            let (reader, mut writer) = tokio::io::split(stream);
            let mut reader = BufReader::new(reader);
            let mut line = String::new();

            if reader.read_line(&mut line).await.is_ok() {
                let req: Value = serde_json::from_str(&line).unwrap_or(Value::Null);
                let method = req["method"].as_str().unwrap_or("");
                
                let response = match method {
                    "ping" => json!({"jsonrpc": "2.0", "result": "pong", "id": req["id"]}),
                    "create_vhost" => {
                        match create_vhost(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_vhost" => {
                        match delete_vhost(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "list_vhosts" => {
                        match list_vhosts().await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_status" => {
                        match get_status().await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "restart_service" => {
                        match restart_service(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "create_backup" => {
                        match create_backup(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "create_db_backup" => {
                        match create_db_backup(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "restore_backup" => {
                        match restore_backup(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "restore_db_backup" => {
                        match restore_db_backup(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "reload_services" => {
                        match reload_services().await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "create_database" => {
                        match create_database(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_database" => {
                        match delete_database(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "list_databases" => {
                        match list_databases().await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "create_ftp_user" => {
                        match create_ftp_user(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_ftp_user" => {
                        match delete_ftp_user(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "list_ftp_users" => {
                        match list_ftp_users().await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_database_size" => {
                        match get_database_size(&req["params"]).await {
                            Ok(size) => json!({"jsonrpc": "2.0", "result": size, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_directory_size" => {
                        match get_directory_size(&req["params"]).await {
                            Ok(size) => json!({"jsonrpc": "2.0", "result": size, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "update_cron_jobs" => {
                        match update_cron_jobs(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "list_cron_jobs" => {
                        match list_cron_jobs(&req["params"]).await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "update_dns_zone" => {
                        match update_dns_zone(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_dns_zone" => {
                        match delete_dns_zone(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "request_ssl_cert" => {
                        match request_ssl_cert(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "update_email_account" => {
                        match update_email_account(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_email_account" => {
                        match delete_email_account(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_system_stats" => {
                        match get_system_stats().await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "list_files" => {
                        match list_files(&req["params"]).await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "read_file" => {
                        match read_file_content(&req["params"]).await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "write_file" => {
                        match write_file_content(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_file" => {
                        match delete_file_item(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "create_directory" => {
                        match create_directory_item(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "rename_file" => {
                        match rename_file_item(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_logs" => {
                        match get_logs(&req["params"]).await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_service_logs" => {
                        match get_service_logs(&req["params"]).await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "apply_firewall_rule" => {
                        match apply_firewall_rule(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "delete_firewall_rule" => {
                        match delete_firewall_rule(&req["params"]).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "toggle_firewall" => {
                        match toggle_firewall(&req["params"], Arc::clone(&state)).await {
                            Ok(msg) => json!({"jsonrpc": "2.0", "result": msg, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    "get_firewall_status" => {
                        match get_firewall_status(Arc::clone(&state)).await {
                            Ok(data) => json!({"jsonrpc": "2.0", "result": data, "id": req["id"]}),
                            Err(e) => json!({"jsonrpc": "2.0", "error": {"code": -32000, "message": e.to_string()}, "id": req["id"]}),
                        }
                    },
                    _ => json!({"jsonrpc": "2.0", "error": {"code": -32601, "message": "Method not found"}, "id": req["id"]}),
                };

                let _ = writer.write_all(format!("{}\n", response).as_bytes()).await;
            }
        });
    }
}
