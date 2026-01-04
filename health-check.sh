#!/bin/bash

###############################################################################
# GetSuperCP Health Check Script
#
# Monitors application health, performance, and availability
# Can be run periodically via cron or monitoring system
###############################################################################

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STATUS_FILE="${SCRIPT_DIR}/storage/health_status.json"
LOG_FILE="${SCRIPT_DIR}/storage/logs/health-check.log"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

mkdir -p "$(dirname "$LOG_FILE")"

###############################################################################
# Logging
###############################################################################

log_check() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $*" >> "$LOG_FILE"
}

###############################################################################
# Health Checks
###############################################################################

check_application_running() {
    local status="OK"
    local message="Application is running"
    
    if ! php artisan tinker --execute="echo 'OK';" | grep -q "OK"
    then
        status="CRITICAL"
        message="Application is not responding"
    fi
    
    echo "application|$status|$message"
    log_check "Application check: $status - $message"
}

check_database_connection() {
    local status="OK"
    local message="Database connected"
    
    if ! php artisan tinker --execute="\DB::connection()->getPdo(); echo 'OK';" | grep -q "OK"
    then
        status="CRITICAL"
        message="Cannot connect to database"
    fi
    
    echo "database|$status|$message"
    log_check "Database check: $status - $message"
}

check_cache_system() {
    local status="OK"
    local message="Cache system operational"
    
    if ! php artisan tinker --execute="\Cache::put('health_check', time(), 60); echo 'OK';" | grep -q "OK"
    then
        status="WARNING"
        message="Cache system may have issues"
    fi
    
    echo "cache|$status|$message"
    log_check "Cache check: $status - $message"
}

check_queue_system() {
    local status="OK"
    local message="Queue system ready"
    
    # Check if any jobs are failing
    local failed_count=$(php artisan tinker --execute="echo \DB::table('failed_jobs')->count();" | tail -n 1 | tr -d '\r\n' | grep -o '[0-9]\+' || echo "0")
    
    if [ "$failed_count" -gt 10 ]; then
        status="WARNING"
        message="$failed_count failed jobs in queue"
    fi
    
    echo "queue|$status|$message"
    log_check "Queue check: $status - $message"
}

check_disk_space() {
    local status="OK"
    local message="Disk space adequate"
    local storage_path="${SCRIPT_DIR}/storage"
    
    local available=$(df -P "$storage_path" | awk 'NR==2 {print $4}')
    # Ensure available is a number
    available=$(echo "$available" | tr -d '\r\n' | grep -o '[0-9]\+' | head -n 1 || echo "2000000")

    local threshold=$((1024 * 1024)) # 1GB in KB
    
    if [ "$available" -lt "$threshold" ]; then
        status="CRITICAL"
        message="Low disk space: $(numfmt --to=iec $((available * 1024)) 2>/dev/null || echo "${available}KB")"
    fi
    
    echo "disk_space|$status|$message"
    log_check "Disk space check: $status - $message"
}

check_http_endpoint() {
    local status="OK"
    local message="HTTP endpoint responding"
    local url="${1:-http://localhost}"
    
    if ! curl -sf "$url/health" > /dev/null 2>&1; then
        status="WARNING"
        message="HTTP endpoint not responding"
    fi
    
    echo "http|$status|$message"
    log_check "HTTP check: $status - $message"
}

check_ssl_certificates() {
    local status="OK"
    local message="SSL certificates valid"
    
    # Check for expiring certificates
    local expiring=$(php artisan tinker --execute="echo \App\Models\SslCertificate::whereBetween('expires_at', [now(), now()->addDays(30)])->count();" | tail -n 1 | tr -d '\r\n' | grep -o '[0-9]\+' || echo "0")
    
    if [ "$expiring" -gt 0 ]; then
        status="WARNING"
        message="$expiring SSL certificates expiring within 30 days"
    fi
    
    echo "ssl_certificates|$status|$message"
    log_check "SSL certificates check: $status - $message"
}

check_backups() {
    local status="OK"
    local message="Recent backups available"
    
    # Check for backups in last 7 days
    local recent_backups=$(php artisan tinker --execute="echo \App\Models\Backup::where('created_at', '>', now()->subDays(7))->count();" | tail -n 1 | tr -d '\r\n' | grep -o '[0-9]\+' || echo "0")
    
    if [ "$recent_backups" -eq 0 ]; then
        status="WARNING"
        message="No recent backups found"
    fi
    
    echo "backups|$status|$message"
    log_check "Backups check: $status - $message"
}

check_security_alerts() {
    local status="OK"
    local message="No active security alerts"
    
    # Check for unresolved security issues
    local alerts=$(php artisan tinker --execute="echo \App\Models\MonitoringAlert::where('resolved', false)->where('type', 'security')->count();" | tail -n 1 | tr -d '\r\n' | grep -o '[0-9]\+' || echo "0")
    
    if [ "$alerts" -gt 0 ]; then
        status="WARNING"
        message="$alerts active security alerts"
    fi
    
    echo "security_alerts|$status|$message"
    log_check "Security alerts check: $status - $message"
}

###############################################################################
# Generate JSON Status Report
###############################################################################

generate_status_report() {
    local checks=(
        "$(check_application_running)"
        "$(check_database_connection)"
        "$(check_cache_system)"
        "$(check_queue_system)"
        "$(check_disk_space)"
        "$(check_ssl_certificates)"
        "$(check_backups)"
        "$(check_security_alerts)"
    )
    
    local overall_status="OK"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Parse checks and determine overall status
    local json_checks="{"
    for check in "${checks[@]}"; do
        IFS='|' read -r name status message <<< "$check"
        
        # Update overall status (CRITICAL > WARNING > OK)
        if [ "$status" = "CRITICAL" ]; then
            overall_status="CRITICAL"
        elif [ "$status" = "WARNING" ] && [ "$overall_status" != "CRITICAL" ]; then
            overall_status="WARNING"
        fi
        
        json_checks+="\"$name\":{\"status\":\"$status\",\"message\":\"$message\"},"
    done
    json_checks="${json_checks%,}}"
    
    # Generate JSON
    cat > "$STATUS_FILE" << EOF
{
  "timestamp": "$timestamp",
  "overall_status": "$overall_status",
  "checks": $json_checks
}
EOF
    
    log_check "Status report generated: $overall_status"
}

###############################################################################
# Send Alerts
###############################################################################

send_alerts() {
    if [ ! -f "$STATUS_FILE" ]; then
        return
    fi
    
    local overall_status=$(grep -o '"overall_status": "[^"]*"' "$STATUS_FILE" | cut -d'"' -f4)
    
    if [ "$overall_status" = "CRITICAL" ]; then
        # In production, send alerts via email, Slack, PagerDuty, etc.
        log_check "CRITICAL alert conditions detected"
        
        # Example: Send email alert
        # mail -s "GetSuperCP Critical Alert" admin@example.com < "$STATUS_FILE"
    fi
}

###############################################################################
# Main
###############################################################################

main() {
    log_check "Starting health checks..."
    generate_status_report
    send_alerts
    log_check "Health checks completed"
    
    # Exit with appropriate code
    if grep -q '"overall_status": "CRITICAL"' "$STATUS_FILE"; then
        exit 2
    elif grep -q '"overall_status": "WARNING"' "$STATUS_FILE"; then
        exit 1
    fi
    
    exit 0
}

main "$@"
