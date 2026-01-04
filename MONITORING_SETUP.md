# GetSuperCP Monitoring & Health Check Configuration

## Overview

GetSuperCP includes comprehensive monitoring and health checking capabilities to ensure your application runs smoothly in production. This guide walks you through setting up automated monitoring.

## Quick Start

### 1. Run Health Checks Manually

```bash
cd /home/super/getsupercp
./health-check.sh
```

Check the status file:

```bash
cat storage/health_status.json
```

Check logs:

```bash
tail -50 storage/logs/health-check.log
```

### 2. Set Up Automated Health Checks

Add to your crontab (`crontab -e`):

```bash
# Run health checks every 5 minutes
*/5 * * * * /home/super/getsupercp/health-check.sh

# Run daily optimization
0 2 * * * /home/super/getsupercp/deploy.sh staging optimize

# Backup database daily at 3 AM
0 3 * * * /home/super/getsupercp/deploy.sh production backup
```

### 3. Configure Alert Notifications

Edit `.env` to configure alert channels:

```env
# Email alerts
MAIL_FROM_ADDRESS=alerts@yourdomain.com
ALERT_EMAIL=admin@yourdomain.com

# Slack alerts (optional)
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# PagerDuty alerts (optional)
PAGERDUTY_INTEGRATION_KEY=your_integration_key
```

---

## Health Check Metrics

### Application Health

**What it checks:**
- PHP-FPM process running
- Laravel application responsive
- Database connection working
- Cache system operational

**Threshold:** CRITICAL if any fails
**Action:** Email alert to admin, log to file

### Database Health

**What it checks:**
- MySQL/PostgreSQL connectivity
- Database size under limits
- Replication lag (if configured)
- Query performance baseline

**Threshold:** WARNING if slow, CRITICAL if offline
**Action:** Email alert, may trigger failover

### Cache System

**What it checks:**
- Redis/Memcached running
- Cache operations working
- Memory usage under limits
- Hit/miss ratio

**Threshold:** WARNING if memory >85%, CRITICAL if offline
**Action:** Email alert, attempt restart

### Disk Space

**What it checks:**
- Root filesystem usage
- Application directory usage
- Log file size
- Backup storage availability

**Threshold:** WARNING if <2GB, CRITICAL if <500MB
**Action:** Email alert, may trigger log rotation

### SSL Certificates

**What it checks:**
- Certificate validity
- Days until expiration
- Certificate chain validity
- Key strength (2048-bit minimum)

**Threshold:** WARNING if <30 days, CRITICAL if <7 days or expired
**Action:** Email alert, may trigger renewal

### Backups

**What it checks:**
- Latest backup timestamp
- Backup file size
- Backup storage available
- Backup integrity

**Threshold:** WARNING if older than 24hrs, CRITICAL if older than 48hrs
**Action:** Email alert, may trigger manual backup

### Security Alerts

**What it checks:**
- Failed login attempts
- Suspicious API activity
- Rate limit violations
- Unauthorized access attempts

**Threshold:** WARNING if >10 failed logins, CRITICAL if >50
**Action:** Email alert, log to security audit table

---

## Web Dashboard Integration

GetSuperCP includes a monitoring dashboard at `/monitoring`:

1. **Status Page** - Real-time system metrics
   - CPU usage percentage
   - Memory usage percentage
   - Disk usage percentage
   - Network I/O
   - Service status

2. **Alerts Page** - Historical and active alerts
   - Alert creation time
   - Metric and threshold
   - Current value
   - Status (active, resolved)
   - Acknowledge/resolve actions

3. **Charts** - Visual trend data
   - 24-hour metrics trending
   - Performance baseline
   - Anomaly detection
   - Capacity planning data

### Create Dashboard Alert

1. Login to GetSuperCP
2. Go to **Monitoring** → **Alerts**
3. Click **Create Alert**
4. Configure:
   - **Metric**: CPU, Memory, Disk, Database, etc.
   - **Condition**: Greater than, Less than, Equals
   - **Threshold**: 80, 85, 90, etc.
   - **Duration**: How long threshold before alerting (1, 5, 10 minutes)
   - **Notification**: Email, Webhook, In-app

---

## API Monitoring

### Monitoring Endpoints

Check system status via API:

```bash
# Get system status (JSON)
curl -H "Authorization: Bearer YOUR_API_KEY" \
  https://yourdomain.com/api/monitoring/status

# Response:
{
  "application": {
    "status": "healthy",
    "uptime": 86400,
    "memory": 512,
    "cpu": 25
  },
  "database": {
    "status": "healthy",
    "connections": 5,
    "slow_queries": 0
  },
  "cache": {
    "status": "healthy",
    "memory": 256,
    "hit_rate": 0.85
  },
  "alerts": {
    "active": 0,
    "triggered_today": 2
  }
}
```

### View Monitoring Alerts

```bash
# List active alerts
curl -H "Authorization: Bearer YOUR_API_KEY" \
  https://yourdomain.com/api/monitoring/alerts

# Create new alert
curl -X POST \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"metric": "cpu", "condition": ">", "threshold": 80, "enabled": true}' \
  https://yourdomain.com/api/monitoring/alerts
```

---

## Log Aggregation

### Local Log Files

Health check logs are stored in:
```
storage/logs/health-check.log
storage/logs/deployment-TIMESTAMP.log
storage/logs/laravel.log
```

