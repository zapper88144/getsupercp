# phpMyAdmin Access Guide

## Installation Status: ✅ COMPLETE

phpMyAdmin has been successfully installed and integrated with GetSuperCP. All components are verified and ready to use.

---

## 🚀 Quick Access

### Web Dashboard (UI)
- **URL**: `http://localhost/admin/database/manager`
- **Username**: `test@example.com`
- **Password**: Your GetSuperCP login password
- **Required Role**: Admin (already configured)

### API Endpoints

#### Check Installation Status
```bash
curl -X GET http://localhost/api/phpmyadmin/check \
  -H "Content-Type: application/json"
```

#### Get Database Statistics
```bash
curl -X GET http://localhost/api/phpmyadmin/status \
  -H "Content-Type: application/json"
```

#### List All Databases
```bash
curl -X GET http://localhost/api/phpmyadmin/databases \
  -H "Content-Type: application/json"
```

#### Get Database Details (Tables)
```bash
curl -X GET http://localhost/api/phpmyadmin/database/laravel \
  -H "Content-Type: application/json"
```

#### Execute SELECT Query
```bash
curl -X POST http://localhost/api/phpmyadmin/query \
  -H "Content-Type: application/json" \
  -d '{
    "database": "laravel",
    "query": "SELECT * FROM users LIMIT 10"
  }'
```

---

## 📁 File Locations

| Component | Location |
|-----------|----------|
| phpMyAdmin Installation | `/home/super/phpmyadmin` |
| Configuration File | `/home/super/phpmyadmin/config.inc.php` |
| Laravel Config | `config/phpmyadmin.php` |
| Controller | `app/Http/Controllers/PhpMyAdminController.php` |
| Authorization Policy | `app/Policies/PhpMyAdminPolicy.php` |
| Security Middleware | `app/Http/Middleware/VerifyPhpMyAdminAccess.php` |
| Routes | `routes/web.php` (lines with phpMyAdmin routes) |
| Environment Config | `.env` (PHPMYADMIN_* variables) |

---

## 🔐 Security Features

✅ **Admin-Only Access**: Only users with `is_admin = true` can access phpMyAdmin
✅ **Session Authentication**: Requires valid GetSuperCP login session
✅ **IP Whitelisting**: Configurable allowed IP addresses (default: localhost only)
✅ **Access Logging**: All access attempts are logged with user email, IP, and timestamp
✅ **Query Restrictions**: DROP, TRUNCATE, DELETE operations are blocked via API
✅ **Security Headers**: HTTPS-ready with security headers (X-Frame-Options, X-XSS-Protection)
✅ **Middleware Protection**: All routes protected by VerifyPhpMyAdminAccess middleware

---

## ⚙️ Configuration

### Environment Variables (in `.env`)

```env
# Enable/disable phpMyAdmin feature
PHPMYADMIN_ENABLED=true

# Installation path
PHPMYADMIN_PATH=/home/super/phpmyadmin

# URL prefix (if behind a reverse proxy, adjust accordingly)
PHPMYADMIN_URL=/phpmyadmin

# Allowed IP addresses (comma-separated)
PHPMYADMIN_ALLOWED_IPS=127.0.0.1,::1
```

### Laravel Configuration (in `config/phpmyadmin.php`)

```php
// Database connection settings
'database' => [
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 3306),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
],

// Security settings
'security' => [
    'require_admin' => true,
    'log_access' => true,
    'session_timeout' => 60, // minutes
    'force_https' => false, // enable in production
],
```

---

## 📊 Database Tasks

### Create a New Database User

1. Access phpMyAdmin at `/admin/database/manager`
2. Click "User accounts" in the top menu
3. Click "Add user account"
4. Fill in:
   - **Login name**: e.g., `app_user`
   - **Host name**: `localhost` or `%` (for any host)
   - **Password**: Use the "Generate" button for strong password
   - **Re-type password**: Confirm the password
5. Check the databases to grant privileges
6. Click "Go"

### Backup Database

1. Click on the database name in the left sidebar
2. Click the "Export" tab
3. Choose format (SQL is recommended for MySQL/MariaDB)
4. Click "Go"

### Import Database

1. Click on the database name in the left sidebar
2. Click the "Import" tab
3. Choose your SQL file
4. Click "Go"

### Create Table

1. Select the database
2. Enter table name and number of columns
3. Click "Create"
4. Configure columns (name, type, length, etc.)
5. Click "Save"

### Run Queries

