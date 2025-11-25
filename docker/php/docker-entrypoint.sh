#!/bin/sh
set -e

# Install/update Composer dependencies if vendor directory is missing or incomplete
if [ ! -f "vendor/autoload.php" ]; then
  echo "Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
  chown -R www-data:www-data /var/www/vendor
fi

# Execute the command (php-fpm)
exec "$@"

