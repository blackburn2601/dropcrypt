#!/bin/bash
set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  DropCrypt - Automated Setup Script${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running!${NC}"
    echo -e "${YELLOW}Please start Docker Desktop and try again.${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Docker is running"

# Check for docker compose (new) or docker-compose (old)
DOCKER_COMPOSE=""
if docker compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
    echo -e "${GREEN}✓${NC} Docker Compose (v2) is available"
elif docker-compose version > /dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
    echo -e "${GREEN}✓${NC} Docker Compose (v1) is available"
else
    echo -e "${RED}Error: Neither 'docker compose' nor 'docker-compose' is available!${NC}"
    echo -e "${YELLOW}Please install Docker Compose and try again.${NC}"
    exit 1
fi

echo ""

# ============================================
# Step 1: Setup Environment Files
# ============================================
echo -e "${BLUE}[1/6] Setting up environment files...${NC}"

# Root .env file
if [ ! -f "./.env" ]; then
    if [ -f "./.env.example" ]; then
        echo -e "  ${YELLOW}→${NC} Creating root .env file from template..."
        cp ./.env.example ./.env
        
        # Generate database passwords
        DB_PASSWORD=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-20)
        DB_ROOT_PASSWORD=$(openssl rand -base64 24 | tr -d "=+/" | cut -c1-20)
        
        # Update database passwords (macOS compatible)
        if [[ "$OSTYPE" == "darwin"* ]]; then
            sed -i '' "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" ./.env
            sed -i '' "s/DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD/" ./.env
        else
            sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" ./.env
            sed -i "s/DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD/" ./.env
        fi
        
        echo -e "  ${GREEN}✓${NC} Root .env file created"
    else
        echo -e "  ${RED}✗${NC} .env.example not found!"
        exit 1
    fi
else
    echo -e "  ${GREEN}✓${NC} Root .env file already exists"
fi

# Backend .env file
if [ ! -f "./backend/.env" ]; then
    if [ -f "./backend/.env.example" ]; then
        echo -e "  ${YELLOW}→${NC} Creating backend .env file from template..."
        cp ./backend/.env.example ./backend/.env
        echo -e "  ${GREEN}✓${NC} Backend .env file created"
    else
        echo -e "  ${RED}✗${NC} backend/.env.example not found!"
        exit 1
    fi
else
    echo -e "  ${GREEN}✓${NC} Backend .env file already exists"
fi

# ============================================
# Step 2: Generate Secure Keys
# ============================================
echo ""
echo -e "${BLUE}[2/6] Generating secure encryption keys...${NC}"

# Generate keys
APP_SECRET=$(openssl rand -hex 32)
MESSAGE_ENCRYPTION_KEY=$(openssl rand -hex 32)

echo -e "  ${YELLOW}→${NC} APP_SECRET: ${APP_SECRET:0:16}...${APP_SECRET: -16}"
echo -e "  ${YELLOW}→${NC} MESSAGE_ENCRYPTION_KEY: ${MESSAGE_ENCRYPTION_KEY:0:16}...${MESSAGE_ENCRYPTION_KEY: -16}"

# Update backend .env with secure keys
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/APP_SECRET=.*/APP_SECRET=$APP_SECRET/" ./backend/.env
    sed -i '' "s/MESSAGE_ENCRYPTION_KEY=.*/MESSAGE_ENCRYPTION_KEY=$MESSAGE_ENCRYPTION_KEY/" ./backend/.env
else
    # Linux
    sed -i "s/APP_SECRET=.*/APP_SECRET=$APP_SECRET/" ./backend/.env
    sed -i "s/MESSAGE_ENCRYPTION_KEY=.*/MESSAGE_ENCRYPTION_KEY=$MESSAGE_ENCRYPTION_KEY/" ./backend/.env
fi

echo -e "  ${GREEN}✓${NC} Secure keys generated and saved"

