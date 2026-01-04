# phpMyAdmin Integration Guide for GetSuperCP

## Overview

This guide explains how to integrate and use phpMyAdmin with GetSuperCP for local database management without Docker.

## Installation

### Prerequisites

- PHP 7.4+ (PHP 8.4+ recommended)
- Apache or Nginx web server
- MySQL/MariaDB server
- Root or sudo access
- GetSuperCP application installed

### Quick Installation

Run the automated installation script:

```bash
sudo bash install-phpmyadmin.sh
```

The script will:
1. Check dependencies
2. Create phpMyAdmin directory
3. Download phpMyAdmin 5.2.1
4. Extract and configure files
5. Set proper permissions
6. Create security configurations
7. Integrate with GetSuperCP
8. Update environment variables

### Manual Installation

If you prefer manual installation:

```bash
# 1. Create directory
sudo mkdir -p /var/www/phpmyadmin
cd /var/www/phpmyadmin

# 2. Download phpMyAdmin
wget https://files.phpmyadmin.net/phpMyAdmin/5.2.1/phpMyAdmin-5.2.1-all-languages.tar.gz

# 3. Extract
tar -xzf phpMyAdmin-5.2.1-all-languages.tar.gz
mv phpMyAdmin-5.2.1-all-languages/* .
rm -rf phpMyAdmin-5.2.1-all-languages*

# 4. Configure
cp config.sample.inc.php config.inc.php
# Edit config.inc.php with your MySQL credentials

# 5. Set permissions
sudo chown -R www-data:www-data /var/www/phpmyadmin
sudo chmod -R 755 /var/www/phpmyadmin
sudo chmod 700 /var/www/phpmyadmin/tmp
```

## Configuration

### GetSuperCP Environment

Add to `.env`:

```env
# phpMyAdmin Configuration
PHPMYADMIN_ENABLED=true
PHPMYADMIN_PATH=/var/www/phpmyadmin
PHPMYADMIN_URL=/phpmyadmin
PHPMYADMIN_ALLOWED_IPS=127.0.0.1,::1
DB_ADMIN_USER=root
DB_ADMIN_PASSWORD=your_password
```

### MySQL/MariaDB Configuration

Edit `/var/www/phpmyadmin/config.inc.php`:

```php
<?php
/**
 * phpMyAdmin configuration file.
 */

// Authentication
$cfg['Servers'][1]['host'] = 'localhost';
$cfg['Servers'][1]['port'] = '3306';
$cfg['Servers'][1]['user'] = 'root';           // DB user
$cfg['Servers'][1]['password'] = 'password';   // DB password
$cfg['Servers'][1]['auth_type'] = 'cookie';
$cfg['Servers'][1]['compress'] = false;

// Security
$cfg['LoginCookieValidity'] = 1440;      // 24 hours
$cfg['Servers'][1]['AllowNoPassword'] = false;
$cfg['AllowArbitraryServer'] = false;    // No server selector

// Appearance
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['DefaultTabServer'] = 'databases';
$cfg['MaxRows'] = 25;
$cfg['Order'] = 'ASCENDING';

// Generate blowfish secret
$cfg['blowfish_secret'] = 'your-random-32-char-string-here';
```

### Web Server Configuration

#### Apache

Create `/etc/apache2/conf-available/phpmyadmin.conf`:

```apache
<Directory /var/www/phpmyadmin>
    Options FollowSymLinks
    AllowOverride All
    
    # Require authentication
    <RequireAll>
        Require local
    </RequireAll>
    
    # PHP configuration
    <IfModule mod_php.c>
        php_flag display_errors Off
        php_value upload_max_filesize 32M
        php_value post_max_size 32M
        php_value memory_limit 512M
    </IfModule>
</Directory>

Alias /phpmyadmin /var/www/phpmyadmin
```

Enable the configuration:

```bash
sudo a2enconf phpmyadmin
sudo a2enmod rewrite
sudo systemctl reload apache2
```

#### Nginx

Create `/etc/nginx/conf.d/phpmyadmin.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    
    server_name your-domain.com;
    
    root /var/www;
    index index.php;
    
    location /phpmyadmin {
        root /var/www;
        try_files $uri $uri/ /phpmyadmin/index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
    }
}
```

Reload Nginx:

```bash
sudo systemctl reload nginx
```

## Security

### Access Control

phpMyAdmin is protected by multiple layers:

1. **Laravel Authentication**: Requires GetSuperCP login
2. **Admin-Only Access**: Only users with `is_admin` flag can access
3. **.htaccess Rules**: Prevents direct access to sensitive files
4. **IP Whitelisting**: Can restrict by IP address in `.env`

