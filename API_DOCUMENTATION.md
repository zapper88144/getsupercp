# GetSuperCP API Documentation

## Overview

GetSuperCP provides a comprehensive RESTful API for server management and hosting control. All API endpoints require authentication and return JSON responses.

### Base URL
```
https://api.example.com/api/v1
```

### Authentication

All API requests require an API token in the `Authorization` header:

```bash
curl -H "Authorization: Bearer YOUR_API_TOKEN" \
     https://api.example.com/api/v1/domains
```

### Rate Limiting

- **General requests**: 60 requests per minute per IP
- **API endpoints**: 100 requests per minute per IP
- **Authentication endpoints**: 5 attempts per minute per IP
- **Downloads**: 20 concurrent per user

Rate limit information is returned in response headers:
- `X-RateLimit-Limit`: Maximum requests
- `X-RateLimit-Remaining`: Requests remaining
- `X-RateLimit-Reset`: Unix timestamp when limit resets

### Response Format

All responses follow a consistent JSON format:

**Success Response (2xx)**
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```

**Error Response (4xx, 5xx)**
```json
{
  "success": false,
  "error": "error_code",
  "message": "Human-readable error message",
  "details": { ... }
}
```

### Error Codes

| Code | Status | Description |
|------|--------|-------------|
| `authentication_failed` | 401 | Invalid or missing API token |
| `insufficient_permissions` | 403 | User lacks required permissions |
| `not_found` | 404 | Resource not found |
| `validation_error` | 422 | Invalid request data |
| `rate_limit_exceeded` | 429 | Too many requests |
| `server_error` | 500 | Internal server error |

---

## Web Domains

### List Domains

```
GET /domains
```

Returns paginated list of web domains for the authenticated user.

**Query Parameters**
- `page` (int, optional): Page number (default: 1)
- `per_page` (int, optional): Results per page (default: 15, max: 100)
- `search` (string, optional): Search domains by name
- `status` (string, optional): Filter by status (active, suspended, expired)
- `sort` (string, optional): Sort field (name, created_at, expires_at)
- `order` (string, optional): Sort order (asc, desc)

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "user_id": "uuid",
      "name": "example.com",
      "registrar": "namecheap",
      "expires_at": "2025-12-31T00:00:00Z",
      "auto_renew": true,
      "status": "active",
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z",
      "ssl_certificate": { ... }
    }
  ],
  "meta": {
    "total": 42,
    "per_page": 15,
    "current_page": 1,
    "last_page": 3
  }
}
```

### Get Domain Details

```
GET /domains/{id}
```

Retrieve detailed information about a specific domain.

**Response**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "name": "example.com",
    "registrar": "namecheap",
    "expires_at": "2025-12-31T00:00:00Z",
    "auto_renew": true,
    "status": "active",
    "dns_records": [
      {
        "type": "A",
        "name": "@",
        "value": "192.0.2.1",
        "ttl": 3600
      }
    ],
    "ssl_certificate": {
      "id": "uuid",
      "domain": "example.com",
      "issuer": "Let's Encrypt",
      "expires_at": "2025-12-31T00:00:00Z",
      "auto_renew": true
    }
  }
}
```

### Create Domain

```
POST /domains
```

Register a new domain or add existing domain to GetSuperCP.

**Request Body**
```json
{
  "name": "example.com",
  "registrar": "namecheap",
  "auto_renew": true
}
```

**Response** (201 Created)
```json
{
  "success": true,
  "data": { ... },
  "message": "Domain created successfully"
}
```

### Update Domain

```
PUT /domains/{id}
```

Update domain configuration.

**Request Body**
```json
{
  "auto_renew": false,
  "status": "active"
}
```

### Delete Domain

```
DELETE /domains/{id}
```

Remove domain from GetSuperCP management.

**Response** (204 No Content)

---

## SSL Certificates

### List Certificates

```
GET /ssl-certificates
```

Retrieve paginated list of SSL certificates.

**Query Parameters**
- `page` (int, optional): Page number
- `status` (string, optional): Filter by status (valid, expired, expiring)
- `domain` (string, optional): Filter by domain

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "domain": "example.com",
      "issuer": "Let's Encrypt",
      "fingerprint": "sha256:...",
      "issued_at": "2024-01-01T00:00:00Z",
      "expires_at": "2025-01-01T00:00:00Z",
      "auto_renew": true,
      "status": "valid",
      "alternate_domains": ["www.example.com"],
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": { ... }
}
```

