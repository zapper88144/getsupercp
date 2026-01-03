# SuperCP (Get Super Control Panel)

SuperCP is a modern, high-performance hosting control panel built with Laravel 12, React 18, and a custom Rust-based system daemon. It provides a comprehensive suite of tools for managing web servers, domains, databases, and more.

## Features

- **Dashboard**: Real-time system metrics and overview.
- **Web Domains**: Manage Nginx vhosts and SSL certificates.
- **Databases**: Provision MySQL and PostgreSQL databases.
- **Firewall**: Manage UFW rules and system security.
- **Services**: Monitor and control system services (Nginx, PHP-FPM, MySQL, etc.).
- **FTP Users**: Create and manage FTP accounts.
- **Cron Jobs**: Schedule and manage system tasks.
- **DNS Management**: Manage DNS zones and records (BIND).
- **Email Accounts**: Provision email accounts (Postfix/Dovecot).
- **File Manager**: Full-featured web-based file browser.
- **Backups**: Automated web and database backups.
- **System Logs**: Real-time log viewer for system and application logs.
- **MCP Server**: Full Model Context Protocol implementation for AI-driven management.

## Tech Stack

- **Backend**: Laravel 12.44.0 (PHP 8.4.16)
- **Frontend**: React 18.3.1, Inertia.js 2.0.18, Tailwind CSS 4.0
- **System Agent**: Rust daemon (Tokio async runtime)
- **Database**: SQLite (Metadata), MySQL 8.0 (User Databases)
- **AI Interface**: laravel/mcp (v0.5.1)

## Getting Started

### Prerequisites

- PHP 8.4+
- Node.js 20+
- Rust (for building the daemon)
- MySQL 8.0+
- Nginx
- UFW

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/super/getsupercp.git
   cd getsupercp
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JS dependencies:
   ```bash
   npm install
   ```

4. Build the Rust daemon:
   ```bash
   cd rust
   cargo build --release
   ```

5. Set up environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Start the development server:
   ```bash
   npm run dev
   ```

## AI Management (MCP)

SuperCP includes a built-in MCP server that allows you to manage your server using AI agents. The MCP endpoint is available at `/mcp`.

To use the MCP server, register it in your MCP client (like Claude Desktop or Cursor) using the following configuration:

```json
{
  "mcpServers": {
    "supercp": {
      "command": "php",
      "args": ["artisan", "mcp:start", "SuperCP"]
    }
  }
}
```

## License

The SuperCP is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