### Best Practices

1. **Change Default Credentials**
   - Use strong database passwords
   - Update `config.inc.php` with secure credentials

2. **Enable HTTPS**
   ```env
   APP_URL=https://your-domain.com
   FORCE_HTTPS=true
   ```

3. **Restrict Access by IP**
   ```env
   PHPMYADMIN_ALLOWED_IPS=192.168.1.100,192.168.1.101
   ```

4. **Use SSH Tunneling**
   ```bash
   ssh -L 3306:localhost:3306 user@remote-server
   ```

5. **Disable Root Login (Optional)**
   - Create dedicated database users
   - Don't use root account for applications

6. **Enable Logging**
   ```php
   // In config.inc.php
   $cfg['LogFileIP'] = true;
   $cfg['QueryHistoryDB'] = true;
   $cfg['QueryHistoryMax'] = 100;
   ```

## Access Methods

### Via GetSuperCP Admin Panel

1. Log in to GetSuperCP
2. Navigate to **Admin Dashboard**
3. Click **Database Manager**
4. You'll be redirected to phpMyAdmin

Route: `/admin/database/manager`

### Direct Access

If you have the URL:

```
https://your-domain.com/phpmyadmin/
```

Requires:
- GetSuperCP authentication
- Admin privileges

### Command Line

```bash
# Access MySQL directly
mysql -h localhost -u root -p

# Test connection
mysql -h localhost -u root -p -e "SELECT VERSION();"
```

## Database Management Tasks

### Create a New Database

1. Log in to phpMyAdmin
2. Click **Databases** tab
3. Enter database name
4. Click **Create**

### Create a Database User

```sql
CREATE USER 'appuser'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON appname.* TO 'appuser'@'localhost';
FLUSH PRIVILEGES;
```

Or through phpMyAdmin:

1. Go to **User accounts**
2. Click **Add user**
3. Set username, host, password
4. Assign privileges

### Import Data

1. Select database
2. Go to **Import** tab
3. Upload SQL file or paste SQL
4. Click **Import**

### Export Data

1. Select database or table
2. Go to **Export** tab
3. Choose format (SQL, CSV, JSON, etc.)
4. Click **Go**

### Run Queries

1. Go to **SQL** tab
2. Enter your SQL query
3. Click **Go**

Example:

```sql
SELECT * FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY);
UPDATE products SET price = price * 1.1 WHERE category = 'electronics';
```

## Troubleshooting

### Connection Refused

```
Error: Connection refused
```

**Solution**: Verify MySQL is running

```bash
# Check MySQL status
sudo systemctl status mysql
# or
sudo systemctl status mariadb

# Start MySQL if stopped
sudo systemctl start mysql
```

### Permission Denied

```
Error: Permission denied for user 'root'@'localhost'
```

**Solution**: Check credentials in `config.inc.php`

```bash
# Verify MySQL user
mysql -h localhost -u root -p -e "SELECT user, host FROM mysql.user;"

# Test connection
mysql -h localhost -u root -p
```

### Directory Not Found

```
Error: /var/www/phpmyadmin not found
```

**Solution**: Run installation script again

```bash
sudo bash install-phpmyadmin.sh
```

### Blowfish Secret Error

```
Error: Blowfish secret not configured
```

**Solution**: Generate and set in `config.inc.php`

```bash
# Generate random secret
openssl rand -base64 32

# Edit config
sudo nano /var/www/phpmyadmin/config.inc.php
# Find: $cfg['blowfish_secret'] = '';
# Replace with generated value
```

### Large File Upload

If you need to import large files:

```php
// Edit php.ini
upload_max_filesize = 256M
post_max_size = 256M
memory_limit = 512M
```

Restart web server:

```bash
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
```

## Artisan Commands

### Verify Installation

```bash
php artisan phpmyadmin:verify
```

Output:
```
phpMyAdmin Installation Status
==============================
Enabled: Yes
Path: /var/www/phpmyadmin
Web URL: /phpmyadmin/
Database Connection: OK
Configuration: Valid
```

### Check Database

```bash
php artisan phpmyadmin:check-db
```

### Generate Config

```bash
php artisan phpmyadmin:generate-config
```

## Performance Optimization

### Enable Query Caching

In `/var/www/phpmyadmin/config.inc.php`:

```php
// Query execution time tracking
$cfg['DBG']['sql'] = true;

// Export options
$cfg['Export']['sql_max_query_size'] = 50000;
$cfg['Export']['sql_compatibility'] = 'NONE';
```

### Optimize Large Databases

