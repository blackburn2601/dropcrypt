#!/bin/sh
set -e

# Wait for the database to be ready
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "Waiting for database to be ready..."
  sleep 2
done

# Create database if it doesn't exist
php bin/console doctrine:database:create --if-not-exists || true

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# First argument is the command to run (usually php-fpm)
exec "$@" 