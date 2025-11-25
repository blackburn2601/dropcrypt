FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    mysql-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first
COPY backend/composer.json backend/composer.lock* ./

# Install dependencies
RUN composer install --no-interaction --no-scripts

# Copy project files
COPY backend/ .

# Update dependencies and optimize autoloader
RUN composer update --no-interaction --no-scripts && \
    composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www

# Copy the entrypoint script
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Expose port 9000
EXPOSE 9000

# Use entrypoint to handle composer install on startup
ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm", "-F"] 