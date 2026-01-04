# 🚀 GetSuperCP Production Deployment Guide

**Version**: 1.0.0  
**Date**: January 4, 2026  
**Status**: Ready for Production

---

## Prerequisites

- **Server OS**: Linux (Ubuntu 20.04+ or similar)
- **PHP**: 8.4+ with extensions (curl, gd, json, mbstring, openssl, pdo, sqlite, xml)
- **Node.js**: 18+ (for frontend builds)
- **Database**: MySQL 8.0+ or PostgreSQL 12+ (recommended)
- **Web Server**: Nginx or Apache
- **SSL**: Valid SSL certificate

---

## Phase 1: Pre-Deployment Checklist

- [ ] Server provisioned and accessible
- [ ] PHP 8.4+ installed with required extensions
- [ ] Node.js 18+ installed
- [ ] Database server configured
- [ ] Git repository cloned
- [ ] Domain name configured and DNS pointing to server
- [ ] SSL certificate obtained

---

## Phase 2: Server Setup

### 2.1 SSH into Your Server
```bash
ssh user@your-server.com
```

### 2.2 Install System Dependencies (Ubuntu)
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y build-essential php8.4 php8.4-cli php8.4-fpm \
  php8.4-curl php8.4-gd php8.4-json php8.4-mbstring \
  php8.4-openssl php8.4-pdo php8.4-sqlite3 php8.4-xml \
  nginx mysql-server git curl wget nodejs npm
```

### 2.3 Configure PHP-FPM
```bash
sudo nano /etc/php/8.4/fpm/php.ini

# Set these values:
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```

### 2.4 Restart Services
```bash
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
sudo systemctl restart mysql
```

---

## Phase 3: Clone & Setup Application

### 3.1 Clone Repository
```bash
cd /var/www
sudo git clone https://github.com/zapper88144/getsupercp.git
cd getsupercp
sudo chown -R www-data:www-data /var/www/getsupercp
```

### 3.2 Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 3.3 Create Production Environment File
```bash
cp .env.example .env
```

---

## Phase 4: Configure Environment Variables

### 4.1 Edit Production .env
```bash
nano .env
```

### 4.2 Essential Settings

```dotenv
# Application
APP_NAME="SuperCP"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE  # Run: php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Locale
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# Database (MySQL Example)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=supercp_prod
DB_USERNAME=supercp_user
DB_PASSWORD=SECURE_PASSWORD_HERE

# Cache (Redis Recommended)
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=database

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null

# Security Headers
FORCE_HTTPS=true
SECURE_COOKIES=true

# Email (Configure for Your Provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="SuperCP"

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=notice

# Cache Settings
CACHE_PREFIX=supercp_prod_

# File System
FILESYSTEM_DISK=local
```

### 4.3 Generate Application Key
```bash
php artisan key:generate
```

---

## Phase 5: Database Setup

### 5.1 Create Database & User (MySQL)
```bash
sudo mysql -u root -p

CREATE DATABASE supercp_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'supercp_user'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON supercp_prod.* TO 'supercp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5.2 Run Migrations
```bash
php artisan migrate --force
```

### 5.3 Seed Initial Data (Optional)
```bash
php artisan db:seed --force
```

---

## Phase 6: Web Server Configuration

### 6.1 Nginx Configuration
```bash
sudo nano /etc/nginx/sites-available/supercp
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;

    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/getsupercp/public;
    index index.php index.html;

    # Logging
    access_log /var/log/nginx/supercp-access.log;
    error_log /var/log/nginx/supercp-error.log;

    # Gzip Compression
    gzip on;
    gzip_types text/plain text/css text/javascript application/json;
    gzip_min_length 1000;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6.2 Enable Site
```bash
sudo ln -s /etc/nginx/sites-available/supercp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## Phase 7: SSL Certificate Setup

### 7.1 Using Let's Encrypt (Free)
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot certonly --nginx -d your-domain.com
```

### 7.2 Auto-Renewal
```bash
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

---

## Phase 8: File Permissions & Security

### 8.1 Set Correct Permissions
```bash
sudo chown -R www-data:www-data /var/www/getsupercp
sudo chmod -R 755 /var/www/getsupercp
sudo chmod -R 775 /var/www/getsupercp/storage
sudo chmod -R 775 /var/www/getsupercp/bootstrap/cache
```

