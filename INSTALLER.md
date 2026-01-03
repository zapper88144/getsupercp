# SuperCP Installer Guide

## Quick Start

### Full Automated Installation

```bash
cd /home/super/getsupercp
chmod +x install.sh
./install.sh
```

The installer will automatically:
- ✅ Check system dependencies (PHP, Node.js, npm, Composer)
- ✅ Setup `.env` configuration file
- ✅ Create required directories and sockets
- ✅ Install PHP dependencies (Composer)
- ✅ Install Node.js dependencies (npm)
- ✅ Setup SQLite database with migrations
- ✅ Build frontend assets (npm run build)
- ✅ Compile Rust daemon (if available)
- ✅ Optimize Laravel configuration
- ✅ Fix file permissions
- ✅ Run test suite
- ✅ Display next steps

## What the Installer Does

### 1. Dependency Checking
Verifies all required tools are installed:
- PHP 8.4+
- Composer
- Node.js & npm
- Git (optional)
- Rust/Cargo (optional, for daemon rebuilds)

### 2. Environment Configuration
- Creates/updates `.env` file
- Auto-generates APP_KEY if needed
- Configures database connection
- Sets up cache and session drivers

### 3. Directory Structure
Creates all required directories:
- `storage/app` - Application storage
- `storage/logs` - Application logs
- `storage/framework/sockets` - Daemon socket
- `bootstrap/cache` - Laravel cache

### 4. PHP Setup
- Installs Composer dependencies
- Runs database migrations
- Creates Laravel cache

### 5. Frontend Build
- Installs npm packages
- Builds production assets
- Optimizes JavaScript/CSS

### 6. Rust Daemon
- Compiles with `cargo build --release`
- Creates `target/release/super-daemon` binary
- Ready for background service

### 7. Optimization
- Caches Laravel configuration
- Optimizes Composer autoloader
- Clears old cache files

### 8. Testing
- Runs PHPUnit test suite
- Verifies 72 tests pass
- Optional (prompted during install)

## Environment Variables

The installer creates `.env` with:
```
APP_NAME=SuperCP
APP_ENV=production
APP_KEY=base64:... (auto-generated)
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
CACHE_DRIVER=file
SESSION_DRIVER=cookie
QUEUE_CONNECTION=database
DAEMON_SOCKET=/var/run/super-daemon.sock
```

**Edit these values** in `.env` after installation for your environment.

## After Installation

### Start Development Environment
```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Watch frontend files
npm run dev

# Terminal 3: Start Rust daemon
./rust/target/release/super-daemon
```

### Start Production Environment
```bash
# Build once
npm run build

# Start in production mode
php artisan serve --env=production
```

### Access the Application
```
http://localhost:8000
```

### Default Authentication
- Register new user or
- Check Laravel Breeze setup for demo credentials

## Troubleshooting

### "php: command not found"
```bash
# Install PHP
sudo apt-get install php8.4 php8.4-cli php8.4-fpm
```

### "node: command not found"
```bash
# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### "composer: command not found"
```bash
# Install Composer
sudo apt-get install composer
# or
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Database locked error
```bash
# Fix SQLite permissions
chmod 664 database/database.sqlite
chmod 755 database/
```

### Build fails
```bash
# Clear build cache and rebuild
rm -rf public/build
npm run build
```

### Daemon socket issues
```bash
# Check socket directory exists
ls -la storage/framework/sockets/

# Create if missing
mkdir -p storage/framework/sockets
chmod 755 storage/framework/sockets
```

## Manual Installation (if needed)

If the automated installer doesn't work:

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Database
mkdir -p database
touch database/database.sqlite
php artisan migrate --force

# 4. Build frontend
npm run build

# 5. Build Rust daemon
cd rust
cargo build --release
cd ..

# 6. Optimize
php artisan config:cache
composer dump-autoload --optimize
```

## Installation Log

The installer logs all output to `installer.log`:
```bash
cat installer.log
```

This is useful for debugging installation issues.

## Support

For issues:
1. Check `installer.log` for detailed output
2. Review `.env` configuration
3. Run `php artisan test` to verify setup
4. Check `storage/logs/laravel.log` for errors

## Next Steps

1. ✅ Installation complete
2. 📝 Review and customize `.env`
3. 🚀 Start development servers
4. 🔐 Set up authentication
5. 🛠️ Configure your control panel settings
6. 📊 Access dashboard at `http://localhost:8000`

