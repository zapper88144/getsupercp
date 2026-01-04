# Service Layer Implementation Summary

## Overview
GetSuperCP service layer provides a clean abstraction between Laravel controllers and the Rust daemon via JSON-RPC 2.0 communication over Unix Domain Sockets.

## Architecture

### Communication Flow
```
HTTP Request → Laravel Controller → Service Layer → RustDaemonClient → Unix Socket → Rust Daemon → System Operations
                                                                                    ↓
                                                              Eloquent Models ← Database Records
```

## Services Implemented

### 1. RustDaemonClient Service
**File:** `app/Services/RustDaemonClient.php`

Core JSON-RPC 2.0 communication layer for inter-process communication with the Rust daemon.

**Key Methods:**
- `call(method, params)` - Execute JSON-RPC method
- `send(request)` - Raw socket communication
- `parseResponse(response)` - JSON-RPC response validation
- `isRunning()` - Daemon health check (ping)

**Helper Methods (40+):**
- System: `getSystemStats()`, `getStatus()`, `restartService()`
- Web: `createVhost()`, `deleteVhost()`, `listVhosts()`
- Database: `createDatabase()`, `deleteDatabase()`, `listDatabases()`
- SSL: `requestSslCert()`
- Firewall: `getFirewallStatus()`, `applyFirewallRule()`, `deleteFirewallRule()`, `toggleFirewall()`
- Email: `updateEmailAccount()`, `deleteEmailAccount()`
- DNS: `updateDnsZone()`, `deleteDnsZone()`
- FTP: `createFtpUser()`, `deleteFtpUser()`, `listFtpUsers()`
- Cron: `updateCronJobs()`, `listCronJobs()`
- Backup: `createBackup()`, `restoreBackup()`, `createDbBackup()`, `restoreDbBackup()`
- Files: `listFiles()`, `readFile()`, `writeFile()`, `deleteFile()`, `createDirectory()`, `renameFile()`
- Logs: `getLogs()`, `getServiceLogs()`

**Socket Configuration:**
- Path: `/home/super/getsupercp/storage/framework/sockets/super-daemon.sock`
- Timeout: 30 seconds (configurable)
- Error Handling: Exception throwing on JSON-RPC errors

**Example Usage:**
```php
$client = new RustDaemonClient();
$result = $client->createVhost([
    'domain' => 'example.com',
    'root' => '/var/www/example.com',
    'php_version' => '8.4',
    'user' => 'example',
]);
```

---

### 2. WebDomainService
**File:** `app/Services/WebDomainService.php`

Manages Nginx virtual hosts and PHP-FPM pools via the daemon.

**Key Methods:**
- `create(user, data)` - Create new domain with Nginx/PHP-FPM config
- `update(domain, data)` - Update domain configuration
- `delete(domain)` - Delete domain and remove configs
- `toggleSsl(domain, enable)` - Enable/disable HTTPS with Let's Encrypt
- `renewSsl(domain)` - Renew expiring SSL certificates
- `sync()` - Reconcile daemon state with database
- `isDaemonRunning()` - Health check

**Validation:**
- Domain format: RFC 1123 compliant regex
- Path format: Absolute path validation
- Uniqueness: Database check for duplicates

**Example Usage:**
```php
$service = new WebDomainService();
$domain = $service->create($user, [
    'domain' => 'mysite.com',
    'root_path' => '/var/www/mysite.com',
    'php_version' => '8.4',
]);
```

**Database Model:** `App\Models\WebDomain`

---

### 3. EmailService
**File:** `app/Services/EmailService.php`

Manages email accounts on Postfix/Dovecot backend via the daemon.

**Key Methods:**
- `create(user, data)` - Create email account
- `update(account, data)` - Update account settings
- `delete(account)` - Delete email account
- `updateQuota(account, quotaMb)` - Modify storage quota
- `isDaemonRunning()` - Health check

**Validation:**
- Email format: RFC 5322 compliant
- Quota: 256 MB - 100 GB range
- Uniqueness: Database check

**Example Usage:**
```php
$service = new EmailService();
$email = $service->create($user, [
    'email' => 'user@example.com',
    'password' => 'secure_password',
    'quota_mb' => 1024,
]);
```

**Database Model:** `App\Models\EmailAccount`

---

### 4. DnsService
**File:** `app/Services/DnsService.php`

Manages DNS zones and records on PowerDNS via the daemon.

