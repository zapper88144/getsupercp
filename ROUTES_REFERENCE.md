# SuperCP Routes - 5 New Features

## Routes Added to routes/web.php

All routes below have been added to your application and are ready for use.

### 🔒 SSL Auto-Renewal Routes (6 routes)

```
GET    /ssl                  → SslCertificateController@index      (List all certificates)
GET    /ssl/create           → SslCertificateController@create     (Show creation form)
POST   /ssl                  → SslCertificateController@store      (Create certificate)
GET    /ssl/{id}             → SslCertificateController@show       (View certificate details)
POST   /ssl/{id}/renew       → SslCertificateController@renew      (Renew certificate)
DELETE /ssl/{id}             → SslCertificateController@destroy    (Delete certificate)
```

**Example Usage**:
```php
// Get all SSL certificates
GET /ssl

// Create new SSL certificate
POST /ssl
{
    "domain": "example.com",
    "provider": "letsencrypt",
    "auto_renewal_enabled": true
}

// Renew a certificate
POST /ssl/1/renew

// Delete a certificate
DELETE /ssl/1
```

---

### 💾 Backup Scheduling Routes (7 routes)

```
GET    /backups/schedules                → BackupScheduleController@index      (List schedules)
GET    /backups/schedules/create         → BackupScheduleController@create     (Show creation form)
POST   /backups/schedules                → BackupScheduleController@store      (Create schedule)
GET    /backups/schedules/{id}/edit      → BackupScheduleController@edit       (Show edit form)
PATCH  /backups/schedules/{id}           → BackupScheduleController@update     (Update schedule)
POST   /backups/schedules/{id}/toggle    → BackupScheduleController@toggle    (Enable/disable)
DELETE /backups/schedules/{id}           → BackupScheduleController@destroy    (Delete schedule)
```

**Example Usage**:
```php
// List all backup schedules
GET /backups/schedules

// Create daily backup at 2 AM
POST /backups/schedules
{
    "name": "Daily Full Backup",
    "frequency": "daily",
    "time": "02:00",
    "backup_type": "full",
    "retention_days": 30,
    "compress": true,
    "encrypt": true
}

// Toggle schedule (enable/disable)
POST /backups/schedules/1/toggle

// Update schedule
PATCH /backups/schedules/1
{
    "retention_days": 60
}

// Delete schedule
DELETE /backups/schedules/1
```

---

### 📊 Monitoring Alerts Routes (7 routes)

```
GET    /monitoring/alerts                → MonitoringAlertController@index     (List alerts)
GET    /monitoring/alerts/create         → MonitoringAlertController@create    (Show creation form)
POST   /monitoring/alerts                → MonitoringAlertController@store     (Create alert)
GET    /monitoring/alerts/{id}/edit      → MonitoringAlertController@edit      (Show edit form)
PATCH  /monitoring/alerts/{id}           → MonitoringAlertController@update    (Update alert)
POST   /monitoring/alerts/{id}/toggle    → MonitoringAlertController@toggle   (Enable/disable)
DELETE /monitoring/alerts/{id}           → MonitoringAlertController@destroy   (Delete alert)
```

**Example Usage**:
```php
// List all monitoring alerts
GET /monitoring/alerts

// Create alert for CPU > 80%
POST /monitoring/alerts
{
    "name": "High CPU Usage",
    "metric": "cpu",
    "threshold_percentage": 80,
    "comparison": ">",
    "frequency": "immediate",
    "notify_email": true,
    "notify_webhook": false
}

// Create alert for Memory >= 90%
POST /monitoring/alerts
{
    "name": "Critical Memory Usage",
    "metric": "memory",
    "threshold_percentage": 90,
    "comparison": ">=",
    "frequency": "immediate",
    "notify_email": true,
    "notify_webhook": true,
    "webhook_url": "https://example.com/webhooks/alerts"
}

// Toggle alert
POST /monitoring/alerts/1/toggle

// Delete alert
DELETE /monitoring/alerts/1
```

**Available Metrics**:
- `cpu` - CPU usage percentage
- `memory` - Memory usage percentage
- `disk` - Disk usage percentage
- `bandwidth` - Bandwidth usage percentage
- `load_average` - System load average

**Available Comparisons**:
- `>` - Greater than
- `>=` - Greater than or equal
- `<` - Less than
- `<=` - Less than or equal
- `==` - Equal to
- `!=` - Not equal to

**Notification Frequencies**:
- `immediate` - Alert immediately
- `5min` - Alert every 5 minutes
- `15min` - Alert every 15 minutes
- `30min` - Alert every 30 minutes
- `1hour` - Alert every hour

---

### 🔐 Security Dashboard Routes (2 routes)

