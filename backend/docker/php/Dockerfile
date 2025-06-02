FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd pdo_pgsql

# Install Composer directly
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www

# Create a non-root user
RUN useradd -m -u 1000 symfony

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Copy .env file
COPY .env ./

# Set permissions
RUN chown -R symfony:symfony .

# Switch to non-root user
USER symfony

# Install dependencies
RUN composer require symfony/runtime:^6.4 --no-interaction --with-all-dependencies
RUN composer install --no-interaction --prefer-dist

# Switch back to root for copying files
USER root

# Copy the rest of the application
COPY . .

# Set proper permissions
RUN chown -R symfony:symfony .
RUN chmod +x bin/console

# Copy the entrypoint script
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Switch back to symfony user
USER symfony

# Run composer scripts and clear cache
RUN composer run-script post-install-cmd || true
RUN php bin/console cache:clear --no-warmup || true
RUN php bin/console cache:warmup || true

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"] 