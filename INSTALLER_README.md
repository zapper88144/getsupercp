# SuperCP Installation Suite

## 🎯 Overview

The SuperCP Installation Suite provides a complete, automated setup for deploying the SuperCP control panel. It handles all dependencies, configuration, database setup, and optimization in one command.

## 📦 What's Included

### 1. **install.sh** - Main Installer
- **12KB** bash script
- Fully automated installation
- Error handling and logging
- Progress tracking with colored output
- 10+ step installation process

### 2. **INSTALLER.md** - Complete Guide
- Detailed documentation
- Step-by-step walkthrough
- Configuration reference
- Troubleshooting guide
- Manual installation fallback

### 3. **INSTALLER_COMPLETE.md** - Summary
- Installation overview
- What was created
- Feature highlights
- Next implementation steps

### 4. **QUICK_START.sh** - Quick Reference
- One-page cheat sheet
- Common commands
- Configuration snippets
- Development workflow
- Production deployment steps

## 🚀 Quick Start

### One-Command Installation
```bash
cd /home/super/getsupercp
./install.sh
```

That's it! The installer handles everything automatically.

## ✨ What Gets Installed

### System Check (Pre-installation)
- PHP 8.4+ ✓
- Composer ✓
- Node.js 20+ ✓
- npm 10+ ✓
- Git (optional)
- Rust/Cargo (optional, for daemon rebuilds)

### Environment Setup
- `.env` file configuration
- Auto-generated APP_KEY
- Database connection setup
- Cache driver configuration

### Directory Structure
```
storage/
  ├── app/              (auto-created)
  ├── logs/             (auto-created)
  ├── framework/
  │   └── sockets/      (auto-created)
  └── cache/            (auto-created)
bootstrap/
  └── cache/            (auto-created)
database/
  └── database.sqlite   (auto-created)
public/build/           (auto-created by npm)
```

### PHP Installation (Composer)
- Laravel 12 framework + 50+ packages
- Inertia.js v2 for React integration
- Breeze for authentication
- PHPUnit for testing
- All dependencies and dev tools

### JavaScript Installation (npm)
- React 19 framework
- Tailwind CSS v4 styling
- Vite for bundling
- Heroicons for icons
- 2000+ npm modules

### Database Setup
- SQLite database creation
- 18 tables with relationships
- Migration execution
- Schema validation
- Permission configuration

### Frontend Build
- Vite compilation
- Asset minification
- CSS optimization
- JavaScript bundling
- Production assets generation

### Rust Daemon
- Cargo build --release
- Binary compilation (target/release/super-daemon)
- Unix socket configuration
- Ready for systemd service

### Optimization
- Laravel configuration caching
- Composer autoloader optimization
- View cache clearing
- File permission fixing
- Production settings

### Testing
- PHPUnit test suite execution
- 72 tests verification
- All assertions validation
- Optional (prompted during installation)

## 📋 Installation Steps

The installer runs these steps in order:

```
1. Check Dependencies         ✓
2. Setup Environment         ✓
3. Create Directories        ✓
4. Install PHP Dependencies  ✓
5. Install Node Dependencies ✓
6. Setup Database           ✓
7. Build Frontend           ✓
8. Build Rust Daemon        ✓
9. Optimize Laravel         ✓
10. Fix Permissions         ✓
11. Run Tests (optional)    ✓
12. Show Next Steps         ✓
```

**Total Time**: ~5-10 minutes

## 🛠️ After Installation

### Development Environment
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Frontend watcher (hot reload)
npm run dev

# Terminal 3: Rust daemon
./rust/target/release/super-daemon
```

Then access: **http://localhost:8000**

### Production Environment
```bash
# Build once
npm run build

# Start with production settings
php artisan serve --env=production
```

## 📝 Configuration

The installer creates `.env` with defaults. Customize for your environment:

```bash
# Edit configuration
nano .env