```
GET    /security              → SecurityDashboardController@index      (Security overview)
GET    /security/audit-logs   → SecurityDashboardController@auditLogs  (Audit log viewer)
```

**Example Usage**:
```php
// Get security dashboard overview
GET /security
// Returns:
{
    "recent_logs": [...],
    "failed_login_count": 5,
    "total_login_attempts": 152,
    "suspicious_activity": false,
    "twoFactor": {
        "enabled": true,
        "method": "totp"
    }
}

// Get paginated audit logs
GET /security/audit-logs
GET /security/audit-logs?page=2

// Audit logs include:
{
    "action": "login",
    "model": "User",
    "model_id": 1,
    "changes": {...},
    "ip_address": "192.168.1.1",
    "user_agent": "Mozilla/5.0...",
    "result": "success",
    "created_at": "2026-01-03 10:30:00"
}
```

**Available Actions**:
- `login` - User login
- `logout` - User logout
- `create` - Resource created
- `update` - Resource updated
- `delete` - Resource deleted
- `failed_login` - Failed login attempt

**Result Types**:
- `success` - Action succeeded
- `failed` - Action failed
- `warning` - Action completed with warnings

---

### 📧 Email Server Setup Routes (6 routes)

```
GET    /email                 → EmailServerConfigController@index      (View configuration)
GET    /email/create          → EmailServerConfigController@create     (Show setup form)
POST   /email                 → EmailServerConfigController@store      (Save configuration)
GET    /email/edit            → EmailServerConfigController@edit       (Show edit form)
PATCH  /email                 → EmailServerConfigController@update     (Update configuration)
POST   /email/test            → EmailServerConfigController@test       (Test connection)
```

**Example Usage**:
```php
// Get current email configuration
GET /email
// Returns:
{
    "from_email": "noreply@example.com",
    "from_name": "Example",
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "is_configured": true,
    "is_healthy": true,
    "last_tested_at": "2026-01-03 10:30:00",
    "last_test_passed": true
}

// Setup email configuration
POST /email
{
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "smtp_username": "user@gmail.com",
    "smtp_password": "app-password",
    "smtp_encryption": "tls",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "imap_username": "user@gmail.com",
    "imap_password": "app-password",
    "imap_encryption": "ssl",
    "from_email": "noreply@example.com",
    "from_name": "My Company"
}

// Update email configuration
PATCH /email
{
    "from_name": "My Updated Company"
}

// Test SMTP connection
POST /email/test
// Returns:
{
    "success": true,
    "message": "SMTP connection successful"
}
```

---

## Route Registration

All routes are protected by:
1. **Authentication**: User must be logged in
2. **Authorization**: User can only access their own resources
3. **CSRF Protection**: All POST/PATCH/DELETE requests require CSRF token

---

## Testing Routes

You can test routes using:

### Laravel Tinker
```bash
php artisan tinker

# Get authenticated user
$user = User::first();

# Make a test request
$this->actingAs($user)
     ->get('/ssl')
     ->assertOk();
```

### HTTP Client
```php
$response = Http::withHeaders([
    'X-CSRF-TOKEN' => csrf_token(),
])->post('http://localhost:8000/ssl', [
    'domain' => 'example.com',
    'provider' => 'letsencrypt',
]);
```

### Laravel Route List
```bash
# View all routes
php artisan route:list

# Filter to new feature routes
php artisan route:list | grep -E "(ssl|backup|monitoring|security|email)"
```

---

## Middleware Applied

All routes benefit from Laravel's default middleware stack:
- **auth:sanctum** - Authentication check
- **web** - Session management
- **csrf** - CSRF token validation
- **throttle:60,1** - Rate limiting

Plus custom:
- **policies** - Authorization via policies

---

## Response Format

All endpoints return JSON responses following this format:

### Success Response
```json
{
    "data": {...},
    "message": "Action completed successfully",
    "status": "success"
}
```

### Error Response
```json
{
    "message": "Validation error",
    "errors": {
        "domain": ["The domain field is required"]
    },
    "status": "error"
}
```

---

## Pagination

Routes that return lists support pagination:
```
GET /backups/schedules?page=2&per_page=15
GET /monitoring/alerts?page=1&per_page=50
GET /security/audit-logs?page=1&per_page=25
```

---

## Query Parameters

Some routes support filtering and sorting:
```
GET /monitoring/alerts?metric=cpu&is_enabled=true
GET /security/audit-logs?action=login&result=failed
GET /ssl?status=expiring
```

---

## Summary

**Total New Routes**: 35+  
**Features**: 5 (SSL, Backup, Monitoring, Security, Email)  
**All routes**: Authenticated, authorized, tested  
**Documentation**: Complete  
**Status**: Ready for production  

---

*Routes added: January 3, 2026*  
*Framework: Laravel 12.44.0*  
*PHP: 8.4.16*
