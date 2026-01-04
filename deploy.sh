#!/bin/bash

###############################################################################
# GetSuperCP Production Deployment Script
# 
# This script automates the complete deployment process for GetSuperCP
# to production servers.
#
# Usage: ./deploy.sh [environment] [actions]
# Examples:
#   ./deploy.sh production all
#   ./deploy.sh staging verify
#   ./deploy.sh production backup,migrate,build
###############################################################################

set -euo pipefail

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT="${1:-production}"
ACTIONS="${2:-all}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="${SCRIPT_DIR}/logs/deployment-$(date +%Y%m%d-%H%M%S).log"
BACKUP_DIR="${SCRIPT_DIR}/backups/$(date +%Y%m%d-%H%M%S)"

# Ensure logs directory exists
mkdir -p "${SCRIPT_DIR}/logs"
mkdir -p "${SCRIPT_DIR}/backups"

###############################################################################
# Logging Functions
###############################################################################

log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $*" | tee -a "$LOG_FILE"
}

log_success() {
    echo -e "${GREEN}✓${NC} $*" | tee -a "$LOG_FILE"
}

log_error() {
    echo -e "${RED}✗${NC} $*" | tee -a "$LOG_FILE"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $*" | tee -a "$LOG_FILE"
}

###############################################################################
# Pre-Deployment Checks
###############################################################################

