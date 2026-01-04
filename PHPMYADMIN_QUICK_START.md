# phpMyAdmin Quick Start Guide

## Installation & Setup (3 Steps)

### Step 1: Run Installation Script
```bash
cd /home/super/getsupercp
chmod +x install-phpmyadmin.sh
sudo bash install-phpmyadmin.sh
```

The script will:
- ✅ Download phpMyAdmin 5.2.1
- ✅ Extract to `/home/super/phpmyadmin`
- ✅ Create secure configuration
- ✅ Set proper permissions
- ✅ Update `.env` file
- ✅ Configure web server (Apache/Nginx)

### Step 2: Verify Installation
```bash
# Check files
ls -la /home/super/phpmyadmin/config.inc.php

# Check Laravel routes
php artisan route:list | grep phpmyadmin

# Test config access
php artisan tinker
>>> config('phpmyadmin.enabled')
```

### Step 3: Enable in Environment
The installation script automatically adds to `.env`:
```env
PHPMYADMIN_ENABLED=true
PHPMYADMIN_PATH=/home/super/phpmyadmin
PHPMYADMIN_URL=/phpmyadmin
```

## Access Methods

### 1. Via GetSuperCP Dashboard
```
1. Login as admin user
2. Go to Admin > Database Manager
3. Or direct URL: /admin/database/manager
```

### 2. Via API Endpoints
```bash
# Check installation status
curl http://localhost/api/phpmyadmin/check

# Get database status
curl http://localhost/api/phpmyadmin/status

# List databases
curl http://localhost/api/phpmyadmin/databases

# Get database info
curl http://localhost/api/phpmyadmin/database/mysql

# Execute SELECT query
curl -X POST http://localhost/api/phpmyadmin/query \
  -H "Content-Type: application/json" \
  -d '{"query":"SELECT VERSION()"}'
```

## Common Tasks

### Create Database User
```sql
-- Via phpMyAdmin or MySQL:
CREATE USER 'username'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

### Import Database
1. Open phpMyAdmin
2. Select database or create new one
3. Click "Import" tab
4. Upload SQL file
5. Click Go

### Export Database
1. Select database
2. Click "Export" tab
3. Choose SQL format
4. Download file

### Create Table
1. Select database
2. Click "Create new table"
3. Define columns
4. Click Create

## Security Features

✅ **Admin-Only Access** - Requires `is_admin = true`
✅ **Session Authentication** - Uses Laravel sessions
✅ **Access Logging** - All access logged with user/IP
✅ **IP Whitelisting** - Optional via `PHPMYADMIN_ALLOWED_IPS`
✅ **Operation Restrictions** - Blocks DROP, TRUNCATE, DELETE
✅ **Query Restrictions** - Only SELECT via API
✅ **Security Headers** - Prevents clickjacking and XSS
✅ **HTTPS Ready** - Set `ForceSSL=true` for production

## Configuration

### Enable/Disable
```env
PHPMYADMIN_ENABLED=true   # or false to disable
```

### Restrict to IPs
```env
PHPMYADMIN_ALLOWED_IPS=192.168.1.100,10.0.0.1
```

### Custom Session Timeout
Edit `config/phpmyadmin.php`:
```php
'security' => [
    'session_timeout' => 60,  // minutes
],
```

### Change URL Prefix
```env
PHPMYADMIN_URL=/db-admin   # instead of /phpmyadmin
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| 404 Not Found | Verify `/home/super/phpmyadmin` exists and `PHPMYADMIN_ENABLED=true` |
| 403 Forbidden | Ensure user has `is_admin = true` in database |
| Can't connect DB | Check MySQL/MariaDB is running and credentials are correct |
| Permission denied | Run: `sudo chown -R phpmyadmin:www-data /home/super/phpmyadmin` |
| Session timeout | Edit `config/phpmyadmin.php` and increase `session_timeout` |
| Memory errors | Increase in `config/phpmyadmin.php`: `memory_limit` |

## File Locations

```
/home/super/getsupercp/
├── config/phpmyadmin.php              ← Configuration
├── app/Http/Controllers/PhpMyAdminController.php
├── app/Http/Middleware/VerifyPhpMyAdminAccess.php
├── app/Policies/PhpMyAdminPolicy.php
└── install-phpmyadmin.sh              ← Installation script

/home/super/phpmyadmin/                ← phpMyAdmin installation
├── index.php
├── config.inc.php
├── tmp/
└── upload/
```

## Routes Reference

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/admin/database/manager` | Dashboard redirect |
| GET | `/api/phpmyadmin/status` | Database status |
| GET | `/api/phpmyadmin/databases` | List databases |
| GET | `/api/phpmyadmin/database/{name}` | Database details |
| POST | `/api/phpmyadmin/query` | Execute query |
| GET | `/api/phpmyadmin/check` | Check installation |

All routes require authentication and admin privileges.

## Performance Tips

- Use indexes on large tables
- Enable query caching
- Archive old data periodically
- Monitor database size
- Regular backups
- Optimize tables monthly

## Backup & Restore

### Backup Database
```bash
mysqldump -u root -p database_name > backup.sql
```

### Restore Database
```bash
mysql -u root -p database_name < backup.sql
```

### Via phpMyAdmin
1. Select database
2. Export → SQL → Download
3. To restore: Import → Select file → Go

## Next Steps

1. ✅ Run `install-phpmyadmin.sh`
2. ✅ Verify installation (`php artisan route:list | grep phpmyadmin`)
3. ✅ Login to GetSuperCP as admin
4. ✅ Test database access
5. ✅ Create backup users
6. ✅ Configure backups in admin panel

## Support

For issues:
1. Check `storage/logs/laravel.log`
2. Review `PHPMYADMIN_INTEGRATION.md` for detailed guide
3. Verify routes: `php artisan route:list | grep phpmyadmin`
4. Test API: `curl http://localhost/api/phpmyadmin/check`

## Version Info

- **phpMyAdmin**: 5.2.1
- **PHP**: 8.4+
- **Laravel**: 12+
- **Databases**: MySQL 5.7+ / MariaDB 10.3+

---

**Status**: ✅ Ready to Install
**Last Updated**: January 4, 2026