### View Health Check Logs

```bash
# Last 50 lines
tail -50 storage/logs/health-check.log

# Follow in real-time
tail -f storage/logs/health-check.log

# Search for errors
grep ERROR storage/logs/health-check.log

# Count by severity
grep CRITICAL storage/logs/health-check.log | wc -l
```

### Centralized Logging (ELK Stack, etc.)

For production, configure centralized logging:

```bash
# Install Filebeat or similar
apt-get install filebeat

# Configure to ship logs
cat > /etc/filebeat/filebeat.yml << EOF
filebeat.inputs:
- type: log
  paths:
    - /home/super/getsupercp/storage/logs/*.log
  json.message_key: log

output.elasticsearch:
  hosts: ["localhost:9200"]
EOF

systemctl restart filebeat
```

---

## Performance Monitoring

### Slow Query Logging

Enable in MySQL/PostgreSQL:

**MySQL:**
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

**PostgreSQL:**
```sql
ALTER SYSTEM SET log_min_duration_statement = 2000;
SELECT pg_reload_conf();
```

### PHP Performance Monitoring

Install PHP monitoring tools:

```bash
# Option 1: Xdebug (development only)
sudo apt-get install php8.4-xdebug

# Option 2: Blackfire (production-grade)
wget -O - https://packages.blackfire.io/gpg.key | apt-key add -
apt-get install blackfire-php
```

### Database Performance

```bash
# View slow queries
mysql> SELECT * FROM mysql.slow_log;

# Show running queries
mysql> SHOW PROCESSLIST;

# Check index usage
mysql> SELECT * FROM performance_schema.table_io_waits_summary_by_index_usage;
```

---

## Alerting Channels

### Email Alerts

Configure SMTP in `.env`:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

### Slack Integration

Create Slack webhook:

1. Go to https://api.slack.com/apps
2. Create new app
3. Enable Incoming Webhooks
4. Create webhook for channel
5. Add to `.env`:

```env
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
SLACK_CHANNEL=#monitoring
```

### PagerDuty Integration

1. Create PagerDuty service
2. Get integration key
3. Configure in app:

```env
PAGERDUTY_ENABLED=true
PAGERDUTY_INTEGRATION_KEY=your_key
PAGERDUTY_SERVICE_ID=your_service_id
```

### Webhook Alerts

Create custom webhook for third-party integrations:

```json
{
  "event": "cpu_threshold_exceeded",
  "metric": "cpu_usage",
  "value": 85,
  "threshold": 80,
  "timestamp": "2026-01-04T10:30:00Z",
  "severity": "warning"
}
```

---

## Troubleshooting Monitoring

### Health Check Not Running

```bash
# Check cron job
crontab -l

# Check file permissions
ls -la health-check.sh

# Make executable
chmod +x health-check.sh

# Run manually to debug
./health-check.sh -v  # verbose mode
```

### Missing Alert Notifications

1. **Email not sending:**
   ```bash
   php artisan tinker
   Mail::to('test@example.com')->send(new TestMail());
   ```

2. **Slack webhook failing:**
   - Verify webhook URL in `.env`
   - Check channel exists and bot has access
   - Test with: `curl -X POST -d 'test' YOUR_WEBHOOK_URL`

3. **Database alerts not triggering:**
   - Verify database connection in health-check.sh
   - Check stored procedure permissions
   - Run: `./health-check.sh`

### High CPU from Health Checks

If health-check.sh uses too much CPU:
- Reduce frequency: `0,30 * * * *` (every 30 minutes)
- Use `nice`: `*/5 * * * * nice -n 10 /path/health-check.sh`
- Limit concurrent checks with `flock`

---

## Advanced Configuration

### Custom Health Checks

Extend health-check.sh with custom checks:

```bash
#!/bin/bash

# Add custom check function
check_custom_service() {
  if systemctl is-active --quiet my-service; then
    echo "OK"
  else
    echo "CRITICAL"
  fi
}

# Add to main checks
CUSTOM_STATUS=$(check_custom_service)
```

### Metrics Export

Export metrics for Prometheus:

```bash
# Enable Prometheus metrics endpoint
php artisan tinker
# Create custom metrics route
```

### SLA Monitoring

Track uptime SLA:

```sql
SELECT 
  DATE_FORMAT(timestamp, '%Y-%m-%d') as date,
  ROUND(SUM(IF(status = 'healthy', 1, 0)) / COUNT(*) * 100, 2) as uptime_percent
FROM health_checks
GROUP BY DATE_FORMAT(timestamp, '%Y-%m-%d')
ORDER BY date DESC;
```

---

## Best Practices

1. **Monitor the Monitors**: Set up monitoring for your monitoring system
2. **Test Alerts**: Regularly test alert notifications
3. **Document Thresholds**: Document why each threshold was chosen
4. **Review Regularly**: Review alerts weekly for false positives
5. **Gradual Rollout**: Test monitoring on staging first
6. **Retention Policy**: Define how long to keep health check data
7. **Alerting Fatigue**: Adjust thresholds to reduce false positives
8. **Runbooks**: Create runbooks for common alerts

---

## Support

For monitoring issues:
- Check health-check.sh logs
- Review app error logs
- Contact your hosting provider
- Open issue on GitHub

Last Updated: January 4, 2026
