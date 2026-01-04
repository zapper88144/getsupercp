# GetSuperCP User Guide

## Getting Started

Welcome to GetSuperCP! This guide will help you manage your hosting infrastructure efficiently.

### Initial Login

1. Visit your GetSuperCP installation URL
2. Enter your email address and password
3. Verify your email if required
4. Enable Two-Factor Authentication (recommended for security)

### Dashboard Overview

The dashboard shows:
- **Overview**: Key metrics and recent activity
- **Quick Actions**: Create domains, backups, certificates
- **Alerts**: Active warnings and notifications
- **System Status**: Resource usage (CPU, Memory, Disk)

---

## Managing Web Domains

### Add a Domain

1. Go to **Web Domains** → **Add Domain**
2. Enter your domain name (e.g., `example.com`)
3. Select your registrar
4. Enable auto-renewal if desired
5. Click **Create**

### Update Domain Settings

1. Go to **Web Domains** → Select your domain
2. Click **Edit**
3. Modify settings (auto-renewal, nameservers, etc.)
4. Click **Save**

### View Domain Details

Select a domain to see:
- Expiration date
- DNS records
- SSL certificate status
- Web hosting configuration
- Associated resources

### Delete a Domain

1. Go to **Web Domains**
2. Click **Delete** next to the domain
3. Confirm deletion

---

## SSL Certificates

### Request a New Certificate

1. Go to **SSL Certificates** → **Request Certificate**
2. Select the domain
3. Add alternate domains (www.example.com, etc.) if needed
4. Enable auto-renewal
5. Select **Let's Encrypt** (recommended)
6. Click **Request**

### Monitor Certificate Status

- **Valid**: Certificate is active and valid
- **Expiring Soon**: Certificate expires within 30 days
- **Expired**: Certificate has expired
- **Pending**: Certificate is being processed

### Renew a Certificate

1. Go to **SSL Certificates**
2. Find your certificate
3. Click **Renew** (typically not needed if auto-renewal is enabled)
4. Follow the prompts

### Certificate Details