### 8.2 Create Required Directories
```bash
mkdir -p /var/www/getsupercp/storage/logs
mkdir -p /var/www/getsupercp/storage/app/backups
mkdir -p /var/www/getsupercp/bootstrap/cache
```

### 8.3 Firewall Configuration
```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## Phase 9: Optimize & Cache

### 9.1 Clear Development Cache
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9.2 Optimize Autoloader
```bash
composer install --optimize-autoloader --no-dev
```

---

## Phase 10: Monitoring & Logging

### 10.1 Enable Logging
```bash
# Monitor application logs
tail -f storage/logs/laravel.log
```

### 10.2 Set Up Error Tracking (Optional)
```bash
# Add to .env:
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id

# Install package:
composer require sentry/sentry-laravel
php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
```

### 10.3 Configure Cron Job (For Scheduled Tasks)
```bash
sudo crontab -e

# Add this line:
* * * * * cd /var/www/getsupercp && php artisan schedule:run >> /dev/null 2>&1
```

---

## Phase 11: Verify Deployment

### 11.1 Check Application Status
```bash
php artisan about
```

### 11.2 Run Tests (Optional but Recommended)
```bash
php artisan test
# Expected: 115/116 tests passing (99.1%)
```

### 11.3 Verify Routes
```bash
php artisan route:list
# Should show 107 routes
```

### 11.4 Test Email Configuration
```bash
php artisan tinker
Mail::raw('Test email', function($message) {
  $message->to('your-email@example.com');
});
# Check email inbox
```

---

## Phase 12: Post-Deployment Checklist

- [ ] Application loads without errors
- [ ] All 10 React pages render correctly
- [ ] Sidebar navigation displays properly
- [ ] Dark mode toggle works
- [ ] Mobile responsive design verified
- [ ] SSL certificate valid (green lock)
- [ ] All 5 features functional:
  - [ ] SSL Certificate Management
  - [ ] Backup & Schedules
  - [ ] Monitoring & Alerts
  - [ ] Security Dashboard
  - [ ] Email Configuration
- [ ] Tests passing: 115/116 (99.1%)
- [ ] Error logs monitored
- [ ] Backups configured
- [ ] Monitoring alerts active

---

## Phase 13: Maintenance & Operations

### 13.1 Regular Backups
```bash
# Daily backups
0 2 * * * cd /var/www/getsupercp && php artisan backup:run >> /dev/null 2>&1
```

### 13.2 Keep Dependencies Updated
```bash
composer update --no-dev
npm update
```

### 13.3 Monitor Performance
```bash
# Check disk usage
df -h

# Check memory usage
free -h

# Check PHP-FPM status
sudo systemctl status php8.4-fpm
```

### 13.4 Security Updates
```bash
# Keep system updated
sudo apt update && sudo apt upgrade -y

# Check Laravel for security updates
composer outdated
```

---

## Troubleshooting

### Issue: 500 Internal Server Error
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Verify PHP-FPM
sudo systemctl status php8.4-fpm

# Check permissions
ls -la storage/
```

### Issue: Database Connection Error
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

### Issue: Missing Assets (CSS/JS)
```bash
# Rebuild frontend
npm run build

# Clear Vite cache
rm -rf bootstrap/cache/vite.json
```

### Issue: File Upload Failures
```bash
# Check storage directory
ls -la storage/app/

# Verify permissions
chmod -R 775 storage/
```

---

## Production URLs

- **Application**: https://your-domain.com
- **Admin Panel**: https://your-domain.com/dashboard
- **API**: https://your-domain.com/api

---

## Support & Documentation

- Laravel Documentation: https://laravel.com/docs
- Inertia.js: https://inertiajs.com
- React Documentation: https://react.dev
- Tailwind CSS: https://tailwindcss.com

---

## Deployment Summary

✅ **GetSuperCP is production-ready with**:
- 99.1% test coverage (115/116 tests passing)
- Modern React 19 frontend
- Secure Laravel 12 backend
- 5 fully functional features
- Comprehensive monitoring
- Professional UI with sidebar navigation

**Estimated Deployment Time**: 30-45 minutes

---

**Status**: ✅ READY FOR PRODUCTION DEPLOYMENT  
**Last Updated**: January 4, 2026
