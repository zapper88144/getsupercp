# GetSuperCP Administrator Guide

## Table of Contents

1. [Installation](#installation)
2. [Initial Setup](#initial-setup)
3. [User Management](#user-management)
4. [System Monitoring](#system-monitoring)
5. [Backup & Recovery](#backup--recovery)
6. [Security Management](#security-management)
7. [Performance Tuning](#performance-tuning)
8. [Troubleshooting](#troubleshooting)
9. [API Management](#api-management)

---

## Installation

### System Requirements

- **OS**: Ubuntu 20.04+ or CentOS 8+
- **PHP**: 8.4+
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Server**: Nginx or Apache
- **Memory**: 2GB minimum (4GB+ recommended)
- **Disk**: 20GB minimum for installation and logs

### Pre-Installation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y \
  php8.4 php8.4-fpm php8.4-mysql php8.4-pgsql php8.4-curl \
  php8.4-gd php8.4-xml php8.4-zip php8.4-mbstring php8.4-bcmath \
  composer npm nginx mysql-server git curl
```

### Database Setup

```bash
# Create database and user
mysql -u root -p << EOF
CREATE DATABASE getsuper_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'getsuper'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON getsuper_production.* TO 'getsuper'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Application Installation

```bash
# Clone repository
cd /var/www
git clone https://github.com/yourusername/getsupercp.git
cd getsupercp

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --force

# Set permissions
chmod -R 775 storage bootstrap/cache
```

---

## Initial Setup

### 1. Create Admin User

```bash
php artisan tinker

# In Tinker:
$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('secure_password'),
    'is_admin' => true,
    'email_verified_at' => now(),
]);
exit();
```

### 2. Configure Email

Edit `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="GetSuperCP"
```

### 3. Configure Application

Edit `.env`:
```env
APP_NAME="GetSuperCP"
APP_URL=https://admin.example.com
APP_TIMEZONE=UTC
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=getsuper_production
DB_USERNAME=getsuper
DB_PASSWORD=secure_password_here
```

### 4. Set Up SSL Certificate

```bash
# Using Let's Encrypt with Certbot
sudo certbot certonly --standalone -d admin.example.com

# Nginx will use: /etc/letsencrypt/live/admin.example.com/
```

### 5. Configure Web Server

**Nginx Configuration** (/etc/nginx/sites-available/getsuper):

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name admin.example.com;

    ssl_certificate /etc/letsencrypt/live/admin.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.example.com/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Root directory
    root /var/www/getsupercp/public;
    index index.php index.html index.htm;

    # Logging
    access_log /var/log/nginx/getsuper_access.log combined;
    error_log /var/log/nginx/getsuper_error.log;

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name admin.example.com;
    return 301 https://$server_name$request_uri;
}
```

Enable the site:
```bash
sudo ln -s /etc/nginx/sites-available/getsuper /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## User Management

### Add New User

```bash
php artisan tinker

$user = App\Models\User::create([
    'name' => 'New User',
    'email' => 'user@example.com',
    'password' => bcrypt('temporary_password'),
    'email_verified_at' => now(),
]);
exit();
```

### Reset User Password

```bash
php artisan tinker

$user = App\Models\User::where('email', 'user@example.com')->first();
$user->update(['password' => bcrypt('new_password')]);
exit();
```

### Disable User Account

```bash
php artisan tinker

$user = App\Models\User::where('email', 'user@example.com')->first();
$user->update(['status' => 'disabled']);
exit();
```

### View User Activity

```sql
SELECT * FROM audit_logs 
WHERE user_id = 'user_uuid' 
ORDER BY created_at DESC 
LIMIT 100;
```

---

## System Monitoring

### Health Check Script

Run the health check script regularly:

```bash
# Run immediately
./health-check.sh

# Run via cron (every 5 minutes)
*/5 * * * * /var/www/getsupercp/health-check.sh >> /var/log/getsupercp-health.log 2>&1
```

### Monitor Key Metrics

```bash
# View real-time metrics
tail -f storage/logs/health-check.log

# Check application status
php artisan tinker << 'EOF'
echo "Database: " . (\DB::connection()->getPdo() ? "✓ Connected" : "✗ Failed") . "\n";
echo "Cache: " . (\Cache::put('test', 'ok', 60) ? "✓ Working" : "✗ Failed") . "\n";
echo "Queue: " . (\DB::table('failed_jobs')->count()) . " failed jobs\n";
exit();
EOF
```

### Set Up Monitoring Dashboard

Access the built-in monitoring dashboard:

1. Log in to GetSuperCP admin panel
2. Navigate to "Monitoring"
3. Configure alert thresholds:
   - CPU Usage: 80%
   - Memory Usage: 85%
   - Disk Usage: 90%
   - Database Size: Custom threshold

---

## Backup & Recovery

### Automated Backups

Set up automated backups via cron:

```bash
# Daily backup at 2 AM
0 2 * * * /var/www/getsupercp/deploy.sh production backup >> /var/log/getsupercp-backup.log 2>&1

# Weekly full backup
0 3 * * 0 mysqldump -u getsuper -p'password' getsuper_production | gzip > /backups/full-$(date +\%Y\%m\%d).sql.gz
```

### Manual Backup

```bash
# Backup database
mysqldump -u getsuper -p getsuper_production > /backups/manual-backup-$(date +%Y%m%d).sql

# Backup application files
tar -czf /backups/app-backup-$(date +%Y%m%d).tar.gz /var/www/getsupercp
```

### Recovery

```bash
# Restore database
mysql -u getsuper -p getsuper_production < /backups/backup-file.sql

# Verify restore
php artisan tinker
\DB::table('users')->count();
exit();
```

---

## Security Management

### Update Security Headers

Edit `app/Http/Middleware/SecurityHeaders.php`:

```php
// Add/modify headers as needed
$response->header('X-Custom-Header', 'value');
```

### Manage API Keys

```bash
# Generate new API key for user
php artisan tinker
$user = App\Models\User::where('email', 'user@example.com')->first();
$token = $user->createToken('api-key')->plainTextToken;
echo "New API Key: $token";
exit();
```

### Review Audit Logs

```bash
# Recent changes
php artisan tinker
App\Models\AuditLog::latest()->limit(50)->get()->each(fn($log) => 
    echo $log->user->email . " " . $log->action . " " . $log->created_at . "\n"
);
exit();

# Failed login attempts
SELECT * FROM audit_logs 
WHERE action = 'login_failed' 
AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Security Hardening Checklist

- [ ] Change all default passwords
- [ ] Enable 2FA for all admin accounts
- [ ] Configure firewall rules
- [ ] Set up SSL certificates (HTTPS only)
- [ ] Disable debug mode in production
- [ ] Configure rate limiting
- [ ] Set up log rotation
- [ ] Configure backup retention
- [ ] Review and update permissions
- [ ] Set up intrusion detection

---

## Performance Tuning

### PHP-FPM Configuration

Edit `/etc/php/8.4/fpm/pool.d/www.conf`:

```ini
; Process pool
pm = dynamic
pm.max_children = 50
pm.start_servers = 20
pm.min_spare_servers = 10
pm.max_spare_servers = 30
pm.max_requests = 500
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.4-fpm
```

### Database Optimization

```sql
-- Analyze tables
ANALYZE TABLE users, web_domains, ssl_certificates, backups;

-- Optimize tables
OPTIMIZE TABLE users, web_domains, ssl_certificates, backups;

-- Check indexes
SHOW INDEX FROM web_domains;
```

### Cache Configuration

Edit `.env`:
```env
CACHE_DRIVER=redis
CACHE_TTL=3600
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Enable OPcache

Edit `/etc/php/8.4/fpm/conf.d/10-opcache.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=60
```

### Query Optimization

```bash
# Enable query logging
php artisan tinker
\DB::listen(function ($query) {
    echo $query->sql . " - " . $query->time . "ms\n";
});
exit();
```

---

## Troubleshooting

### Application Won't Start

```bash
# Check logs
tail -100 storage/logs/laravel.log

# Verify permissions
ls -la storage/logs/
ls -la bootstrap/cache/

# Test artisan
php artisan tinker
```

### Database Connection Issues

```bash
# Test connection
mysql -u getsuper -p -h localhost -e "SELECT 1"

# Check PHP MySQL extension
php -m | grep mysql

# Verify .env
grep DB_ .env
```

### High Memory Usage

```bash
# Check PHP-FPM processes
ps aux | grep php-fpm | head -5

# Monitor in real-time
watch -n 1 'ps aux | grep php-fpm | wc -l'

# Adjust pool configuration (see Performance Tuning)
```

### Slow API Requests

```bash
# Enable query logging temporarily
php artisan tinker
\DB::listen(function ($query) {
    if ($query->time > 1000) {
        error_log("Slow query: " . $query->sql . " ({$query->time}ms)");
    }
});
exit();

# Check logs
tail -f storage/logs/laravel.log | grep Slow
```

### Email Not Sending

```bash
# Test mail configuration
php artisan tinker
\Mail::raw('Test email', function ($message) {
    $message->to('admin@example.com');
});
exit();

# Check logs
grep -i mail storage/logs/laravel.log | tail -20
```

---

## API Management

### Generate API Token

```bash
php artisan tinker
$user = App\Models\User::where('email', 'user@example.com')->first();
$token = $user->createToken('API Token')->plainTextToken;
echo "Token: $token";
exit();
```

### Revoke API Token

```bash
php artisan tinker
$user = App\Models\User::where('email', 'user@example.com')->first();
$user->tokens()->delete();  // Revoke all tokens
exit();
```

### Monitor API Usage

```bash
# Check API requests in logs
tail -f storage/logs/laravel.log | grep "api"

# View rate limit hits
grep "429" storage/logs/laravel.log | wc -l
```

### Configure API Rate Limits

Edit `app/Http/Middleware/RateLimiting.php` to adjust limits.

---

## Support & Resources

- **Documentation**: See `/docs` directory
- **Logs**: `/var/www/getsupercp/storage/logs/`
- **Database**: `getsuper_production`
- **API Docs**: See `API_DOCUMENTATION.md`
- **Email Support**: support@example.com

Last updated: January 4, 2026
