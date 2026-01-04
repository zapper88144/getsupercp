# Rust Daemon JSON-RPC Methods Reference

## Overview
The GetSuperCP Rust daemon provides a comprehensive set of JSON-RPC 2.0 methods for managing hosting infrastructure. All methods communicate over a Unix Domain Socket at `/home/super/getsupercp/storage/framework/sockets/super-daemon.sock`.

## Request Format
```json
{
  "jsonrpc": "2.0",
  "method": "method_name",
  "params": { "param1": "value1" },
  "id": 1
}
```

## Response Format
**Success:**
```json
{
  "jsonrpc": "2.0",
  "result": { "success": true, "data": "..." },
  "id": 1
}
```

**Error:**
```json
{
  "jsonrpc": "2.0",
  "error": {
    "code": -32603,
    "message": "Connection failed"
  },
  "id": 1
}
```

## Available Methods

### System Operations

#### ping
Health check for daemon availability.
```json
{
  "method": "ping",
  "params": {}
}
```
**Response:** `{ "pong": true }`

#### get_system_stats
Retrieve system performance metrics.
```json
{
  "method": "get_system_stats",
  "params": {}
}
```
**Response:**
```json
{
  "cpu_usage": 45.2,
  "memory_used_mb": 2048,
  "memory_total_mb": 8192,
  "disk_used_gb": 50,
  "disk_total_gb": 100,
  "network_tx_mb": 512,
  "network_rx_mb": 1024
}
```

#### get_status
Get overall system status.
```json
{
  "method": "get_status",
  "params": {}
}
```
**Response:**
```json
{
  "uptime_seconds": 86400,
  "running_services": 15,
  "failed_services": 0,
  "nginx_status": "running",
  "php_fpm_status": "running",
  "mysql_status": "running",
  "postfix_status": "running",
  "dovecot_status": "running",
  "powerdns_status": "running"
}
```

#### restart_service
Restart a specific service.
```json
{
  "method": "restart_service",
  "params": {
    "service": "nginx"
  }
}
```
**Service Names:** nginx, php-fpm, mysql, postfix, dovecot, powerdns, pure-ftpd

---

### Web Domain Management

#### create_vhost
Create a new virtual host (Nginx + PHP-FPM).
```json
{
  "method": "create_vhost",
  "params": {
    "domain": "example.com",
    "root": "/var/www/example.com",
    "php_version": "8.4",
    "user": "example_user",
    "aliases": ["www.example.com", "api.example.com"]
  }
}
```
**Response:** `{ "created": true, "vhost_file": "..." }`

#### delete_vhost
Remove a virtual host configuration.
```json
{
  "method": "delete_vhost",
  "params": {
    "domain": "example.com"
  }
}
```
**Response:** `{ "deleted": true }`

#### list_vhosts
Get list of all configured vhosts.
```json
{
  "method": "list_vhosts",
  "params": {}
}
```
**Response:**
```json
{
  "vhosts": [
    {
      "domain": "example.com",
      "root": "/var/www/example.com",
      "php_version": "8.4",
      "user": "example_user",
      "ssl_enabled": true,
      "ssl_cert_path": "/etc/letsencrypt/live/example.com/fullchain.pem"
    }
  ]
}
```

---

### SSL Certificate Management

#### request_ssl_cert
Request or renew SSL certificate via Let's Encrypt.
```json
{
  "method": "request_ssl_cert",
  "params": {
    "domain": "example.com",
    "email": "admin@example.com",
    "renew": false
  }
}
```
**Response:**
```json
{
  "success": true,
  "cert_path": "/etc/letsencrypt/live/example.com/fullchain.pem",
  "key_path": "/etc/letsencrypt/live/example.com/privkey.pem",
  "expires": "2025-01-15"
}
```

---

### Database Management

#### create_database
Create a new MySQL database with dedicated user.
```json
{
  "method": "create_database",
  "params": {
    "database": "myapp_db",
    "db_user": "myapp_user",
    "password": "secure_password",
    "engine": "InnoDB"
  }
}
```
**Response:**
```json
{
  "created": true,
  "database": "myapp_db",
  "user": "myapp_user",
  "host": "localhost"
}
```

#### delete_database
Drop a database and its associated user.
```json
{
  "method": "delete_database",
  "params": {
    "database": "myapp_db",
    "db_user": "myapp_user"
  }
}
```
**Response:** `{ "deleted": true }`

#### list_databases
Get list of all databases.
```json
{
  "method": "list_databases",
  "params": {}
}
```
**Response:**
```json
{
  "databases": [
    {
      "name": "myapp_db",
      "user": "myapp_user",
      "created_at": "2025-01-01",
      "size_mb": 256
    }
  ]
}
```

---

### Email Account Management

#### update_email_account
Create or update an email account.
```json
{
  "method": "update_email_account",
  "params": {
    "email": "user@example.com",
    "password": "secure_password",
    "quota_mb": 1024
  }
}
```
**Response:** `{ "updated": true, "email": "user@example.com" }`

