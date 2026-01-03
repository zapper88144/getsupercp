use clap::{Parser, Subcommand};
use serde_json::{json, Value};
use std::io::{BufRead, BufReader, Write};
use std::os::unix::net::UnixStream;
use uuid::Uuid;

#[derive(Parser)]
#[command(name = "super-cli")]
#[command(about = "SuperCP Command Line Interface", long_about = None)]
struct Cli {
    #[command(subcommand)]
    command: Commands,
}

#[derive(Subcommand)]
enum Commands {
    /// Ping the daemon
    Ping,
    /// List all provisioned vhosts
    List,
    /// List all provisioned databases
    ListDbs,
    /// List all provisioned FTP users
    ListFtp,
    /// List all provisioned cron jobs
    ListCron,
    /// Get system and daemon status
    Status,
    /// Reload system services
    Reload,
    /// Create a new vhost (System only, does not update DB)
    Create {
        #[arg(short, long)]
        domain: String,
        #[arg(short, long)]
        user: String,
        #[arg(short, long)]
        root: String,
        #[arg(short, long, default_value = "8.4")]
        php: String,
    },
    /// Delete a vhost (System only, does not update DB)
    Delete {
        #[arg(short, long)]
        domain: String,
        #[arg(short, long)]
        user: String,
    },
}

fn call_daemon(method: &str, params: Value) -> Result<Value, Box<dyn std::error::Error>> {
    let socket_path = "/home/super/getsupercp/storage/framework/sockets/super-daemon.sock";
    let mut stream = UnixStream::connect(socket_path)?;
    
    let id = Uuid::new_v4().to_string();
    let request = json!({
        "jsonrpc": "2.0",
        "method": method,
        "params": params,
        "id": id
    });

    stream.write_all(format!("{}\n", request).as_bytes())?;
    
    let mut reader = BufReader::new(stream);
    let mut response_line = String::new();
    reader.read_line(&mut response_line)?;
    
    let response: Value = serde_json::from_str(&response_line)?;
    Ok(response)
}

fn main() -> Result<(), Box<dyn std::error::Error>> {
    let cli = Cli::parse();

    match &cli.command {
        Commands::Ping => {
            let res = call_daemon("ping", json!({}))?;
            println!("Response: {}", res["result"].as_str().unwrap_or("Error"));
        }
        Commands::List => {
            let res = call_daemon("list_vhosts", json!({}))?;
            if let Some(list) = res["result"].as_array() {
                println!("Provisioned Domains:");
                for domain in list {
                    println!(" - {}", domain.as_str().unwrap_or(""));
                }
            } else {
                println!("Error: {}", res["error"]["message"].as_str().unwrap_or("Unknown error"));
            }
        }
        Commands::ListDbs => {
            let res = call_daemon("list_databases", json!({}))?;
            if let Some(list) = res["result"].as_array() {
                println!("Provisioned Databases:");
                for db in list {
                    println!(" - {}", db.as_str().unwrap_or(""));
                }
            } else {
                println!("Error: {}", res["error"]["message"].as_str().unwrap_or("Unknown error"));
            }
        }
        Commands::ListFtp => {
            let res = call_daemon("list_ftp_users", json!({}))?;
            if let Some(list) = res["result"].as_array() {
                println!("Provisioned FTP Users:");
                for user in list {
                    println!(" - {}", user.as_str().unwrap_or(""));
                }
            } else {
                println!("Error: {}", res["error"]["message"].as_str().unwrap_or("Unknown error"));
            }
        }
        Commands::ListCron => {
            let res = call_daemon("list_cron_jobs", json!({}))?;
            if let Some(list) = res["result"].as_array() {
                println!("Provisioned Cron Jobs (by user):");
                for user in list {
                    println!(" - {}", user.as_str().unwrap_or(""));
                }
            } else {
                println!("Error: {}", res["error"]["message"].as_str().unwrap_or("Unknown error"));
            }
        }
        Commands::Status => {
            let res = call_daemon("get_status", json!({}))?;
            if let Some(status) = res["result"].as_object() {
                println!("System Status:");
                for (key, value) in status {
                    println!(" - {}: {}", key, value.as_str().unwrap_or(""));
                }
            } else {
                println!("Error: {}", res["error"]["message"].as_str().unwrap_or("Unknown error"));
            }
        }
        Commands::Reload => {
            let res = call_daemon("reload_services", json!({}))?;
            println!("Response: {}", res["result"].as_str().unwrap_or("Error"));
        }
        Commands::Create { domain, user, root, php } => {
            let res = call_daemon("create_vhost", json!({
                "domain": domain,
                "user": user,
                "root": root,
                "php_version": php
            }))?;
            println!("Response: {}", res["result"].as_str().unwrap_or("Error"));
        }
        Commands::Delete { domain, user } => {
            let res = call_daemon("delete_vhost", json!({
                "domain": domain,
                "user": user
            }))?;
            println!("Response: {}", res["result"].as_str().unwrap_or("Error"));
        }
    }

    Ok(())
}