```sql
-- Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'database_name'
ORDER BY size_mb DESC;

-- Optimize table
OPTIMIZE TABLE table_name;

-- Analyze table
ANALYZE TABLE table_name;

-- Repair table
REPAIR TABLE table_name;
```

### Enable Compression

```php
// In config.inc.php
$cfg['Servers'][1]['compress'] = true;
```

## Backup & Restore

### Backup Database

Via phpMyAdmin:

1. Select database
2. Go to **Export**
3. Choose format (SQL recommended)
4. Click **Go**

Via Command Line:

```bash
mysqldump -u root -p database_name > backup.sql
```

### Restore Database

Via phpMyAdmin:

1. Create new database
2. Select database
3. Go to **Import**
4. Upload backup file
5. Click **Import**

Via Command Line:

```bash
mysql -u root -p database_name < backup.sql
```

## Integration with GetSuperCP

### Create Database Controller

```php
// app/Http/Controllers/DatabaseInfoController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DatabaseInfoController extends Controller
{
    public function index()
    {
        // Check authorization
        $this->authorize('isAdmin');
        
        $databases = DB::select('SHOW DATABASES');
        $variables = DB::select('SHOW VARIABLES');
        
        return Inertia::render('Admin/DatabaseManager', [
            'databases' => $databases,
            'variables' => $variables,
        ]);
    }
}
```

### Create Menu Item

In your admin navigation:

```jsx
<NavLink href={route('database.manager')}>
    Database Manager
</NavLink>
```

## Monitoring & Logging

### View phpMyAdmin Logs

```bash
# Apache access logs
tail -f /var/log/apache2/access.log | grep phpmyadmin

# Web server error logs
tail -f /var/log/apache2/error.log
```

### Enable Query Logging

In MySQL:

```sql
-- Enable general query log
SET GLOBAL general_log = 'ON';

-- View log file location
SHOW VARIABLES LIKE 'general_log_file';

-- Disable when done
SET GLOBAL general_log = 'OFF';
```

## Advanced Configuration

### Multi-Server Setup

```php
$cfg['Servers'][1] = array(
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'password',
    'AllowNoPassword' => false,
);

$cfg['Servers'][2] = array(
    'host' => '192.168.1.100',
    'user' => 'remote_user',
    'password' => 'remote_password',
);
```

### Theme Customization

Available themes:
- pmahomme (default)
- bootstrap (dark theme)
- original

```php
$cfg['ThemeDefault'] = 'bootstrap';
```

### Feature Configuration

```php
// Disable export
$cfg['Export']['format'] = false;

// Disable operations
$cfg['AllowUserDropDatabase'] = false;

// Disable create table
$cfg['AllowUserCreateDatabase'] = false;
```

## Updates & Maintenance

### Check for Updates

Visit: https://www.phpmyadmin.net/

### Update phpMyAdmin

```bash
cd /var/www/phpmyadmin

# Backup current version
sudo cp -r . ../phpmyadmin-backup

# Download new version
wget https://files.phpmyadmin.net/phpMyAdmin/5.2.1/phpMyAdmin-5.2.1-all-languages.tar.gz

# Extract and replace
tar -xzf phpMyAdmin-5.2.1-all-languages.tar.gz
# Copy new files, keeping config.inc.php
```

## Support & Resources

- **Official Site**: https://www.phpmyadmin.net/
- **Documentation**: https://docs.phpmyadmin.net/
- **Issues**: https://github.com/phpmyadmin/phpmyadmin
- **Forum**: https://www.phpmyadmin.net/support/

## FAQ

**Q: Can I use phpMyAdmin with SQLite?**
A: phpMyAdmin is designed for MySQL/MariaDB. For SQLite, use Laravel Tinker or database clients like SQLiteStudio.

**Q: Is it safe to expose phpMyAdmin?**
A: Not recommended. Use authentication, HTTPS, and IP whitelisting. Or use a private network/VPN.

**Q: What's the difference between phpMyAdmin versions?**
A: Version 5.2+ requires PHP 7.4+, has better security, and modern UI.

**Q: Can I disable certain phpMyAdmin features?**
A: Yes, via `config.inc.php`. See Advanced Configuration section.

**Q: How do I reset phpMyAdmin password?**
A: Change the MySQL password and update `config.inc.php`.

## Conclusion

phpMyAdmin provides a web interface for database management. Combined with GetSuperCP's authentication layer, it offers a secure way to manage your MySQL/MariaDB databases locally.

For production environments, consider additional security measures like:
- VPN access only
- Custom authentication plugins
- Advanced logging and monitoring
- Regular backups
- Database replication

---

**Last Updated**: January 4, 2026
**Version**: 1.0
**GetSuperCP Integration**: Complete