#### delete_email_account
Delete an email account.
```json
{
  "method": "delete_email_account",
  "params": {
    "email": "user@example.com"
  }
}
```
**Response:** `{ "deleted": true }`

---

### DNS Zone Management

#### update_dns_zone
Create, update, or delete a DNS zone and manage records.
```json
{
  "method": "update_dns_zone",
  "params": {
    "domain": "example.com",
    "action": "create",
    "records": [
      {
        "name": "www",
        "type": "A",
        "content": "192.168.1.1",
        "ttl": 3600
      },
      {
        "name": "@",
        "type": "MX",
        "content": "mail.example.com",
        "priority": 10
      }
    ]
  }
}
```

**Actions:**
- `create` - Create new zone
- `add_record` - Add single record
- `update_record` - Modify record
- `delete_record` - Remove record
- `delete` - Delete entire zone

**Response:**
```json
{
  "success": true,
  "domain": "example.com",
  "record_count": 2
}
```

---

### FTP User Management

#### create_ftp_user
Create a new FTP user account.
```json
{
  "method": "create_ftp_user",
  "params": {
    "username": "ftpuser",
    "password": "secure_password",
    "home_dir": "/home/ftp/ftpuser",
    "shell": "/bin/false"
  }
}
```
**Response:** `{ "created": true, "username": "ftpuser" }`

#### delete_ftp_user
Delete an FTP user account.
```json
{
  "method": "delete_ftp_user",
  "params": {
    "username": "ftpuser"
  }
}
```
**Response:** `{ "deleted": true }`

#### list_ftp_users
Get list of all FTP users.
```json
{
  "method": "list_ftp_users",
  "params": {}
}
```
**Response:**
```json
{
  "users": [
    {
      "username": "ftpuser",
      "home_dir": "/home/ftp/ftpuser",
      "status": "active"
    }
  ]
}
```

---

### Firewall Management

#### get_firewall_status
Get current firewall status.
```json
{
  "method": "get_firewall_status",
  "params": {}
}
```
**Response:**
```json
{
  "enabled": true,
  "rules_count": 15,
  "default_policy": "deny"
}
```

#### toggle_firewall
Enable or disable the firewall.
```json
{
  "method": "toggle_firewall",
  "params": {
    "enabled": true
  }
}
```
**Response:** `{ "success": true, "enabled": true }`

#### apply_firewall_rule
Add or modify a firewall rule.
```json
{
  "method": "apply_firewall_rule",
  "params": {
    "port": 443,
    "protocol": "tcp",
    "action": "allow",
    "source": "any"
  }
}
```
**Response:** `{ "applied": true, "rule_id": 1 }`

#### delete_firewall_rule
Remove a firewall rule.
```json
{
  "method": "delete_firewall_rule",
  "params": {
    "port": 443,
    "protocol": "tcp",
    "action": "allow"
  }
}
```
**Response:** `{ "deleted": true }`

---

### Backup Operations

#### create_backup
Create a backup of specified resources.
```json
{
  "method": "create_backup",
  "params": {
    "type": "full",
    "targets": {
      "databases": ["myapp_db"],
      "directories": ["/var/www/example.com"],
      "exclude": ["/var/www/example.com/temp"]
    }
  }
}
```
**Types:** full, database, files, domain

**Response:**
```json
{
  "success": true,
  "backup_id": "backup_20250101_120000",
  "path": "/backups/backup_20250101_120000.tar.gz",
  "size_mb": 512
}
```

#### restore_backup
Restore from a backup file.
```json
{
  "method": "restore_backup",
  "params": {
    "backup_path": "/backups/backup_20250101_120000.tar.gz",
    "target_dir": "/"
  }
}
```
**Response:** `{ "restored": true, "items_restored": 150 }`

#### create_db_backup
Create database-only backup.
```json
{
  "method": "create_db_backup",
  "params": {
    "databases": ["myapp_db", "wordpress_db"]
  }
}
```
**Response:**
```json
{
  "success": true,
  "backup_file": "/backups/db_backup_20250101_120000.sql.gz",
  "size_mb": 256
}
```

#### restore_db_backup
Restore database from backup.
```json
{
  "method": "restore_db_backup",
  "params": {
    "backup_path": "/backups/db_backup_20250101_120000.sql.gz",
    "database": "myapp_db"
  }
}
```
**Response:** `{ "restored": true, "rows_imported": 50000 }`

---

### Cron Job Management

#### update_cron_jobs
Manage user cron jobs.
```json
{
  "method": "update_cron_jobs",
  "params": {
    "user": "www-data",
    "action": "add",
    "job": {
      "schedule": "0 2 * * *",
      "command": "/usr/bin/php /var/www/example.com/artisan schedule:run"
    }
  }
}
```
**Actions:** add, update, delete

**Response:** `{ "success": true, "job_count": 3 }`