### Create SSL Certificate

```
POST /ssl-certificates
```

Request a new SSL certificate.

**Request Body**
```json
{
  "domain_id": "uuid",
  "domains": ["example.com", "www.example.com"],
  "auto_renew": true,
  "certificate_type": "lets_encrypt"
}
```

**Response** (201 Created)

### Renew Certificate

```
POST /ssl-certificates/{id}/renew
```

Manually trigger certificate renewal.

**Response**
```json
{
  "success": true,
  "data": { ... },
  "message": "Certificate renewal initiated"
}
```

---

## Databases

### List Databases

```
GET /databases
```

Retrieve list of provisioned databases.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "name": "db_production",
      "type": "mysql",
      "host": "db.example.com",
      "port": 3306,
      "user_count": 2,
      "size_mb": 256,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Create Database

```
POST /databases
```

Create a new database.

**Request Body**
```json
{
  "name": "db_production",
  "type": "mysql",
  "collation": "utf8mb4_unicode_ci"
}
```

### Create Database User

```
POST /databases/{id}/users
```

Add a database user.

**Request Body**
```json
{
  "username": "db_user",
  "password": "secure_password",
  "permissions": "all"
}
```

---

## Backups

### List Backups

```
GET /backups
```

Retrieve backup history.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "domain_id": "uuid",
      "status": "completed",
      "size_mb": 512,
      "created_at": "2024-01-01T00:00:00Z",
      "expires_at": "2024-02-01T00:00:00Z"
    }
  ]
}
```

### Create Backup

```
POST /domains/{domain_id}/backups
```

Manually trigger a backup.

**Response** (201 Created)

### Download Backup

```
GET /backups/{id}/download
```

Download backup file.

**Response** (200 with file stream)

### List Backup Schedules

```
GET /backup-schedules
```

Retrieve automated backup schedules.

### Create Backup Schedule

```
POST /backup-schedules
```

Create automated backup schedule.

**Request Body**
```json
{
  "domain_id": "uuid",
  "frequency": "daily",
  "retention_days": 30
}
```

---

## Monitoring

### Get Monitoring Status

```
GET /monitoring/status
```

Retrieve real-time monitoring data.

**Response**
```json
{
  "success": true,
  "data": {
    "cpu_usage": 45.2,
    "memory_usage": 62.1,
    "disk_usage": 78.5,
    "network_in_mbps": 12.3,
    "network_out_mbps": 8.7,
    "uptime_hours": 720,
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

### List Monitoring Alerts

```
GET /monitoring/alerts
```

Retrieve active and historical alerts.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "type": "cpu_usage",
      "threshold": 80,
      "current_value": 85,
      "status": "triggered",
      "created_at": "2024-01-01T12:00:00Z",
      "resolved_at": null
    }
  ]
}
```

### Create Alert

```
POST /monitoring/alerts
```

Create a new monitoring alert.

**Request Body**
```json
{
  "metric": "cpu_usage",
  "threshold": 80,
  "comparison": "greater_than",
  "notification_method": "email"
}
```

---

## Firewall

### List Firewall Rules

```
GET /firewall/rules
```

Retrieve all firewall rules.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "protocol": "tcp",
      "port": 22,
      "source": "192.0.2.0/24",
      "action": "allow",
      "enabled": true,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Create Firewall Rule

```
POST /firewall/rules
```

Add a new firewall rule.

**Request Body**
```json
{
  "protocol": "tcp",
  "port": 8080,
  "source": "0.0.0.0/0",
  "action": "allow"
}
```

### Update Firewall Rule

```
PUT /firewall/rules/{id}
```

Modify a firewall rule.

### Delete Firewall Rule

```
DELETE /firewall/rules/{id}
```

Remove a firewall rule.

---

## Email Accounts

### List Email Accounts

```
GET /email/accounts
```

Retrieve email accounts for domains.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "domain_id": "uuid",
      "email": "admin@example.com",
      "quota_mb": 1024,
      "used_mb": 512,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Create Email Account

```
POST /email/accounts
```

Create a new email account.

**Request Body**
```json
{
  "domain_id": "uuid",
  "local_part": "admin",
  "quota_mb": 1024,
  "password": "secure_password"
}
```

### Update Email Account

```
PUT /email/accounts/{id}
```

Modify email account settings.

### Delete Email Account

```
DELETE /email/accounts/{id}
```

Remove an email account.

---

## Services

### List Services

```
GET /services
```

Retrieve system services status.

**Response**
```json
{
  "success": true,
  "data": [
    {
      "name": "nginx",
      "status": "running",
      "enabled": true,
      "uptime": 720,
      "last_restart": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Restart Service

```
POST /services/{name}/restart
```

Restart a system service.

---

## File Manager

### List Directory

```
GET /files/browse
```

Browse file system directory.

**Query Parameters**
- `path` (string): Directory path to browse

**Response**
```json
{
  "success": true,
  "data": {
    "path": "/var/www/example.com",
    "files": [
      {
        "name": "index.html",
        "type": "file",
        "size": 2048,
        "permissions": "644",
        "modified": "2024-01-01T00:00:00Z"
      }
    ]
  }
}
```

### Upload File

```
POST /files/upload
```

Upload a file.

**Request** (multipart/form-data)
- `file`: File to upload
- `path`: Destination directory

---

## Security

### Get Audit Logs

```
GET /security/audit-logs
```

Retrieve security audit logs.

**Query Parameters**
- `action` (string, optional): Filter by action type
- `user_id` (string, optional): Filter by user

**Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "user_id": "uuid",
      "action": "domain_created",
      "resource": "domain",
      "resource_id": "uuid",
      "changes": { ... },
      "ip_address": "192.0.2.1",
      "user_agent": "Mozilla/5.0...",
      "created_at": "2024-01-01T12:00:00Z"
    }
  ]
}
```

---

## Webhooks

GetSuperCP can send webhook notifications for important events.

### Webhook Events

- `domain.created`
- `domain.updated`
- `domain.deleted`
- `ssl.certificate_issued`
- `ssl.certificate_expiring`
- `backup.completed`
- `backup.failed`
- `alert.triggered`
- `alert.resolved`

### Webhook Payload

```json
{
  "event": "domain.created",
  "timestamp": "2024-01-01T12:00:00Z",
  "data": {
    "id": "uuid",
    "name": "example.com",
    ...
  }
}
```

---

## Examples

### Using cURL

```bash
# List domains
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.example.com/api/v1/domains

# Create domain
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"example.com","registrar":"namecheap"}' \
     https://api.example.com/api/v1/domains

# Get domain details
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.example.com/api/v1/domains/uuid
```

### Using Python

```python
import requests

headers = {"Authorization": "Bearer YOUR_TOKEN"}

# List domains
response = requests.get(
    "https://api.example.com/api/v1/domains",
    headers=headers
)
domains = response.json()["data"]

# Create domain
response = requests.post(
    "https://api.example.com/api/v1/domains",
    headers=headers,
    json={"name": "example.com", "registrar": "namecheap"}
)
```

### Using JavaScript

```javascript
const headers = {"Authorization": "Bearer YOUR_TOKEN"};

// List domains
const response = await fetch("https://api.example.com/api/v1/domains", {
  headers
});
const data = await response.json();
console.log(data.data);

// Create domain
const response = await fetch("https://api.example.com/api/v1/domains", {
  method: "POST",
  headers: {
    ...headers,
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    name: "example.com",
    registrar: "namecheap"
  })
});
```

---

## Support

For API support, email: api-support@example.com

Last updated: January 4, 2026