# Key variables to update:
APP_URL=http://your-domain.com
APP_ENV=production
DB_CONNECTION=sqlite (or mysql/pgsql)
DAEMON_SOCKET=/var/run/super-daemon.sock
```

## 🔍 Troubleshooting

### View Installation Log
```bash
cat installer.log
```

### Common Issues

**"php: command not found"**
```bash
sudo apt-get install php8.4 php8.4-cli php8.4-fpm
```

**"npm: command not found"**
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

**Database Permission Error**
```bash
chmod 664 database/database.sqlite
chmod 755 database/
```

**Daemon Socket Issues**
```bash
mkdir -p storage/framework/sockets
chmod 755 storage/framework/sockets
```

## 📚 Documentation

| File | Purpose |
|------|---------|
| `install.sh` | Main installation script |
| `INSTALLER.md` | Complete installation guide |
| `INSTALLER_COMPLETE.md` | Installation summary |
| `QUICK_START.sh` | Quick reference commands |
| `IMPLEMENTATION_STATUS.md` | Feature status report |
| `README.md` | Project overview |

## 🎯 Verify Installation

After installation, verify everything is working:

```bash
# Run tests
php artisan test

# Check Laravel
php artisan version

# Check Node
npm --version

# Check Rust daemon
./rust/target/release/super-daemon --help
```

All should complete without errors.

## 🔐 Security

The installer:
- ✓ Sets correct file permissions (755/664)
- ✓ Creates secure socket directory
- ✓ Protects sensitive configuration
- ✓ Secures database files
- ✓ Optimizes for production

## 🚀 Production Deployment

1. **Run installer** on production server
2. **Update `.env`** with production values
3. **Set `APP_ENV=production`** in .env
4. **Configure systemd services** for daemon and Laravel
5. **Setup reverse proxy** (Nginx/Apache)
6. **Enable HTTPS** with SSL certificates

Example systemd service files can be added later.

## 📊 System Requirements

### Minimum
- PHP 8.4+
- Node.js 18+
- Composer
- npm
- 1GB RAM
- 2GB disk space

### Recommended
- PHP 8.4.16
- Node.js 20.x
- 2GB+ RAM
- 5GB+ disk space
- SSD storage
- Multi-core CPU

## ✅ Features After Installation

### Web Management
- Web domain hosting
- SSL certificate management
- PHP version configuration
- Root path management

### Database Management
- MySQL/PostgreSQL support
- User provisioning
- Database creation/deletion
- Access control

### System Features
- Firewall rules (UFW)
- Service management
- System monitoring
- Log viewing
- Cron jobs
- FTP accounts
- Email accounts
- DNS zones
- File manager
- Backups & restore

### Dashboard
- Real-time metrics
- System statistics
- Quick access links
- Status overview

### Security
- User authentication
- Role-based access
- Permission controls
- Audit logging

## 💡 Tips

- Run installer with `-v` for verbose output
- Check `installer.log` for debugging
- Use `npm run dev` during development
- Run `php artisan test` regularly
- Monitor `storage/logs/laravel.log` for errors

## 🤝 Support

- **Documentation**: Check included .md files
- **Logs**: `installer.log`, `storage/logs/laravel.log`
- **Tests**: `php artisan test`
- **Status**: `IMPLEMENTATION_STATUS.md`

## 📌 Version Information

- **SuperCP**: v1.0 (Production Ready)
- **Laravel**: 12.44.0
- **PHP**: 8.4.16
- **React**: 19.0
- **Node.js**: 20.x
- **Rust**: Latest (stable)

## 🎉 Installation Complete!

Your SuperCP control panel is ready for:
- Development ✓
- Testing ✓
- Production ✓

Start with:
```bash
./install.sh
```

**Questions?** Check [INSTALLER.md](INSTALLER.md) for detailed documentation.

---

**SuperCP Installation Suite - Production Ready! 🚀**
