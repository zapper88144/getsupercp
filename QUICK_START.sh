#!/bin/bash
# SuperCP Installer Quick Reference
# Place this in root directory for easy access

cat << 'EOF'

╔════════════════════════════════════════════════════════════════════╗
║                   SuperCP Installer Quick Reference                ║
╚════════════════════════════════════════════════════════════════════╝

📦 INSTALLATION
═══════════════════════════════════════════════════════════════════

Full automated installation:
  $ ./install.sh

What it does:
  ✓ Check dependencies (PHP, Node.js, Composer, npm)
  ✓ Setup .env configuration
  ✓ Create directories and sockets
  ✓ Install PHP dependencies
  ✓ Install Node.js dependencies
  ✓ Setup database and run migrations
  ✓ Build frontend assets
  ✓ Compile Rust daemon
  ✓ Optimize Laravel
  ✓ Fix permissions
  ✓ Run tests (optional)

Time required: ~5-10 minutes


🚀 AFTER INSTALLATION
═══════════════════════════════════════════════════════════════════

Start development environment (3 terminals):

  Terminal 1 - Laravel Server:
  $ php artisan serve

  Terminal 2 - Frontend Watcher:
  $ npm run dev

  Terminal 3 - Rust Daemon:
  $ ./rust/target/release/super-daemon

Access application:
  🌐 http://localhost:8000


📝 CONFIGURATION
═══════════════════════════════════════════════════════════════════

Edit environment:
  $ nano .env

Key variables:
  APP_URL=http://localhost
  APP_ENV=production (or development)
  DB_CONNECTION=sqlite
  DAEMON_SOCKET=/var/run/super-daemon.sock


🔍 TROUBLESHOOTING
═══════════════════════════════════════════════════════════════════

View installation log:
  $ cat installer.log

Check test suite:
  $ php artisan test

Check Laravel logs:
  $ tail -f storage/logs/laravel.log

Fix database permissions:
  $ chmod 664 database/database.sqlite
  $ chmod 755 database/


📚 DOCUMENTATION
═══════════════════════════════════════════════════════════════════

Installer guide:
  $ cat INSTALLER.md

Implementation status:
  $ cat IMPLEMENTATION_STATUS.md

Installation summary:
  $ cat INSTALLER_COMPLETE.md


🛠️ DEVELOPMENT WORKFLOW
═══════════════════════════════════════════════════════════════════

Create a component:
  $ php artisan make:component YourComponent

Create a controller:
  $ php artisan make:controller YourController

Create a model:
  $ php artisan make:model YourModel -mfs

Run tests:
  $ php artisan test

Format code:
  $ vendor/bin/pint


🔧 PRODUCTION DEPLOYMENT
═══════════════════════════════════════════════════════════════════

1. Run installer:
   $ ./install.sh

2. Update .env:
   $ nano .env

3. Set production mode:
   APP_ENV=production

4. Build frontend:
   $ npm run build

5. Setup systemd services for daemon and Laravel

6. Configure reverse proxy (Nginx/Apache)

7. Enable HTTPS with SSL certificates


📊 SYSTEM REQUIREMENTS
═══════════════════════════════════════════════════════════════════

Minimum:
  - PHP 8.4+
  - Node.js 18+
  - Composer
  - npm
  - 1GB RAM
  - 2GB disk space

Recommended:
  - PHP 8.4.16
  - Node.js 20.x
  - 2GB+ RAM
  - 5GB+ disk space
  - Modern CPU (multi-core)


✨ FEATURES INCLUDED
═══════════════════════════════════════════════════════════════════

Core Control Panel:
  ✓ Web Domain Management
  ✓ Database Management
  ✓ Firewall Rules
  ✓ Email Accounts
  ✓ FTP Users
  ✓ Cron Jobs
  ✓ DNS Zones
  ✓ File Manager
  ✓ Backups
  ✓ System Monitoring
  ✓ Log Viewer
  ✓ Service Management

Infrastructure:
  ✓ Real-time system monitoring
  ✓ SSL certificate management
  ✓ Backup and restore
  ✓ User authentication
  ✓ Permission-based access
  ✓ API endpoints
  ✓ MCP server integration


🎯 NEXT STEPS
═══════════════════════════════════════════════════════════════════

After installation is complete:

1. Access dashboard at http://localhost:8000
2. Create admin user or login
3. Review System > Status
4. Configure domains, databases, email
5. Setup backups and monitoring
6. Deploy to production when ready


💡 TIPS
═══════════════════════════════════════════════════════════════════

- Use 'npm run dev' for hot-reload during development
- Check 'installer.log' for installation details
- Run tests regularly: php artisan test
- Update dependencies: composer update && npm update
- Monitor logs: tail -f storage/logs/laravel.log


📞 SUPPORT
═══════════════════════════════════════════════════════════════════

Documentation: See included .md files
Issues: Check installer.log and storage/logs/
Tests: Run php artisan test
Status: See IMPLEMENTATION_STATUS.md


════════════════════════════════════════════════════════════════════
                        SuperCP Ready! 🚀
════════════════════════════════════════════════════════════════════

EOF