Each certificate shows:
- Domain and alternate domains
- Issuer (Let's Encrypt, etc.)
- Issue and expiration dates
- Fingerprint
- Auto-renewal status

---

## Databases

### Create a New Database

1. Go to **Databases** → **Create Database**
2. Enter database name
3. Select type (MySQL or PostgreSQL)
4. Click **Create**

### Create Database User

1. Go to **Databases** → Select database
2. Click **Add User**
3. Enter username and secure password
4. Grant permissions (Select, Insert, Update, Delete, Create, Drop, etc.)
5. Click **Create User**

### Database Credentials

After creation, you'll receive:
- **Hostname**: Database server address
- **Port**: 3306 (MySQL) or 5432 (PostgreSQL)
- **Database Name**: Your database name
- **Username**: Your database user
- **Password**: Secure password

### Database Management

Common tasks:
- **Export Data**: Download database dump
- **Import Data**: Upload SQL file
- **Reset User Password**: Update user credentials
- **Delete Database**: Remove database and users

---

## Backups

### Create Automatic Backup Schedule

1. Go to **Backups** → **Schedules**
2. Click **Create Schedule**
3. Select domain/resource to backup
4. Choose frequency (Daily, Weekly, Monthly)
5. Set retention period (how long to keep backups)
6. Click **Create**

### Manual Backup

1. Go to **Backups**
2. Click **Create Backup**
3. Select what to backup (files, database, both)
4. Click **Start Backup**

### Download Backup

1. Go to **Backups**
2. Select completed backup
3. Click **Download**
4. Save file securely

### Restore from Backup

1. Go to **Backups**
2. Select backup
3. Click **Restore**
4. Confirm domain/resource
5. Click **Confirm Restore**

**Warning**: Restore will overwrite current data!

---

## Monitoring

### View System Status

1. Go to **Monitoring** → **Status**
2. See real-time metrics:
   - CPU Usage
   - Memory Usage
   - Disk Space
   - Network Traffic
   - Uptime

### Create Monitoring Alerts

1. Go to **Monitoring** → **Alerts**
2. Click **Create Alert**
3. Select metric to monitor (CPU, Memory, Disk, etc.)
4. Set threshold (alert when value exceeds X%)
5. Choose notification method (Email, Webhook)
6. Click **Create**

### Manage Alerts

- **View Active**: See current alerts
- **Acknowledge**: Mark alert as seen
- **Resolve**: Close resolved alerts
- **Edit**: Modify alert settings
- **Delete**: Remove alert

---

## Firewall

### Add Firewall Rule

1. Go to **Firewall** → **Rules**
2. Click **Add Rule**
3. Select protocol (TCP, UDP, ICMP)
4. Enter port (or leave blank for all)
5. Set source IP/CIDR (0.0.0.0/0 for any)
6. Choose action (Allow or Deny)
7. Click **Create**

### Common Rules

```
Protocol: TCP
Port: 22
Source: YOUR_IP
Action: Allow
Purpose: SSH access

Protocol: TCP
Port: 80
Source: 0.0.0.0/0
Action: Allow
Purpose: HTTP access

Protocol: TCP
Port: 443
Source: 0.0.0.0/0
Action: Allow
Purpose: HTTPS access
```

### Manage Rules

- **Enable/Disable**: Toggle rule without deleting
- **Edit**: Change rule parameters
- **Delete**: Remove rule
- **Reorder**: Change rule priority

---

## Email Accounts

### Create Email Account

1. Go to **Email** → **Accounts**
2. Click **Create Account**
3. Select domain
4. Enter local part (before @)
5. Set quota (disk space allowed)
6. Enter password
7. Click **Create**

### Email Account Features

- **Full Name**: Your display name
- **Quota**: Storage limit (1GB = 1024 MB)
- **Auto-Responder**: Set automatic replies
- **Forwarding**: Forward to another email
- **Spam Filter**: Enable/disable spam filtering

### Connect to Email Client

**For IMAP (recommended)**:
- Server: mail.yourdomain.com
- Port: 993 (SSL) or 143 (TLS)
- Username: full@email.address
- Password: your password

**For SMTP**:
- Server: mail.yourdomain.com
- Port: 465 (SSL) or 587 (TLS)
- Username: full@email.address
- Password: your password

---

## FTP Users

### Create FTP Account

1. Go to **FTP Users**
2. Click **Create User**
3. Select domain
4. Enter username
5. Enter secure password
6. Set home directory
7. Click **Create**

### FTP Connection Details

- **Hostname**: ftp.yourdomain.com or IP address
- **Port**: 21 (FTP) or 990 (FTPS)
- **Username**: your FTP username
- **Password**: your FTP password

### FTP Client Settings

Recommended: Use SFTP (SSH File Transfer Protocol) instead of FTP for security.

---

## File Manager

### Browse Files

1. Go to **File Manager**
2. Navigate directories
3. Upload/download files
4. Create folders
5. Edit file permissions

### Upload Files

1. Click **Upload**
2. Select file(s)
3. Choose destination
4. Click **Upload**

### Create/Edit Files

1. Right-click → **Create File**
2. Enter filename
3. Click **Create**
4. Click **Edit** to modify

### Manage Permissions

1. Right-click file/folder
2. Click **Permissions**
3. Adjust read/write/execute
4. Click **Save**

---

## DNS Management

### View DNS Records

1. Go to **DNS**
2. Select domain
3. View all DNS records
4. See record types (A, CNAME, MX, TXT, etc.)

### Add DNS Record

1. Go to **DNS** → Select domain
2. Click **Add Record**
3. Select record type (A, AAAA, CNAME, MX, TXT, etc.)
4. Enter subdomain name (or @ for root)
5. Enter value/data
6. Set TTL (time to live, typically 3600)
7. Click **Create**

### Update DNS Record

1. Select record
2. Click **Edit**
3. Modify value/TTL
4. Click **Save**

### DNS Record Types

- **A**: Point to IPv4 address (e.g., 192.0.2.1)
- **AAAA**: Point to IPv6 address
- **CNAME**: Point to another domain (e.g., www → example.com)
- **MX**: Mail server record (for email)
- **TXT**: Text records (SPF, DKIM, verification)
- **NS**: Nameserver records

---

## Cron Jobs

### Create Cron Job

1. Go to **Cron Jobs**
2. Click **Create Job**
3. Enter full command path
4. Select frequency (custom cron expression or preset)
5. Click **Create**

### Common Cron Schedules

```
Every minute:        * * * * *
Every 5 minutes:     */5 * * * *
Hourly:              0 * * * *
Daily at 2 AM:       0 2 * * *
Weekly (Sunday):     0 0 * * 0
Monthly (1st):       0 0 1 * *
```

### Manage Jobs

- **Enable/Disable**: Pause without deleting
- **Edit**: Change command or schedule
- **View Logs**: See execution history
- **Delete**: Remove job

---

## Services

### View Service Status

1. Go to **Services**
2. See status of all services:
   - Web Server (Nginx/Apache)
   - Database Server
   - Mail Server
   - DNS Server
   - FTP Server

### Manage Services

Common actions:
- **Restart**: Stop and start service
- **Stop**: Turn off service
- **Start**: Turn on service
- **Enable/Disable**: Start on boot

---

## Security

### Enable Two-Factor Authentication

1. Go to **Profile** → **Security**
2. Click **Enable 2FA**
3. Scan QR code with authenticator app
4. Enter verification code
5. Save backup codes
6. Click **Enable**

### View Audit Log

1. Go to **Security** → **Audit Log**
2. See all account activity:
   - Login times
   - Changes made
   - Resources accessed
   - Failed login attempts

### Manage API Keys

1. Go to **Profile** → **API Keys**
2. Click **Generate Key**
3. Give it a name
4. Set permissions
5. Copy key (shown only once!)
6. Use in API requests

### Update Password

1. Go to **Profile** → **Password**
2. Enter current password
3. Enter new password (strong!)
4. Confirm new password
5. Click **Update**

---

## Common Tasks

### Deploy Website Files

1. Create FTP user (see FTP Users section)
2. Connect via SFTP client
3. Upload files to `/public_html`
4. Set proper permissions (755 for folders, 644 for files)

### Configure Email

1. Create email accounts (see Email Accounts section)
2. Configure in email client (see connection details)
3. Test sending/receiving emails
4. Set up SPF/DKIM records (see DNS Management)

### Setup SSL Certificate

1. Request certificate (see SSL Certificates section)
2. Wait for validation (usually automatic)
3. Install on web server (automatic)
4. Visit https://yourdomain.com
5. Verify security certificate is valid

### Create Database for App

1. Create database (see Databases section)
2. Create database user
3. Note credentials
4. Use in application config

---

## Troubleshooting

### Website Not Loading

1. Check DNS records pointing to correct IP
2. Verify SSL certificate is valid
3. Check firewall allows port 80/443
4. Review web server logs
5. Contact support if issue persists

### Email Not Working

1. Verify email account exists
2. Check DNS MX records
3. Test IMAP/SMTP connectivity
4. Check email client settings
5. Review email server logs

### High Resource Usage

1. Go to Monitoring
2. Check which processes use most resources
3. Review logs for errors
4. Reduce database queries or optimize code
5. Consider upgrading resources

### Can't Connect via FTP/SSH

1. Verify firewall allows ports 21, 22
2. Check FTP/SSH user exists and enabled
3. Verify password is correct
4. Try different FTP client
5. Check IP isn't blocked by firewall

---

## Support

### Getting Help

- **Documentation**: Check this guide
- **Knowledge Base**: Visit help center
- **Contact Support**: Email support@example.com
- **Live Chat**: Available during business hours
- **Status Page**: Check service status at status.example.com

### Report a Bug

1. Go to Help → Report Bug
2. Describe the issue
3. Provide screenshots if helpful
4. Click Submit
5. We'll contact you with updates

---

## Security Best Practices

1. **Use Strong Passwords**: Mix uppercase, lowercase, numbers, symbols
2. **Enable 2FA**: Protects your account
3. **Keep Software Updated**: Update applications regularly
4. **Regular Backups**: Backup weekly at minimum
5. **Monitor Activity**: Check audit logs regularly
6. **Use HTTPS**: Always enable SSL certificates
7. **Limit Access**: Use firewall to restrict access
8. **Review Permissions**: Ensure proper file/folder permissions
9. **Rotate Passwords**: Change passwords every 90 days
10. **Enable Alerts**: Set up monitoring notifications

---

**Last Updated**: January 4, 2026

For the latest version, visit: https://docs.example.com/user-guide