#### list_cron_jobs
Get all cron jobs for a user.
```json
{
  "method": "list_cron_jobs",
  "params": {
    "user": "www-data"
  }
}
```
**Response:**
```json
{
  "jobs": [
    {
      "schedule": "0 2 * * *",
      "command": "/usr/bin/php /var/www/example.com/artisan schedule:run"
    }
  ]
}
```

---

### File Operations

#### list_files
List files in a directory.
```json
{
  "method": "list_files",
  "params": {
    "path": "/var/www/example.com"
  }
}
```
**Response:**
```json
{
  "files": [
    {
      "name": "index.php",
      "type": "file",
      "size": 1024,
      "modified": "2025-01-01T12:00:00Z",
      "permissions": "644"
    }
  ]
}
```

#### read_file
Read file contents.
```json
{
  "method": "read_file",
  "params": {
    "path": "/var/www/example.com/config.php"
  }
}
```
**Response:**
```json
{
  "content": "<?php\n// Configuration\n...",
  "size": 2048,
  "encoding": "utf-8"
}
```

#### write_file
Write or update a file.
```json
{
  "method": "write_file",
  "params": {
    "path": "/var/www/example.com/test.txt",
    "content": "File content",
    "mode": "644"
  }
}
```
**Response:** `{ "written": true, "size": 12 }`

#### delete_file
Delete a file.
```json
{
  "method": "delete_file",
  "params": {
    "path": "/var/www/example.com/old_file.txt"
  }
}
```
**Response:** `{ "deleted": true }`

#### create_directory
Create a directory.
```json
{
  "method": "create_directory",
  "params": {
    "path": "/var/www/example.com/uploads",
    "mode": "755"
  }
}
```
**Response:** `{ "created": true, "path": "/var/www/example.com/uploads" }`

#### rename_file
Rename or move a file.
```json
{
  "method": "rename_file",
  "params": {
    "old_path": "/var/www/example.com/old.txt",
    "new_path": "/var/www/example.com/new.txt"
  }
}
```
**Response:** `{ "renamed": true }`

---

### Logging

#### get_logs
Get system or service logs.
```json
{
  "method": "get_logs",
  "params": {
    "service": "nginx",
    "lines": 100
  }
}
```
**Response:**
```json
{
  "logs": [
    "2025-01-01 12:00:00 [INFO] Server started",
    "2025-01-01 12:00:01 [WARNING] Configuration loaded"
  ]
}
```

#### get_service_logs
Get specific service error logs.
```json
{
  "method": "get_service_logs",
  "params": {
    "service": "php-fpm",
    "error_only": true
  }
}
```
**Response:**
```json
{
  "logs": [
    "2025-01-01 12:30:45 [ERROR] PHP Fatal error"
  ]
}
```

---

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| -32603 | Connection failed | Cannot connect to daemon |
| -32601 | Method not found | Unknown JSON-RPC method |
| -32000 | Server error | General daemon error |
| -32001 | Invalid params | Parameter validation failed |
| -32002 | Permission denied | Insufficient permissions |
| -32003 | Resource not found | Resource does not exist |

---

## Usage Example in PHP (via RustDaemonClient)

```php
use App\Services\RustDaemonClient;

$client = new RustDaemonClient();

// Create a virtual host
$result = $client->createVhost([
    'domain' => 'mysite.com',
    'root' => '/var/www/mysite.com',
    'php_version' => '8.4',
    'user' => 'mysite',
]);

// Get system stats
$stats = $client->getSystemStats();
echo "CPU: " . $stats['cpu_usage'] . "%";

// Request SSL certificate
$ssl = $client->requestSslCert('mysite.com', 'admin@mysite.com', false);

// Create database
$db = $client->createDatabase('myapp_db', 'myapp_user', 'password', 'InnoDB');

// Apply firewall rule
$client->applyFirewallRule(443, 'tcp', 'allow', 'any');
```

---

## Socket Connection Details

- **Type:** Unix Domain Socket (AF_UNIX)
- **Path:** `/home/super/getsupercp/storage/framework/sockets/super-daemon.sock`
- **Permissions:** 0o666 (read/write for all)
- **Timeout:** 30 seconds (configurable)
- **Protocol:** JSON-RPC 2.0 over newline-delimited JSON

---

## Rate Limiting & Performance

- No built-in rate limiting (implement at Laravel level)
- Daemon handles ~100+ concurrent method calls
- Average response time: 50-500ms (depends on operation)
- Socket buffer size: 256KB

---

## Security Considerations

1. **Path Validation:** All file operations restricted to `/home` directory
2. **Command Injection:** All user inputs sanitized before shell execution
3. **Socket Permissions:** Only readable/writable by authorized users
4. **Logging:** All daemon operations logged with timestamps
5. **No Direct Shell:** No arbitrary command execution (only predefined methods)

---

## Daemon Restart

To restart the daemon and clear socket:
```bash
systemctl restart super-daemon
# Or manually
pkill -f super-daemon
/path/to/super-daemon
```

The socket is automatically recreated on startup.