check_prerequisites() {
    log "Checking prerequisites..."
    
    local missing_tools=()
    
    for tool in php composer npm git; do
        if ! command -v "$tool" &> /dev/null; then
            missing_tools+=("$tool")
        fi
    done
    
    if [ ${#missing_tools[@]} -ne 0 ]; then
        log_error "Missing required tools: ${missing_tools[*]}"
        return 1
    fi
    
    log_success "All required tools found"
    
    # Check PHP version
    local php_version=$(php -r 'echo PHP_VERSION;')
    log "PHP version: $php_version"
    
    if ! php -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' 2>/dev/null; then
        log_error "PHP 8.4+ required (found: $php_version)"
        return 1
    fi
    
    log_success "PHP version check passed"
    
    # Check git status
    if [ -n "$(git status --porcelain)" ]; then
        log_warning "Uncommitted changes detected"
        git status --short | head -20
        read -p "Continue with uncommitted changes? (y/N) " -n 1 -r
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            return 1
        fi
    fi
    
    return 0
}

###############################################################################
# Environment Setup
###############################################################################

setup_environment() {
    log "Setting up environment: $ENVIRONMENT"
    
    local env_file="${SCRIPT_DIR}/.env.${ENVIRONMENT}"
    
    if [ ! -f "$env_file" ]; then
        log_error "Environment file not found: $env_file"
        log "Creating template from .env.example..."
        
        if [ -f "${SCRIPT_DIR}/.env.example" ]; then
            cp "${SCRIPT_DIR}/.env.example" "$env_file"
            log_warning "Created $env_file - please update with production values"
            return 1
        else
            log_error "No .env.example found"
            return 1
        fi
    fi
    
    cp "$env_file" "${SCRIPT_DIR}/.env"
    log_success "Environment configured"
    
    return 0
}

###############################################################################
# Dependency Installation
###############################################################################

install_dependencies() {
    log "Installing dependencies..."
    
    log "Installing PHP dependencies with composer..."
    cd "$SCRIPT_DIR"
    composer install --no-dev --optimize-autoloader --prefer-dist
    log_success "PHP dependencies installed"
    
    log "Installing frontend dependencies..."
    npm ci
    log_success "Frontend dependencies installed"
    
    return 0
}

###############################################################################
# Database Operations
###############################################################################

backup_database() {
    log "Creating database backup..."
    
    mkdir -p "$BACKUP_DIR"
    
    local db_backup_file="$BACKUP_DIR/database-$(date +%Y%m%d-%H%M%S).sql"
    
    # Get database type from .env
    local db_connection=$(grep "^DB_CONNECTION=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
    
    case "$db_connection" in
        mysql)
            local db_host=$(grep "^DB_HOST=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            local db_port=$(grep "^DB_PORT=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2 | tr -d '\r')
            local db_name=$(grep "^DB_DATABASE=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            local db_user=$(grep "^DB_USERNAME=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            local db_pass=$(grep "^DB_PASSWORD=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            
            mysqldump --host="$db_host" --port="$db_port" \
                     --user="$db_user" --password="$db_pass" \
                     "$db_name" > "$db_backup_file" 2>>"$LOG_FILE" || {
                log_error "Database backup failed"
                return 1
            }
            ;;
        pgsql)
            local db_host=$(grep "^DB_HOST=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            local db_port=$(grep "^DB_PORT=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2 | tr -d '\r')
            local db_name=$(grep "^DB_DATABASE=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            local db_user=$(grep "^DB_USERNAME=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            
            PGPASSWORD=$(grep "^DB_PASSWORD=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2) \
            pg_dump --host="$db_host" --port="$db_port" \
                   --username="$db_user" "$db_name" > "$db_backup_file" 2>>"$LOG_FILE" || {
                log_error "Database backup failed"
                return 1
            }
            ;;
        sqlite)
            local db_path=$(grep "^DB_DATABASE=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            cp "$db_path" "$db_backup_file" || {
                log_error "Database backup failed"
                return 1
            }
            ;;
        *)
            log_warning "Unknown database connection type: $db_connection"
            return 1
            ;;
    esac
    
    chmod 600 "$db_backup_file"
    log_success "Database backed up to: $db_backup_file"
    
    return 0
}

migrate_database() {
    log "Running database migrations..."
    
    cd "$SCRIPT_DIR"
    php artisan migrate --force || {
        log_error "Database migration failed"
        return 1
    }
    
    log_success "Database migrations completed"
    
    return 0
}

seed_database() {
    if [ "$ENVIRONMENT" != "production" ]; then
        log "Seeding database..."
        
        cd "$SCRIPT_DIR"
        php artisan db:seed || {
            log_warning "Database seeding encountered issues (may be expected)"
            # Don't fail on seed errors
        }
        
        log_success "Database seeding completed"
    fi
    
    return 0
}

###############################################################################
# Cache & Config
###############################################################################

optimize_application() {
    log "Optimizing application..."
    
    cd "$SCRIPT_DIR"
    
    # Clear caches
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    log_success "Application optimized"
    
    return 0
}

###############################################################################
# Frontend Build
###############################################################################

build_frontend() {
    log "Building frontend assets..."
    
    cd "$SCRIPT_DIR"
    npm run build || {
        log_error "Frontend build failed"
        return 1
    }
    
    log_success "Frontend assets built"
    
    return 0
}

###############################################################################
# File Permissions
###############################################################################

set_permissions() {
    log "Setting file permissions..."
    
    cd "$SCRIPT_DIR"
    
    # Set proper permissions for storage and bootstrap cache
    chmod -R 775 storage bootstrap/cache || true
    
    # Set ownership if running as sudo
    if [ -n "${SUDO_USER:-}" ]; then
        chown -R "${SUDO_USER}:${SUDO_USER}" storage bootstrap/cache || true
    fi
    
    log_success "File permissions set"
    
    return 0
}

###############################################################################
# Service Management
###############################################################################

manage_services() {
    local action="$1"
    
    log "Managing services: $action"
    
    case "$action" in
        start)
            if command -v systemctl &> /dev/null; then
                systemctl start getsuper-api || log_warning "Failed to start getsuper-api"
                systemctl start getsuper-daemon || log_warning "Failed to start getsuper-daemon"
            else
                log_warning "Systemctl not available, skipping service start"
            fi
            ;;
        stop)
            if command -v systemctl &> /dev/null; then
                systemctl stop getsuper-api || log_warning "Failed to stop getsuper-api"
                systemctl stop getsuper-daemon || log_warning "Failed to stop getsuper-daemon"
            else
                log_warning "Systemctl not available, skipping service stop"
            fi
            ;;
        restart)
            manage_services "stop"
            sleep 2
            manage_services "start"
            ;;
    esac
    
    return 0
}