1. Click the "SQL" tab
2. Enter your SELECT query
3. Click "Go"

> **Note**: Only SELECT queries are allowed via the API. Use phpMyAdmin UI for other operations.

---

## 🔍 Access Logs

### View Recent Access

All access attempts are logged to the Laravel log file at `storage/logs/laravel-*.log`.

To view recent phpmyadmin access:
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i phpmyadmin
```

### Sample Log Entry

```
[2024-01-04 15:23:45] local.DEBUG: PhpMyAdmin accessed by user test@example.com from 127.0.0.1 to admin/database/manager
```

---

## 🛠️ Troubleshooting

### Issue: "Access Denied - Admin Only"

**Solution**: Ensure your user account has admin privileges.

```bash
# Check if user is admin
php artisan tinker
$user = \App\Models\User::where('email', 'your@email.com')->first();
echo $user->is_admin ? 'Admin: Yes' : 'Admin: No';
exit;

# Make user admin if needed
$user->update(['is_admin' => true]);
```

### Issue: "phpMyAdmin directory not found"

**Solution**: Verify installation path.

```bash
# Check if phpMyAdmin is installed
ls -la /home/super/phpmyadmin/

# If missing, run installation script
sudo bash install-phpmyadmin.sh
```

### Issue: "Cannot connect to database"

**Solution**: Verify database credentials in `.env`.

```bash
# Check database connection
php artisan tinker
DB::connection()->getPdo();
echo "Connected!";
exit;
```

### Issue: "IP address not allowed"

**Solution**: Add your IP to whitelist in `.env`.

```env
# Get your current IP
PHPMYADMIN_ALLOWED_IPS=127.0.0.1,::1,YOUR.IP.ADDRESS
```

### Issue: Routes Not Showing

**Solution**: Clear Laravel cache and re-register routes.

```bash
php artisan cache:clear
php artisan route:cache
php artisan route:list | grep phpmyadmin
```

---

## 📱 API Examples

### PHP (with Laravel)

```php
// Using Laravel HTTP client
use Illuminate\Support\Facades\Http;

$response = Http::get('http://localhost/api/phpmyadmin/databases');
$databases = $response->json();

foreach ($databases['databases'] as $db) {
    echo $db . "\n";
}
```

### JavaScript (with Fetch)

```javascript
// Get database list
fetch('/api/phpmyadmin/databases')
  .then(response => response.json())
  .then(data => console.log(data.databases))
  .catch(error => console.error('Error:', error));
```

### Python (with Requests)

```python
import requests

response = requests.get('http://localhost/api/phpmyadmin/databases')
databases = response.json()

print(databases['databases'])
```

---

## 🚀 Performance Tips

1. **Limit Result Sets**: When querying large tables, use LIMIT clause
   ```sql
   SELECT * FROM large_table LIMIT 100;
   ```

2. **Add Indexes**: For frequently queried columns
   ```sql
   CREATE INDEX idx_user_email ON users(email);
   ```

3. **Use Database Viewer**: For viewing tables, use phpMyAdmin UI instead of API (faster)

4. **Monitor Query Logs**: Check query execution times in phpMyAdmin status

---

## ✅ Verification Checklist

Run the verification script to confirm everything is set up correctly:

```bash
bash verify-phpmyadmin.sh
```

Expected output:
- ✓ phpMyAdmin directory exists
- ✓ Configuration file exists
- ✓ Laravel config file exists
- ✓ Routes registered
- ✓ Database connection successful
- ✓ Admin user exists

---

## 📚 Additional Resources

- **phpMyAdmin Official**: https://www.phpmyadmin.net/
- **phpMyAdmin Documentation**: https://docs.phpmyadmin.net/
- **Laravel Database**: https://laravel.com/docs/12/database
- **MySQL Documentation**: https://dev.mysql.com/doc/

---

## 🎯 Next Steps

1. ✅ phpMyAdmin is installed and configured
2. ✅ Routes are registered and protected
3. ✅ Admin user is set up
4. 📋 **Next**: Use phpMyAdmin to manage your databases
5. 📋 **Optional**: Configure IP whitelist for additional security
6. 📋 **Optional**: Enable HTTPS in production environment

---

## Support

For issues or questions:
1. Check the **Troubleshooting** section above
2. Review logs: `storage/logs/laravel-*.log`
3. Run verification: `bash verify-phpmyadmin.sh`
4. Check Laravel routes: `php artisan route:list | grep phpmyadmin`

Last updated: $(date)