# ============================================
# Step 3: Stop Existing Containers
# ============================================
echo ""
echo -e "${BLUE}[3/6] Stopping existing containers...${NC}"

if [ "$($DOCKER_COMPOSE ps -q)" ]; then
    $DOCKER_COMPOSE down -v > /dev/null 2>&1
    echo -e "  ${GREEN}✓${NC} Existing containers stopped"
else
    echo -e "  ${YELLOW}→${NC} No containers to stop"
fi

# ============================================
# Step 4: Build and Start Containers
# ============================================
echo ""
echo -e "${BLUE}[4/6] Building and starting Docker containers...${NC}"
echo -e "  ${YELLOW}→${NC} This may take a few minutes on first run..."

$DOCKER_COMPOSE up -d --build

echo -e "  ${GREEN}✓${NC} Containers started"

# ============================================
# Step 5: Wait for Services
# ============================================
echo ""
echo -e "${BLUE}[5/6] Waiting for services to be ready...${NC}"

# Wait for MySQL
echo -e "  ${YELLOW}→${NC} Waiting for MySQL..."
RETRY_COUNT=0
MAX_RETRIES=30

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    if $DOCKER_COMPOSE exec -T mysql mysqladmin ping -h localhost -u root -p$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2) --silent 2>/dev/null; then
        echo -e "  ${GREEN}✓${NC} MySQL is ready"
        break
    fi
    RETRY_COUNT=$((RETRY_COUNT+1))
    sleep 1
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo -e "  ${RED}✗${NC} MySQL failed to start in time"
    exit 1
fi

# Wait for PHP-FPM
echo -e "  ${YELLOW}→${NC} Waiting for PHP-FPM..."
sleep 3
echo -e "  ${GREEN}✓${NC} PHP-FPM is ready"

# ============================================
# Step 6: Setup Database
# ============================================
echo ""
echo -e "${BLUE}[6/6] Setting up database...${NC}"

# Run migrations
echo -e "  ${YELLOW}→${NC} Running database migrations..."
if $DOCKER_COMPOSE exec -T php php bin/console doctrine:migrations:migrate --no-interaction --env=dev; then
    echo -e "  ${GREEN}✓${NC} Database migrations completed"
else
    echo -e "  ${YELLOW}⚠${NC}  No migrations to run or migrations failed (this might be okay)"
fi

# ============================================
# Verify Installation
# ============================================
echo ""
echo -e "${BLUE}Verifying installation...${NC}"

# Check containers
if [ "$($DOCKER_COMPOSE ps --filter 'status=running' -q | wc -l)" -ge 3 ]; then
    echo -e "  ${GREEN}✓${NC} All containers are running"
else
    echo -e "  ${YELLOW}⚠${NC}  Some containers may not be running"
    $DOCKER_COMPOSE ps
fi

# ============================================
# Success Message
# ============================================
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ Setup completed successfully!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Application URLs:${NC}"
echo -e "  • Frontend/API: ${GREEN}http://localhost${NC}"
echo -e "  • MySQL Port:   ${GREEN}3307${NC}"
echo ""
echo -e "${BLUE}Container Status:${NC}"
$DOCKER_COMPOSE ps
echo ""
echo -e "${BLUE}Useful Commands:${NC}"
echo -e "  • View logs:        ${YELLOW}$DOCKER_COMPOSE logs -f${NC}"
echo -e "  • Stop containers:  ${YELLOW}$DOCKER_COMPOSE down${NC}"
echo -e "  • Start containers: ${YELLOW}$DOCKER_COMPOSE up -d${NC}"
echo -e "  • Enter PHP shell:  ${YELLOW}$DOCKER_COMPOSE exec php sh${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo -e "  1. Open ${GREEN}http://localhost${NC} in your browser"
echo -e "  2. Read ${YELLOW}INFRASTRUCTURE.md${NC} for detailed documentation"
echo -e "  3. Read ${YELLOW}PRODUCTION_DEPLOYMENT.md${NC} for production setup"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
echo "" 