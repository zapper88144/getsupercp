#!/bin/bash

###############################################################################
# phpMyAdmin Setup Verification Script
# Verifies all components are correctly installed and configured
###############################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Counters
PASSED=0
FAILED=0

# Helper functions
check_pass() {
    echo -e "${GREEN}✓${NC} $1"
    ((PASSED++))
}

check_fail() {
    echo -e "${RED}✗${NC} $1"
    ((FAILED++))
}

check_warn() {
    echo -e "${YELLOW}⚠${NC} $1"
}

check_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

header() {
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════${NC}"
    echo ""
}

# Main checks
header "phpMyAdmin Setup Verification"

# Check 1: phpMyAdmin directory
echo "Checking phpMyAdmin installation..."
if [ -d "/home/super/phpmyadmin" ]; then
    check_pass "phpMyAdmin directory exists"
else
    check_fail "phpMyAdmin directory not found at /home/super/phpmyadmin"
fi

# Check 2: Configuration file
if [ -f "/home/super/phpmyadmin/config.inc.php" ]; then
    check_pass "Configuration file exists"
else
    check_fail "Configuration file not found"
fi

# Check 3: Index file
if [ -f "/home/super/phpmyadmin/index.php" ]; then
    check_pass "phpMyAdmin index.php exists"
else
    check_fail "phpMyAdmin index.php not found"
fi

# Check 4: Tmp directory is writable
if [ -w "/home/super/phpmyadmin/tmp" ]; then
    check_pass "Tmp directory is writable"
else
    check_warn "Tmp directory is not writable (may cause issues)"
fi

# Check 5: Laravel config file
echo ""
echo "Checking Laravel integration..."
if [ -f "config/phpmyadmin.php" ]; then
    check_pass "Laravel config file exists"
else
    check_fail "Laravel config file not found"
fi

# Check 6: Controller
if [ -f "app/Http/Controllers/PhpMyAdminController.php" ]; then
    check_pass "PhpMyAdminController exists"
else
    check_fail "PhpMyAdminController not found"
fi

# Check 7: Middleware
if [ -f "app/Http/Middleware/VerifyPhpMyAdminAccess.php" ]; then
    check_pass "VerifyPhpMyAdminAccess middleware exists"
else
    check_fail "VerifyPhpMyAdminAccess middleware not found"
fi

# Check 8: Policy
if [ -f "app/Policies/PhpMyAdminPolicy.php" ]; then
    check_pass "PhpMyAdminPolicy exists"
else
    check_fail "PhpMyAdminPolicy not found"
fi

# Check 9: Routes
echo ""
echo "Checking routes..."
if php artisan route:list 2>/dev/null | grep -q "admin/database/manager"; then
    check_pass "Database manager route registered"
else
    check_fail "Database manager route not registered"
fi

if php artisan route:list 2>/dev/null | grep -q "api/phpmyadmin"; then
    check_pass "API routes registered"
else
    check_fail "API routes not registered"
fi

# Check 10: Environment configuration
echo ""
echo "Checking environment configuration..."
if grep -q "PHPMYADMIN_ENABLED" .env; then
    ENABLED=$(grep "PHPMYADMIN_ENABLED" .env | cut -d= -f2)
    if [ "$ENABLED" = "true" ]; then
        check_pass "phpMyAdmin is enabled in .env"
    else
        check_warn "phpMyAdmin is disabled in .env (set PHPMYADMIN_ENABLED=true)"
    fi
else
    check_warn "PHPMYADMIN_ENABLED not in .env"
fi

if grep -q "PHPMYADMIN_PATH" .env; then
    check_pass "PHPMYADMIN_PATH configured"
else
    check_warn "PHPMYADMIN_PATH not in .env"
fi

# Check 11: Database connection
echo ""
echo "Checking database connectivity..."
if php artisan tinker <<'EOF' 2>/dev/null | grep -q "1$"
try { DB::connection()->getPdo(); echo "1"; } catch (Exception $e) { echo "0"; }
exit;
EOF
then
    check_pass "Database connection successful"
else
    check_fail "Cannot connect to database"
fi

# Check 12: User table
if php artisan tinker <<'EOF' 2>/dev/null | grep -q "1$"
try { 
    if (Schema::hasTable('users')) { echo "1"; } else { echo "0"; }
} catch (Exception $e) { echo "0"; }
exit;
EOF
then
    check_pass "Users table exists"
else
    check_warn "Users table not found"
fi

# Check 13: Admin user
if php artisan tinker <<'EOF' 2>/dev/null | grep -q "1$"
try {
    $admin = \App\Models\User::where('is_admin', true)->first();
    echo $admin ? "1" : "0";
} catch (Exception $e) { echo "0"; }
exit;
EOF
then
    check_pass "Admin user exists"
else
    check_warn "No admin user found (required to access phpMyAdmin)"
fi

# Check 14: Installation script
echo ""
echo "Checking installation script..."
if [ -f "install-phpmyadmin.sh" ]; then
    check_pass "Installation script exists"
    if [ -x "install-phpmyadmin.sh" ]; then
        check_pass "Installation script is executable"
    else
        check_warn "Installation script is not executable (run: chmod +x install-phpmyadmin.sh)"
    fi
else
    check_fail "Installation script not found"
fi

# Check 15: Documentation
echo ""
echo "Checking documentation..."
if [ -f "PHPMYADMIN_QUICK_START.md" ]; then
    check_pass "Quick start guide exists"
else
    check_fail "Quick start guide not found"
fi

if [ -f "PHPMYADMIN_INTEGRATION.md" ]; then
    check_pass "Integration guide exists"
else
    check_fail "Integration guide not found"
fi

# Summary
echo ""
header "Verification Summary"

TOTAL=$((PASSED + FAILED))
echo "Results: ${GREEN}$PASSED passed${NC} / ${RED}$FAILED failed${NC} (out of $TOTAL checks)"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo ""
    echo "Next steps:"
    echo "  1. Run installation script: ${BLUE}sudo bash install-phpmyadmin.sh${NC}"
    echo "  2. Verify routes: ${BLUE}php artisan route:list | grep phpmyadmin${NC}"
    echo "  3. Login as admin and visit: ${BLUE}/admin/database/manager${NC}"
    echo ""
    exit 0
else
    echo ""
    echo -e "${RED}✗ Some checks failed. Please review above.${NC}"
    echo ""
    echo "Common issues:"
    echo "  • phpMyAdmin not installed: run install-phpmyadmin.sh"
    echo "  • Routes not registered: verify routes/web.php has phpMyAdmin routes"
    echo "  • Database not connected: check DB_* environment variables"
    echo "  • No admin user: create one in database"
    echo ""
    exit 1
fi