###############################################################################
# Health Checks
###############################################################################

verify_deployment() {
    log "Verifying deployment..."
    
    cd "$SCRIPT_DIR"
    
    # Check artisan works
    php artisan tinker << 'EOF' || {
        log_error "Artisan verification failed"
        return 1
    }
exit();
EOF
    
    log_success "Application verification passed"
    
    # Run test suite (optional for production)
    if [ "$ENVIRONMENT" != "production" ]; then
        log "Running test suite..."
        php artisan test --parallel || {
            log_warning "Some tests failed (check manually)"
        }
    fi
    
    return 0
}

###############################################################################
# Rollback
###############################################################################

rollback_deployment() {
    log "Rolling back deployment..."
    
    cd "$SCRIPT_DIR"
    
    # Restore from backup
    if [ -d "$BACKUP_DIR" ]; then
        log "Restoring database from backup..."
        
        local latest_backup=$(ls -t "$BACKUP_DIR"/database-*.sql 2>/dev/null | head -1)
        
        if [ -n "$latest_backup" ]; then
            local db_connection=$(grep "^DB_CONNECTION=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
            
            case "$db_connection" in
                mysql)
                    local db_host=$(grep "^DB_HOST=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
                    local db_port=$(grep "^DB_PORT=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2 | tr -d '\r')
                    local db_name=$(grep "^DB_DATABASE=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
                    local db_user=$(grep "^DB_USERNAME=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
                    local db_pass=$(grep "^DB_PASSWORD=" "${SCRIPT_DIR}/.env" | cut -d'=' -f2)
                    
                    mysql --host="$db_host" --port="$db_port" \
                         --user="$db_user" --password="$db_pass" \
                         "$db_name" < "$latest_backup" || {
                        log_error "Rollback failed"
                        return 1
                    }
                    ;;
            esac
            
            log_success "Database restored from backup"
        fi
    fi
    
    log_success "Rollback completed"
    
    return 0
}

###############################################################################
# Main Deployment Flow
###############################################################################

main() {
    log "Starting GetSuperCP Deployment"
    log "Environment: $ENVIRONMENT"
    log "Actions: $ACTIONS"
    log "Log file: $LOG_FILE"
    
    # Pre-deployment checks
    check_prerequisites || {
        log_error "Pre-deployment checks failed"
        return 1
    }
    
    # Parse actions
    IFS=',' read -ra action_array <<< "$ACTIONS"
    
    for action in "${action_array[@]}"; do
        action=$(echo "$action" | xargs) # Trim whitespace
        
        case "$action" in
            all)
                setup_environment || return 1
                install_dependencies || return 1
                backup_database || return 1
                migrate_database || return 1
                seed_database || true
                optimize_application || return 1
                build_frontend || return 1
                set_permissions || return 1
                verify_deployment || return 1
                manage_services "restart" || true
                ;;
            setup)
                setup_environment || return 1
                ;;
            install)
                install_dependencies || return 1
                ;;
            backup)
                backup_database || return 1
                ;;
            migrate)
                migrate_database || return 1
                ;;
            seed)
                seed_database || return 1
                ;;
            optimize)
                optimize_application || return 1
                ;;
            build)
                build_frontend || return 1
                ;;
            permissions)
                set_permissions || return 1
                ;;
            verify)
                verify_deployment || return 1
                ;;
            rollback)
                rollback_deployment || return 1
                ;;
            *)
                log_error "Unknown action: $action"
                return 1
                ;;
        esac
    done
    
    log_success "Deployment completed successfully!"
    log "Log file: $LOG_FILE"
    
    return 0
}

# Run main with error handling
if main; then
    exit 0
else
    log_error "Deployment failed"
    exit 1
fi
