#!/bin/bash
set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}Starting DropCrypt setup...${NC}"

# Check if .env file exists, if not copy from example
if [ ! -f "./backend/.env" ]; then
    echo -e "${BLUE}Creating backend .env file...${NC}"
    cp ./backend/.env.example ./backend/.env
fi

# Generate random keys for security
echo -e "${BLUE}Generating secure keys...${NC}"
APP_SECRET=$(openssl rand -hex 32)
MESSAGE_ENCRYPTION_KEY=$(openssl rand -hex 32)

# Update the .env file with secure keys
sed -i "s/APP_SECRET=.*/APP_SECRET=$APP_SECRET/" ./backend/.env
sed -i "s/MESSAGE_ENCRYPTION_KEY=.*/MESSAGE_ENCRYPTION_KEY=$MESSAGE_ENCRYPTION_KEY/" ./backend/.env

# Start Docker containers
echo -e "${BLUE}Starting Docker containers...${NC}"
docker compose down -v
docker compose up -d --build

# Wait for database to be ready
echo -e "${BLUE}Waiting for database to be ready...${NC}"
sleep 10

# Run database migrations
echo -e "${BLUE}Running database migrations...${NC}"
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction

# Install dependencies and build assets
echo -e "${BLUE}Installing dependencies and building assets...${NC}"
docker compose exec -T php composer install
docker compose exec -T php php bin/console assets:install

echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${GREEN}The application is now available at: http://localhost${NC}" 