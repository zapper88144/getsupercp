# GetSuperCP Implementation Plan

Build a secure, high-performance hosting control panel using Laravel 12 and React for the management interface, and a Rust-based daemon for privileged system operations. This hybrid architecture ensures that the web-facing application remains low-privileged while the Rust daemon handles sensitive system tasks via secure JSON-RPC communication over Unix Domain Sockets.

## Architecture Overview
- **Frontend**: React (Vite) with Tailwind CSS, using Inertia.js for a seamless SPA experience.
- **Backend**: Laravel 12 for business logic, authentication, and API management.
- **System Layer (Rust Daemon)**: A background service (`super-daemon`) listening on a Unix socket (`/run/super-daemon.sock`) to perform privileged operations (Nginx, PHP-FPM, User management).
- **CLI Tool (Rust)**: A utility (`super-cli`) for administrators to perform manual tasks or initial installation.
- **Communication**: JSON-RPC 2.0 over Unix Domain Sockets (UDS).

## Core Service Modules

### 1. Web Service (Nginx & PHP-FPM)
- **Nginx**: Rust daemon handles atomic configuration writes, validation (`nginx -t`), and reloads.
- **PHP-FPM**: Isolated pools per user/site, managed by the Rust daemon.
- **Logic**: Laravel generates config payloads from Blade templates and sends them to the Rust daemon.

### 2. Mail Service (Postfix & Dovecot)
- **Backend**: Configured to use MariaDB/MySQL for domains, accounts, and aliases.
- **Management**: Laravel manages SQL tables via Eloquent; services query them in real-time.
- **Rust Role**: Manages SSL/TLS certificates and service health.

### 3. DNS Service (PowerDNS)
- **Backend**: Uses the PowerDNS Generic MySQL (`gmysql`) backend.
- **Management**: Laravel writes directly to PowerDNS tables.
- **Rust Role**: Automates DNSSEC signing and manages secondary DNS notifications.

### 4. Database Service (MariaDB/MySQL)
- **Management**: Laravel uses a restricted local administrative connection to manage users and databases.
- **Quotas**: Rust daemon monitors disk usage per database.

### 5. FTP Service (Pure-FTPd)
- **Backend**: MySQL authentication for virtual users.
- **Management**: Laravel manages the `ftp_users` table.

### 6. Security & Firewall
- **Firewall**: Rust daemon manages `nftables` or `ufw`, restricting ports 80/443 to Cloudflare IP ranges if enabled.
- **Monitoring**: Rust daemon tails system logs for brute-force detection and automated IP banning.
- **SSL**: Automated Let's Encrypt and Cloudflare Origin SSL provisioning.

## Cloudflare Integration
- **DNS Sync**: Laravel jobs sync local PowerDNS records with Cloudflare via API.
- **Proxy Management**: Toggle Cloudflare Proxy (Orange Cloud) directly from the panel.
- **Origin SSL**: Automated CSR generation and installation of Cloudflare Origin Certificates.
- **Cache Purge**: Manual and automated cache purging from the site settings.

## Implementation Steps

### Step 1: Initialization
- Initialize Laravel 12 with React/Inertia (Breeze).
- Set up Rust workspace with `super-daemon` and `super-cli`.
- Create directory structure for system templates and Unix sockets.

### Step 2: System Bridge
- Develop the Rust `super-daemon` with `tokio` and `serde_json`.
- Implement `RustDaemonClient` in Laravel for JSON-RPC communication.
- Configure `systemd` for the daemon and set socket permissions.

### Step 3: Service Integration
- Configure Postfix, Dovecot, PowerDNS, and Pure-FTPd for MySQL backends.
- Implement Laravel services and jobs for each module.
- Build React management interfaces for all services.

### Step 4: Security & Cloudflare
- Implement the Rust-based security engine (firewall, log monitoring).
- Integrate Cloudflare API for DNS and SSL management.
- Finalize real-time monitoring with Laravel Reverb.
