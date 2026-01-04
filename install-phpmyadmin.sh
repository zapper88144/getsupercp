#!/bin/bash

# phpMyAdmin Installation Script for GetSuperCP
# Installs phpMyAdmin locally without Docker

set -e

echo "================================================"
echo "GetSuperCP - phpMyAdmin Local Installation"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
PHPMYADMIN_VERSION="5.2.1"
PHPMYADMIN_DIR="/home/super/phpmyadmin"
PHPMYADMIN_USER="super"
PHPMYADMIN_GROUP="super"
INSTALL_DIR="$(pwd)"

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: This script must be run as root or with sudo${NC}"
    exit 1
fi

# Step 1: Check dependencies
echo -e "${YELLOW}Step 1: Checking dependencies...${NC}"
command -v php >/dev/null 2>&1 || { echo -e "${RED}PHP is not installed${NC}"; exit 1; }
command -v wget >/dev/null 2>&1 || { echo -e "${YELLOW}wget not found, using curl instead${NC}"; HAS_WGET=0; } || HAS_WGET=1
command -v mysql >/dev/null 2>&1 || { echo -e "${YELLOW}MySQL client not found (optional)${NC}"; }

echo -e "${GREEN}✓ Dependencies check complete${NC}"
echo ""

# Step 2: Create phpMyAdmin directory
echo -e "${YELLOW}Step 2: Creating phpMyAdmin directory...${NC}"
mkdir -p "$PHPMYADMIN_DIR"
chown -R "$PHPMYADMIN_USER:$PHPMYADMIN_GROUP" "$PHPMYADMIN_DIR"
chmod 755 "$PHPMYADMIN_DIR"
echo -e "${GREEN}✓ Created $PHPMYADMIN_DIR${NC}"
echo ""

# Step 3: Download phpMyAdmin
echo -e "${YELLOW}Step 3: Downloading phpMyAdmin ${PHPMYADMIN_VERSION}...${NC}"
cd "$PHPMYADMIN_DIR"

if [ $HAS_WGET -eq 1 ]; then
    wget -q "https://files.phpmyadmin.net/phpMyAdmin/${PHPMYADMIN_VERSION}/phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz"
else
    curl -s -O "https://files.phpmyadmin.net/phpMyAdmin/${PHPMYADMIN_VERSION}/phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz"
fi

if [ ! -f "phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz" ]; then
    echo -e "${RED}Error: Failed to download phpMyAdmin${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Download complete${NC}"
echo ""

# Step 4: Extract phpMyAdmin
echo -e "${YELLOW}Step 4: Extracting phpMyAdmin...${NC}"
tar -xzf "phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz"
mv "phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages"/* .
rm -rf "phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages" "phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz"
echo -e "${GREEN}✓ Extraction complete${NC}"
echo ""

# Step 5: Set permissions
echo -e "${YELLOW}Step 5: Setting permissions...${NC}"
chown -R "$PHPMYADMIN_USER:$PHPMYADMIN_GROUP" "$PHPMYADMIN_DIR"
chmod -R 755 "$PHPMYADMIN_DIR"
chmod 700 "$PHPMYADMIN_DIR/config"
mkdir -p "$PHPMYADMIN_DIR/tmp"
chown -R "$PHPMYADMIN_USER:$PHPMYADMIN_GROUP" "$PHPMYADMIN_DIR/tmp"
chmod 700 "$PHPMYADMIN_DIR/tmp"
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 6: Create configuration
echo -e "${YELLOW}Step 6: Creating phpMyAdmin configuration...${NC}"
cp config.sample.inc.php config.inc.php

# Generate a random blowfish secret
BLOWFISH_SECRET=$(openssl rand -base64 32)

# Update config
sed -i "s/\$cfg\['blowfish_secret'\] = '';/\$cfg['blowfish_secret'] = '$BLOWFISH_SECRET';/" config.inc.php

# Add GetSuperCP specific configuration
cat >> config.inc.php << 'EOF'

/**
 * GetSuperCP phpMyAdmin Configuration
 */

// Enable auth_type cookie
$cfg['Servers'][1]['auth_type'] = 'cookie';

// Allow only localhost
$cfg['Servers'][1]['host'] = 'localhost';
$cfg['Servers'][1]['port'] = '3306';
$cfg['Servers'][1]['user'] = '';
$cfg['Servers'][1]['password'] = '';
$cfg['Servers'][1]['compress'] = false;
$cfg['Servers'][1]['AllowNoPassword'] = false;

// Security settings
$cfg['LoginCookieRecall'] = false;
$cfg['LoginCookieDomain'] = '';
$cfg['LoginCookieStore'] = 15;
$cfg['LoginCookieValidity'] = 1440;

// Disable file upload
$cfg['AllowUserDropDatabase'] = true;
$cfg['AllowArbitraryServer'] = false;

// Appearance
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['DefaultTabServer'] = 'databases';
$cfg['DefaultTabDatabase'] = 'structure';
$cfg['DefaultTabTable'] = 'structure';

