#!/bin/bash

#######################################
# GetSuperCP Deployment Verification
#######################################

echo "=========================================="
echo "GetSuperCP Production Deployment Check"
echo "=========================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running in GetSuperCP directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}✗ Not in GetSuperCP directory. Please run from project root.${NC}"
    exit 1
fi

echo -e "${YELLOW}Checking system requirements...${NC}"
echo ""

# 1. Check PHP version
PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+' | head -1)
echo -n "PHP Version: "
if (( $(echo "$PHP_VERSION >= 8.4" | bc -l) )); then
    echo -e "${GREEN}✓ $PHP_VERSION${NC}"
else
    echo -e "${RED}✗ $PHP_VERSION (requires 8.4+)${NC}"
fi

# 2. Check required PHP extensions
EXTENSIONS=("curl" "gd" "json" "mbstring" "openssl" "pdo" "xml")
echo ""
echo -n "PHP Extensions: "
MISSING=0
for ext in "${EXTENSIONS[@]}"; do
    if ! php -m | grep -q "$ext"; then
        echo -e "${RED}Missing $ext${NC}"
        MISSING=$((MISSING + 1))
    fi
done
if [ $MISSING -eq 0 ]; then
    echo -e "${GREEN}✓ All required${NC}"
fi

# 3. Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node -v | cut -d'v' -f2)
    echo -n "Node.js Version: "
    echo -e "${GREEN}✓ $NODE_VERSION${NC}"
else
    echo -e "${RED}✗ Node.js not installed${NC}"
fi

# 4. Check npm
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm -v)
    echo -n "npm Version: "
    echo -e "${GREEN}✓ $NPM_VERSION${NC}"
else
    echo -e "${RED}✗ npm not installed${NC}"
fi

# 5. Check Composer
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | grep -oP 'Composer \K[0-9.]+')
    echo -n "Composer Version: "
    echo -e "${GREEN}✓ $COMPOSER_VERSION${NC}"
else
    echo -e "${RED}✗ Composer not installed${NC}"
fi

echo ""
echo -e "${YELLOW}Checking application files...${NC}"
echo ""

# 6. Check key files
FILES=("artisan" "composer.json" "package.json" "tailwind.config.js" "tsconfig.json" "vite.config.js")
for file in "${FILES[@]}"; do
    echo -n "$file: "
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${RED}✗${NC}"
    fi
done

echo ""
echo -e "${YELLOW}Checking Laravel setup...${NC}"
echo ""

# 7. Check .env file
echo -n ".env file: "
if [ -f ".env" ]; then
    echo -e "${GREEN}✓${NC}"
    if grep -q "APP_KEY=" .env && grep -q "APP_ENV=" .env; then
        echo -n "  - APP_KEY: "
        if grep "APP_KEY=" .env | grep -q "base64:"; then
            echo -e "${GREEN}✓ Configured${NC}"
        else
            echo -e "${RED}✗ Not set${NC}"
        fi
    fi
else
    echo -e "${RED}✗${NC}"
fi

# 8. Check database
echo -n "Database: "
if [ -f "database/database.sqlite" ]; then
    echo -e "${GREEN}✓ SQLite found${NC}"
else
    echo -e "${YELLOW}⚠ SQLite not found (may be MySQL/PostgreSQL)${NC}"
fi

# 9. Check storage directory
echo -n "Storage directory: "
if [ -d "storage" ] && [ -w "storage" ]; then
    echo -e "${GREEN}✓ Writable${NC}"
else
    echo -e "${RED}✗ Not writable${NC}"
fi

# 10. Check bootstrap directory
echo -n "Bootstrap cache: "
if [ -d "bootstrap/cache" ] && [ -w "bootstrap/cache" ]; then
    echo -e "${GREEN}✓ Writable${NC}"
else
    echo -e "${RED}✗ Not writable${NC}"
fi

echo ""
echo -e "${YELLOW}Checking build artifacts...${NC}"
echo ""

# 11. Check if frontend is built
echo -n "Frontend build: "
if [ -d "public/build" ]; then
    FILE_COUNT=$(find public/build -type f | wc -l)
    echo -e "${GREEN}✓ Built ($FILE_COUNT files)${NC}"
else
    echo -e "${YELLOW}⚠ Not built (run: npm run build)${NC}"
fi

echo ""
echo -e "${YELLOW}Quick Laravel checks...${NC}"
echo ""

# 12. Test Artisan
echo -n "Artisan command: "
if php artisan about > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC}"
else
    echo -e "${RED}✗${NC}"
fi

# 13. Check routes
echo -n "API Routes: "
ROUTE_COUNT=$(php artisan route:list 2>/dev/null | tail -1 | grep -oP '\d+' | head -1)
if [ ! -z "$ROUTE_COUNT" ]; then
    echo -e "${GREEN}✓ $ROUTE_COUNT routes${NC}"
else
    echo -e "${YELLOW}⚠ Unable to count${NC}"
fi

# 14. Run tests
echo ""
echo -e "${YELLOW}Running test suite...${NC}"
echo ""
php artisan test --no-output 2>/dev/null
TEST_RESULT=$?
if [ $TEST_RESULT -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passing${NC}"
else
    echo -e "${YELLOW}⚠ Some tests failing (review before deployment)${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}Deployment Check Complete${NC}"
echo "=========================================="
echo ""
echo "Next steps for production:"
echo "1. Review .env configuration"
echo "2. Set APP_ENV=production and APP_DEBUG=false"
echo "3. Configure database (MySQL/PostgreSQL recommended)"
echo "4. Set up SMTP email configuration"
echo "5. Configure web server (Nginx/Apache)"
echo "6. Set up SSL certificate (Let's Encrypt)"
echo "7. Deploy to your server"
echo ""
echo "See PRODUCTION_DEPLOYMENT.md for detailed instructions."
echo ""
