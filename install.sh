#!/bin/bash

#############################################
# SuperCP Full Installer Script
# Comprehensive setup for production deployment
#############################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"
SOCKET_DIR="$PROJECT_ROOT/storage/framework/sockets"
LOG_FILE="$PROJECT_ROOT/installer.log"

#############################################
# Helper Functions
#############################################

log() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[✓]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[✗]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[!]${NC} $1" | tee -a "$LOG_FILE"
}

section() {
    echo -e "\n${BLUE}═══════════════════════════════════════════${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════${NC}\n"
}

#############################################
# Dependency Checks
#############################################

check_dependencies() {
    section "Checking System Dependencies"

    local missing=()

    # Check PHP
    if ! command -v php &> /dev/null; then
        missing+=("php")
    else
        PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9.]+' | head -1)
        success "PHP $PHP_VERSION installed"
    fi

    # Check Composer
    if ! command -v composer &> /dev/null; then
        missing+=("composer")
    else
        success "Composer installed"
    fi

    # Check Node.js
    if ! command -v node &> /dev/null; then
        missing+=("node.js")
    else
        NODE_VERSION=$(node --version)
        success "Node.js $NODE_VERSION installed"
    fi

    # Check npm
    if ! command -v npm &> /dev/null; then
        missing+=("npm")
    else
        NPM_VERSION=$(npm --version)
        success "npm $NPM_VERSION installed"
    fi

    # Check Cargo/Rust
    if ! command -v cargo &> /dev/null; then
        warning "Rust/Cargo not found (optional - needed only if rebuilding Rust daemon)"
    else
        success "Rust/Cargo installed"
    fi

    # Check Git
    if ! command -v git &> /dev/null; then
        warning "Git not found (optional - needed for version control)"
    else
        success "Git installed"
    fi

    # Check sudo
    if ! command -v sudo &> /dev/null; then
        error "sudo is required but not installed"
    else
        success "sudo installed"
    fi

    # Report missing dependencies
    if [ ${#missing[@]} -gt 0 ]; then
        error "Missing required dependencies: ${missing[*]}"
    fi

    success "All required dependencies installed"
}

#############################################
# Environment Setup
#############################################

setup_env() {
    section "Setting Up Environment"

    if [ -f "$ENV_FILE" ]; then
        warning ".env file already exists"
        read -p "Do you want to regenerate it? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Skipping .env generation"
            return
        fi
    fi

    log "Copying .env.example to .env..."
    if [ -f "$PROJECT_ROOT/.env.example" ]; then
        cp "$PROJECT_ROOT/.env.example" "$ENV_FILE"
        success ".env file created"
    else
        warning ".env.example not found, creating basic .env"
        cat > "$ENV_FILE" << 'EOF'
APP_NAME="SuperCP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=cookie
QUEUE_CONNECTION=database

DAEMON_SOCKET=/var/run/super-daemon.sock
EOF
        log "Basic .env created"
    fi

    # Generate APP_KEY if not set
    if ! grep -q "APP_KEY=base64:" "$ENV_FILE"; then
        log "Generating APP_KEY..."
        cd "$PROJECT_ROOT"
        APP_KEY=$(php artisan key:generate --show)
        sed -i "s/^APP_KEY=$/APP_KEY=$APP_KEY/" "$ENV_FILE"
        success "APP_KEY generated"
    fi
}

#############################################
# Directory & Socket Setup
#############################################

setup_directories() {
    section "Setting Up Directories & Sockets"

    # Create socket directory
    log "Creating socket directory..."
    mkdir -p "$SOCKET_DIR"
    chmod 755 "$SOCKET_DIR"
    success "Socket directory created: $SOCKET_DIR"

    # Create storage directories
    log "Creating storage directories..."
    mkdir -p "$PROJECT_ROOT/storage/app"
    mkdir -p "$PROJECT_ROOT/storage/framework"
    mkdir -p "$PROJECT_ROOT/storage/logs"
    chmod -R 755 "$PROJECT_ROOT/storage"
    success "Storage directories created"

    # Create bootstrap cache
    log "Creating bootstrap cache..."
    mkdir -p "$PROJECT_ROOT/bootstrap/cache"
    chmod 755 "$PROJECT_ROOT/bootstrap/cache"
    success "Bootstrap cache created"
}

#############################################
# PHP Dependencies
#############################################

install_php_dependencies() {
    section "Installing PHP Dependencies"

    cd "$PROJECT_ROOT"

    if [ -f "composer.lock" ]; then
        log "Installing from composer.lock (for consistency)..."
        composer install --no-interaction --no-dev
    else
        log "Installing composer dependencies..."
        composer install --no-interaction
    fi

    success "PHP dependencies installed"
}

#############################################
# Node Dependencies
#############################################

install_node_dependencies() {
    section "Installing Node.js Dependencies"

    cd "$PROJECT_ROOT"

    log "Installing npm packages..."
    npm install

    success "Node.js dependencies installed"
}

#############################################
# Database Setup
#############################################

setup_database() {
    section "Setting Up Database"

    cd "$PROJECT_ROOT"

    # Create SQLite database file if needed
    if [ "$(grep '^DB_CONNECTION=' "$ENV_FILE" | cut -d= -f2)" = "sqlite" ]; then
        log "Using SQLite database"
        DB_PATH=$(grep '^DB_DATABASE=' "$ENV_FILE" | cut -d= -f2-)
        if [ -z "$DB_PATH" ] || [ "$DB_PATH" = "database.sqlite" ]; then
            DB_PATH="$PROJECT_ROOT/database/database.sqlite"
        fi
        
        log "Database file path: $DB_PATH"
        mkdir -p "$(dirname "$DB_PATH")"
        touch "$DB_PATH"
        chmod 664 "$DB_PATH"
    fi

    log "Running database migrations..."
    php artisan migrate --force

    success "Database setup completed"
}

#############################################
# Build Frontend
#############################################

build_frontend() {
    section "Building Frontend Assets"

    cd "$PROJECT_ROOT"

    log "Building with npm..."
    npm run build

    success "Frontend assets built successfully"
}

#############################################
# Rust Daemon Setup
#############################################

setup_rust_daemon() {
    section "Setting Up Rust Daemon"

    RUST_DIR="$PROJECT_ROOT/rust"

    if [ ! -d "$RUST_DIR" ]; then
        warning "Rust daemon directory not found at $RUST_DIR"
        warning "Skipping Rust daemon setup - will need to be built manually"
        return
    fi

    cd "$RUST_DIR"

    if [ ! -f "Cargo.toml" ]; then
        warning "Cargo.toml not found in rust directory"
        warning "Skipping Rust daemon compilation"
        return
    fi

    log "Building Rust daemon (this may take a few minutes)..."
    
    if cargo build --release; then
        success "Rust daemon built successfully"
        
        # Check for super-daemon binary
        if [ -f "target/release/super-daemon" ]; then
            log "Super daemon binary found at target/release/super-daemon"
            success "Rust daemon ready for deployment"
        fi
    else
        warning "Rust daemon build failed - check Rust installation and code"
    fi
}

#############################################
# Laravel Cache & Optimization
#############################################

optimize_laravel() {
    section "Optimizing Laravel"

    cd "$PROJECT_ROOT"

    log "Clearing any old cache..."
    php artisan cache:clear || true
    php artisan config:clear || true
    php artisan view:clear || true

    log "Creating optimized config cache..."
    php artisan config:cache

    log "Creating optimized autoloader..."
    composer dump-autoload --optimize

    success "Laravel optimization completed"
}

#############################################
# Permission Setup
#############################################

fix_permissions() {
    section "Setting Correct Permissions"

    log "Setting storage directory permissions..."
    chmod -R 755 "$PROJECT_ROOT/storage"
    chmod -R 755 "$PROJECT_ROOT/bootstrap/cache"

    log "Setting database directory permissions..."
    chmod -R 755 "$PROJECT_ROOT/database"

    log "Setting public directory permissions..."
    chmod -R 755 "$PROJECT_ROOT/public"

    success "Permissions configured"
}

#############################################
# Run Tests
#############################################

run_tests() {
    section "Running Test Suite"

    cd "$PROJECT_ROOT"

    read -p "Run tests? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log "Running PHPUnit tests..."
        if php artisan test; then
            success "All tests passed!"
        else
            error "Some tests failed - see output above"
        fi
    else
        log "Skipping tests"
    fi
}

#############################################
# Post-Installation
#############################################

post_install() {
    section "Post-Installation Steps"

    success "SuperCP installation completed!"
    
    echo -e "\n${BLUE}Next Steps:${NC}"
    echo "1. Review and update .env file:"
    echo "   ${YELLOW}nano $ENV_FILE${NC}"
    echo ""
    echo "2. Start the Rust daemon:"
    echo "   ${YELLOW}./rust/target/release/super-daemon${NC}"
    echo ""
    echo "3. Start the development server:"
    echo "   ${YELLOW}php artisan serve${NC}"
    echo ""
    echo "4. In another terminal, start the frontend watcher:"
    echo "   ${YELLOW}npm run dev${NC}"
    echo ""
    echo "5. Access the application:"
    echo "   ${YELLOW}http://localhost:8000${NC}"
    echo ""
    echo "Documentation: Check IMPLEMENTATION_STATUS.md"
    echo ""
}

#############################################
# Main Installation Flow
#############################################

main() {
    echo -e "${GREEN}"
    cat << "EOF"
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║              SuperCP Full Installation Wizard              ║
║                                                            ║
║              Production-Ready Control Panel                ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
EOF
    echo -e "${NC}"
    
    log "Installation starting at: $(date)"
    log "Project root: $PROJECT_ROOT"
    log "Log file: $LOG_FILE"

    # Run installation steps
    check_dependencies
    setup_env
    setup_directories
    install_php_dependencies
    install_node_dependencies
    setup_database
    build_frontend
    setup_rust_daemon
    optimize_laravel
    fix_permissions
    run_tests
    post_install

    log "Installation completed at: $(date)"
}

# Run main installation
main "$@"