**Key Methods:**
- `createZone(user, data)` - Create DNS zone
- `addRecord(zone, data)` - Add DNS record (A, AAAA, CNAME, MX, TXT, SRV, CAA, NS)
- `updateRecord(record, data)` - Modify DNS record
- `deleteRecord(record)` - Remove DNS record
- `deleteZone(zone)` - Delete zone and all records
- `sync()` - Sync zones with daemon
- `isDaemonRunning()` - Health check

**Supported Record Types:** A, AAAA, CNAME, MX, TXT, SRV, CAA, NS

**Example Usage:**
```php
$service = new DnsService();
$zone = $service->createZone($user, ['domain' => 'example.com']);
$record = $service->addRecord($zone, [
    'name' => 'www',
    'type' => 'A',
    'content' => '192.168.1.100',
    'ttl' => 3600,
]);
```

**Database Models:** `App\Models\DnsZone`, `App\Models\DnsRecord`

---

### 5. DatabaseService
**File:** `app/Services/DatabaseService.php`

Manages MySQL databases and users via the daemon.

**Key Methods:**
- `create(user, data)` - Create database with dedicated user
- `delete(database)` - Drop database and user
- `reset(database)` - Truncate all tables
- `getSize(database)` - Get database size
- `updateMaxConnections(database, count)` - Modify connection limit
- `isDaemonRunning()` - Health check

**Validation:**
- Database name: 1-64 alphanumeric, underscore, dash
- Uniqueness: Database check
- Username: Auto-generated from prefix + user ID

**Example Usage:**
```php
$service = new DatabaseService();
$db = $service->create($user, [
    'name' => 'myapp_db',
    'engine' => 'InnoDB',
    'max_connections' => 100,
]);
```

**Database Model:** `App\Models\Database`

---

### 6. FtpService
**File:** `app/Services/FtpService.php`

Manages FTP users on Pure-FTPd backend via the daemon.

**Key Methods:**
- `create(user, data)` - Create FTP user
- `update(ftpUser, data)` - Update FTP account
- `delete(ftpUser)` - Delete FTP user
- `list()` - List all FTP users
- `enable(ftpUser)` - Activate user
- `disable(ftpUser)` - Suspend user
- `isDaemonRunning()` - Health check

**Validation:**
- Username: 1-32 alphanumeric, underscore, dash
- Path: Absolute path validation
- Uniqueness: Database check

**Example Usage:**
```php
$service = new FtpService();
$ftpUser = $service->create($user, [
    'username' => 'ftpuser',
    'password' => 'secure_password',
    'home_dir' => '/home/ftp/ftpuser',
]);
```

**Database Model:** `App\Models\FtpUser`

---

### 7. FirewallService
**File:** `app/Services/FirewallService.php`

Manages ufw firewall rules via the daemon.

**Key Methods:**
- `getStatus()` - Get firewall status and rule count
- `enable()` - Enable firewall
- `disable()` - Disable firewall
- `createRule(data)` - Add firewall rule
- `updateRule(rule, data)` - Modify rule
- `deleteRule(rule)` - Remove rule
- `isDaemonRunning()` - Health check

**Validation:**
- Port: 1-65535
- Protocol: tcp, udp
- Action: allow, deny, reject
- Source: any, specific IP/CIDR

**Example Usage:**
```php
$service = new FirewallService();
$rule = $service->createRule([
    'port' => 443,
    'protocol' => 'tcp',
    'action' => 'allow',
    'source' => 'any',
    'description' => 'HTTPS',
]);
```

**Database Model:** `App\Models\FirewallRule`

---

### 8. BackupService
**File:** `app/Services/BackupService.php`

Manages backups and restore operations via the daemon.

**Key Methods:**
- `createBackup(user, type, targets)` - Create manual backup
- `restore(backup, options)` - Restore from backup
- `delete(backup)` - Delete backup file
- `createSchedule(user, data)` - Create backup schedule
- `updateSchedule(schedule, data)` - Modify schedule
- `deleteSchedule(schedule)` - Remove schedule
- `isDaemonRunning()` - Health check

**Backup Types:** full, database, files, domain

**Example Usage:**
```php
$service = new BackupService();
$backup = $service->createBackup($user, 'full', [
    'databases' => ['myapp_db'],
    'directories' => ['/var/www/mysite.com'],
]);
```

**Database Models:** `App\Models\Backup`, `App\Models\BackupSchedule`

---

## Validation (FormRequest Classes)