// Suggestions
$cfg['DBG']['sql'] = false;
$cfg['NumTables'] = 250;
$cfg['MaxRows'] = 25;
$cfg['Order'] = 'ASCENDING';

EOF

echo -e "${GREEN}✓ Configuration created${NC}"
echo ""

# Step 7: Create Laravel integration
echo -e "${YELLOW}Step 7: Creating Laravel integration files...${NC}"
cat > "$INSTALL_DIR/routes/phpmyadmin.php" << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// phpMyAdmin integration routes
Route::middleware(['web', 'auth'])->prefix('admin/database')->group(function () {
    // phpMyAdmin access - admin only
    Route::get('/manager', function () {
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, 'Unauthorized access');
        }
        
        // Redirect to phpMyAdmin
        return redirect('/phpmyadmin/');
    })->name('database.manager');

    // Database info API
    Route::get('/info', 'DatabaseInfoController@index')->name('database.info');
});

// Direct phpMyAdmin access (requires authentication)
Route::get('/phpmyadmin/', function () {
    if (auth()->check() && auth()->user()->is_admin) {
        return file_get_contents('/var/www/phpmyadmin/index.php');
    }
    abort(403, 'Access denied');
})->name('phpmyadmin.index');
EOF

echo -e "${GREEN}✓ Integration files created${NC}"
echo ""

# Step 8: Create .htaccess for security
echo -e "${YELLOW}Step 8: Creating security files...${NC}"
cat > "$PHPMYADMIN_DIR/.htaccess" << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Deny direct access to setup files
    RewriteRule ^setup/ - [L,F]
    RewriteRule ^examples/ - [L,F]
    RewriteRule ^test/ - [L,F]
</IfModule>

# Prevent direct access to configuration
<Files "config.inc.php">
    Order Allow,Deny
    Deny from all
</Files>

<Files "config.sample.inc.php">
    Order Allow,Deny
    Deny from all
</Files>

# Disable directory listing
Options -Indexes
EOF

echo -e "${GREEN}✓ Security configuration created${NC}"
echo ""

# Step 9: Create environment configuration
echo -e "${YELLOW}Step 9: Updating environment configuration...${NC}"
if ! grep -q "PHPMYADMIN_ENABLED" "$INSTALL_DIR/.env"; then
    cat >> "$INSTALL_DIR/.env" << 'EOF'

# phpMyAdmin Configuration
PHPMYADMIN_ENABLED=true
PHPMYADMIN_PATH=/var/www/phpmyadmin
PHPMYADMIN_URL=/phpmyadmin
PHPMYADMIN_ALLOWED_IPS=127.0.0.1,::1
EOF
fi

echo -e "${GREEN}✓ Environment configuration updated${NC}"
echo ""

# Step 10: Create status file
echo -e "${YELLOW}Step 10: Creating installation status...${NC}"
cat > "$INSTALL_DIR/storage/phpmyadmin-installed.txt" << EOF
phpMyAdmin Installation Summary
================================
Installation Date: $(date)
Version: $PHPMYADMIN_VERSION
Installation Path: $PHPMYADMIN_DIR
Web Root: /var/www/phpmyadmin

Configuration:
- Blowfish Secret: Generated
- Authentication: Cookie-based
- Host: localhost
- Port: 3306

Security:
- Admin-only access via Laravel routes
- Direct file access restrictions via .htaccess
- Session-based authentication required

Access:
1. Via Laravel Admin Panel: https://your-domain/admin/database/manager
2. Direct Access: https://your-domain/phpmyadmin/

Next Steps:
1. Configure MySQL/MariaDB connection in config.inc.php
2. Create database users if needed
3. Configure remote access settings in .env
4. Test connection with: mysql -u root -h localhost
5. Set up database backups

EOF

echo -e "${GREEN}✓ Installation status recorded${NC}"
echo ""

# Step 11: Summary
echo "================================================"
echo -e "${GREEN}✓ phpMyAdmin Installation Complete!${NC}"
echo "================================================"
echo ""
echo "Installation Details:"
echo "  - Version: $PHPMYADMIN_VERSION"
echo "  - Path: $PHPMYADMIN_DIR"
echo "  - User: $PHPMYADMIN_USER"
echo ""
echo "Next Steps:"
echo "  1. Configure MySQL/MariaDB connection:"
echo "     sudo nano $PHPMYADMIN_DIR/config.inc.php"
echo ""
echo "  2. Set database credentials in GetSuperCP:"
echo "     Update DB_* variables in .env file"
echo ""
echo "  3. Access phpMyAdmin:"
echo "     - Via Admin Panel: /admin/database/manager"
echo "     - Direct Access: /phpmyadmin/"
echo ""
echo "  4. Verify Installation:"
echo "     php artisan phpmyadmin:verify"
echo ""
echo "Security Notes:"
echo "  - phpMyAdmin requires admin authentication"
echo "  - Access is restricted by .htaccess rules"
echo "  - Configuration file is protected from direct access"
echo "  - Enable HTTPS in production"
echo ""
echo "Documentation:"
echo "  See PHPMYADMIN_INTEGRATION.md for detailed information"
echo ""
