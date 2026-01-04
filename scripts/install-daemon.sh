#!/bin/bash

# SuperCP Daemon Installation Script
# This script installs the super-daemon as a systemd service.

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DAEMON_BINARY="$PROJECT_ROOT/rust/target/release/super-daemon"
SERVICE_FILE="$PROJECT_ROOT/rust/super-daemon/super-daemon.service"
SYSTEMD_DEST="/etc/systemd/system/super-daemon.service"

log() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

success() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
    exit 1
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "Please run as root (use sudo)"
fi

# Check if binary exists
if [ ! -f "$DAEMON_BINARY" ]; then
    log "Daemon binary not found at $DAEMON_BINARY. Attempting to build..."
    cd "$PROJECT_ROOT/rust"
    if ! cargo build --release; then
        error "Failed to build Rust daemon"
    fi
    success "Daemon built successfully"
fi

# Update service file with correct paths
log "Updating service file paths..."
sed -i "s|ExecStart=.*|ExecStart=$DAEMON_BINARY|" "$SERVICE_FILE"
sed -i "s|WorkingDirectory=.*|WorkingDirectory=$PROJECT_ROOT/rust|" "$SERVICE_FILE"

# Copy service file to systemd
log "Installing systemd service..."
cp "$SERVICE_FILE" "$SYSTEMD_DEST"
chmod 644 "$SYSTEMD_DEST"

# Reload systemd
log "Reloading systemd..."
systemctl daemon-reload

# Enable and start service
log "Enabling and starting super-daemon..."
systemctl enable super-daemon
systemctl restart super-daemon

# Check status
if systemctl is-active --quiet super-daemon; then
    success "super-daemon is running and enabled"
else
    error "super-daemon failed to start. Check logs with: journalctl -u super-daemon"
fi