### StoreWebDomainRequest
- Domain format validation (RFC 1123)
- Unique domain check
- Root path validation
- PHP version whitelist (7.4 - 8.4)
- Alias domain validation

### UpdateWebDomainRequest
- Same rules as Store request
- Optional fields support

### StoreEmailAccountRequest
- Email format and uniqueness
- Password confirmation
- Quota range (256 MB - 100 GB)

### StoreDnsZoneRequest
- Domain format validation
- Unique zone check

### StoreDatabaseRequest
- Database name format (1-64 alphanumeric)
- Unique name check
- Engine whitelist (InnoDB, MyISAM)
- Connection limit range (10-10000)

### StoreFtpUserRequest
- Username format validation
- Path validation
- Password confirmation
- Unique username check

---

## System Templates

### resources/templates/system/nginx_vhost.conf.stub
Nginx virtual host configuration template with placeholders:
- `{{DOMAIN}}` - Primary domain
- `{{ALIASES}}` - Alternative domains
- `{{ROOT}}` - Document root path
- `{{SAFE_NAME}}` - Safe identifier (alphanumeric)
- `{{PHP_VERSION}}` - PHP version (7.4, 8.0-8.4)
- `{{SSL_CONFIG}}` - SSL certificate configuration
- `{{SSL_REDIRECT}}` - HTTP to HTTPS redirect (when SSL enabled)

**Features:**
- Security headers (X-Frame-Options, CSP, etc.)
- Hidden file protection
- Static file caching
- PHP-FPM socket handling
- Error logging

### resources/templates/system/php_fpm_pool.conf.stub
PHP-FPM pool configuration template with placeholders:
- `{{SAFE_NAME}}` - Pool identifier
- `{{USER}}` - System user
- `{{GROUP}}` - System group
- `{{PHP_VERSION}}` - PHP version
- `{{DOMAIN}}` - Associated domain
- `{{MAX_CHILDREN}}` - Max child processes
- `{{START_SERVERS}}` - Initial servers
- `{{MIN_SPARE_SERVERS}}` - Minimum spare processes
- `{{MAX_SPARE_SERVERS}}` - Maximum spare processes
- `{{UPLOAD_MAX_FILESIZE}}` - Upload limit
- `{{POST_MAX_SIZE}}` - POST data limit
- `{{MEMORY_LIMIT}}` - Memory limit
- `{{MAX_EXECUTION_TIME}}` - Execution timeout

**Features:**
- Dynamic process management
- Security restrictions (disabled functions)
- Session handling
- Logging configuration

---

## Error Handling

All services implement consistent error handling:

1. **Validation Errors** - Thrown before daemon communication
2. **Daemon Errors** - JSON-RPC error responses are converted to exceptions
3. **System Errors** - Database/file system errors are caught and logged
4. **Logging** - All operations logged at info/error levels

**Exception Messages:**
- User-friendly descriptions
- Contextual error details in logs
- No credential leakage

---

## Best Practices

### Service Usage in Controllers
```php
public function store(StoreWebDomainRequest $request, WebDomainService $service)
{
    try {
        $domain = $service->create($request->user(), $request->validated());
        return redirect()->route('domains.show', $domain)
            ->with('success', 'Domain created successfully');
    } catch (Exception $e) {
        return back()->with('error', $e->getMessage());
    }
}
```

### Dependency Injection
Services use constructor property promotion for RustDaemonClient:
```php
public function __construct(RustDaemonClient $daemon = null)
{
    $this->daemon = $daemon ?? new RustDaemonClient();
}
```

This allows:
- Easy mocking in tests
- Service container injection
- Graceful fallback to direct instantiation

### Database Synchronization
Services maintain both:
1. **Daemon State** - Configuration files, system services
2. **Database Records** - Metadata, user assignments, history

The `sync()` methods reconcile differences between the two.

---

## Testing Recommendations

Each service should have feature tests covering:
1. **Creation** - Valid and invalid inputs
2. **Modification** - Update scenarios
3. **Deletion** - Resource removal
4. **Errors** - Daemon failure handling
5. **Validation** - FormRequest rules

```bash
# Run service tests
php artisan test --filter=WebDomainServiceTest
php artisan test --filter=EmailServiceTest
# etc.
```

---

## Next Steps

1. **Controllers** - Implement CRUD operations using these services
2. **Policies** - Add authorization rules for resources
3. **React Components** - Build UI for each service module
4. **Tests** - Create comprehensive test suites
5. **Documentation** - Generate API documentation
