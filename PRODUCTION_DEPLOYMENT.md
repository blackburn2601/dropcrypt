# DropCrypt Production Deployment Guide

## 📋 **Pre-Deployment Checklist**

### **Phase 1: Environment Configuration** 🔧

#### 1.1 Update Root `.env` File

Create/update `.env` for production:

```env
# Production Environment
APP_ENV=prod
DATABASE_URL=mysql://dropcrypt_user:STRONG_PASSWORD_HERE@mysql:3306/dropcrypt_prod?serverVersion=8.0

# Database Configuration
DB_ROOT_PASSWORD=STRONG_ROOT_PASSWORD_HERE
DB_NAME=dropcrypt_prod
DB_USER=dropcrypt_user
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_PORT=3306  # Don't expose externally in production!
```

**Security Notes:**
- ❌ Don't use default passwords
- ❌ Don't expose MySQL port externally (remove from docker-compose.yml)
- ✅ Use strong, randomly generated passwords

**Generate Strong Passwords:**
```bash
openssl rand -base64 32  # For DB passwords
```

---

#### 1.2 Update Backend `.env` File

Update `backend/.env` for production:

```env
###> symfony/framework-bundle ###
APP_ENV=prod
APP_SECRET=YOUR_GENERATED_SECRET_HERE
APP_DEBUG=0  # CRITICAL: Disable debug mode!
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DB_USER=dropcrypt_user
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_NAME=dropcrypt_prod
DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@mysql:3306/${DB_NAME}?serverVersion=8.0
###< doctrine/doctrine-bundle ###

###> nelmio/cors-bundle ###
# Update to your production domain
CORS_ALLOW_ORIGIN='^https?://yourdomain\.com$'
###< nelmio/cors-bundle ###

###> application config ###
MESSAGE_ENCRYPTION_KEY=YOUR_GENERATED_KEY_HERE
MESSAGE_DEFAULT_EXPIRY=24
MESSAGE_MAX_LENGTH=10000
RATE_LIMIT_PER_MINUTE=30  # Lower for production
###< application config ###
```

**Generate Secure Keys:**
```bash
# APP_SECRET (must be 32 characters minimum)
openssl rand -hex 32

# MESSAGE_ENCRYPTION_KEY
openssl rand -hex 32
```

**Critical Settings:**
- ✅ `APP_ENV=prod` - Production mode
- ✅ `APP_DEBUG=0` - Disable debug mode (security!)
- ✅ Update `CORS_ALLOW_ORIGIN` to your domain
- ✅ Lower `RATE_LIMIT_PER_MINUTE` to prevent abuse

---

### **Phase 2: Docker Configuration** 🐳

#### 2.1 Update `docker-compose.yml` for Production

Create `docker-compose.prod.yml`:

```yaml
services:
  nginx:
    image: nginx:alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"  # Add HTTPS
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - ./backend/public:/var/www/public:ro
      - ./frontend/build:/var/www/html:ro
      - ./ssl:/etc/nginx/ssl:ro  # SSL certificates
    depends_on:
      - php
    networks:
      - dropcrypt
    healthcheck:
      test: ["CMD", "wget", "-q", "--spider", "http://localhost:80/"]
      interval: 30s
      timeout: 3s
      retries: 3

  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      target: production  # Use production build stage
    restart: unless-stopped
    volumes:
      - ./backend:/var/www:ro  # Read-only in production!
    environment:
      - APP_ENV=prod
      - APP_DEBUG=0
      - DATABASE_URL=${DATABASE_URL}
    depends_on:
      - mysql
    networks:
      - dropcrypt
    healthcheck:
      test: ["CMD", "php-fpm", "-t"]
      interval: 30s
      timeout: 3s
      retries: 3

  mysql:
    image: mysql:8.0
    restart: unless-stopped
    # DON'T expose port externally in production!
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
      - MYSQL_DATABASE=${DB_NAME}
      - MYSQL_USER=${DB_USER}
      - MYSQL_PASSWORD=${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./backups:/backups  # For database backups
    networks:
      - dropcrypt
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u${DB_USER}", "-p${DB_PASSWORD}"]
      interval: 10s
      timeout: 5s
      retries: 5
    command: 
      - --character-set-server=utf8mb4
      - --collation-server=utf8mb4_unicode_ci
      - --max_connections=200

networks:
  dropcrypt:
    driver: bridge

volumes:
  mysql_data:
    driver: local
```

**Key Changes:**
- ✅ `restart: unless-stopped` - Auto-restart containers
- ✅ Read-only mounts for security
- ✅ No external MySQL port
- ✅ Health checks enabled
- ✅ Production build target

---

#### 2.2 Update Dockerfile for Production

Update `docker/php/Dockerfile` with multi-stage build:

```dockerfile
FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    mysql-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files
COPY backend/composer.json backend/composer.lock* ./

# Install dependencies
RUN composer install --no-interaction --no-scripts --prefer-dist

# Copy application
COPY backend/ .

# Production optimizations
RUN composer dump-autoload --optimize --classmap-authoritative && \
    composer check-platform-reqs

# Set permissions
RUN chown -R www-data:www-data /var/www

# Copy entrypoint
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Expose port
EXPOSE 9000

# Production Stage
FROM base AS production

# Install OPcache for production
RUN docker-php-ext-enable opcache

# Create OPcache config
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Clear cache and warm up
RUN php bin/console cache:clear --env=prod --no-debug && \
    php bin/console cache:warmup --env=prod --no-debug

USER www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm", "-F"]
```

**Production Optimizations:**
- ✅ OPcache enabled (faster PHP execution)
- ✅ Classmap authoritative (faster autoloading)
- ✅ Cache warmed up at build time
- ✅ Runs as www-data user (security)

---

### **Phase 3: Nginx Configuration** 🌐

#### 3.1 Update Nginx for Production

Update `docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    # Frontend root
    root /var/www/html;
    index index.html;
    
    # SSL Configuration
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Disable access to hidden files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    # Frontend routes
    location / {
        try_files $uri $uri/ /index.html;
        expires 1h;
        add_header Cache-Control "public, must-revalidate";
    }
    
    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Backend API routes
    location /api {
        root /var/www/public;
        try_files $uri /index.php$is_args$args;
    }
    
    # PHP-FPM configuration
    location ~ ^/index\.php(/|$) {
        root /var/www/public;
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        
        # Production settings
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        
        internal;
    }
    
    # Deny access to other PHP files
    location ~ \.php$ {
        return 404;
    }
    
    # Enable gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript 
               application/json application/javascript application/xml+rss 
               application/rss+xml font/truetype font/opentype 
               application/vnd.ms-fontobject image/svg+xml;
    
    # Rate limiting (basic)
    limit_req_zone $binary_remote_addr zone=api_limit:10m rate=30r/m;
    location /api/messages/create {
        limit_req zone=api_limit burst=5 nodelay;
        try_files $uri /index.php$is_args$args;
    }
    
    # Logs
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;
}
```

**Production Features:**
- ✅ HTTPS/SSL enabled
- ✅ HTTP → HTTPS redirect
- ✅ Security headers
- ✅ Asset caching
- ✅ Gzip compression
- ✅ Rate limiting
- ✅ Hidden files blocked

---

### **Phase 4: SSL/HTTPS Setup** 🔒

#### 4.1 Get SSL Certificate (Let's Encrypt)

**Option A: Using Certbot (Recommended)**

```bash
# On your production server
sudo apt-get update
sudo apt-get install certbot

# Get certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Certificates will be in:
# /etc/letsencrypt/live/yourdomain.com/fullchain.pem
# /etc/letsencrypt/live/yourdomain.com/privkey.pem

# Copy to project
mkdir -p ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem ssl/

# Set permissions
sudo chown $USER:$USER ssl/*.pem
```

**Option B: Manual Certificate**
- Purchase SSL certificate from provider
- Place `fullchain.pem` and `privkey.pem` in `ssl/` directory

**Auto-Renewal (Let's Encrypt):**
```bash
# Add to crontab
sudo crontab -e

# Add this line (checks daily, renews if needed)
0 3 * * * certbot renew --quiet --deploy-hook "docker compose restart nginx"
```

---

### **Phase 5: Database Setup** 💾

#### 5.1 Initial Database Setup

```bash
# Start production containers
docker compose -f docker-compose.prod.yml up -d

# Wait for MySQL to be ready
docker compose -f docker-compose.prod.yml exec mysql \
    mysqladmin ping -h localhost -u root -p${DB_ROOT_PASSWORD}

# Run migrations
docker compose -f docker-compose.prod.yml exec php \
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

#### 5.2 Database Backup Strategy

**Create Backup Script (`scripts/backup-db.sh`):**

```bash
#!/bin/bash
set -e

BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)
FILENAME="dropcrypt_backup_${DATE}.sql.gz"

# Create backup
docker compose -f docker-compose.prod.yml exec -T mysql \
    mysqldump -u root -p${DB_ROOT_PASSWORD} dropcrypt_prod \
    | gzip > "${BACKUP_DIR}/${FILENAME}"

# Keep only last 30 days
find ${BACKUP_DIR} -name "dropcrypt_backup_*.sql.gz" -mtime +30 -delete

echo "Backup created: ${FILENAME}"
```

**Schedule Backups:**
```bash
# Add to crontab
0 2 * * * /path/to/scripts/backup-db.sh
```

---

### **Phase 6: Security Hardening** 🛡️

#### 6.1 Security Checklist

**Environment:**
- [ ] `APP_ENV=prod` in all .env files
- [ ] `APP_DEBUG=0` (CRITICAL!)
- [ ] Strong, unique passwords for all services
- [ ] `APP_SECRET` and `MESSAGE_ENCRYPTION_KEY` generated with `openssl rand -hex 32`
- [ ] CORS_ALLOW_ORIGIN set to your domain only

**Docker:**
- [ ] Containers run as non-root users
- [ ] Read-only file system where possible
- [ ] No unnecessary exposed ports
- [ ] Health checks enabled
- [ ] Resource limits set

**Web Server:**
- [ ] HTTPS/SSL enabled with valid certificate
- [ ] HTTP redirects to HTTPS
- [ ] Security headers configured
- [ ] Rate limiting enabled
- [ ] WebProfiler disabled (automatic in prod mode)

**Database:**
- [ ] MySQL port NOT exposed externally
- [ ] Strong root password
- [ ] Dedicated user with limited privileges
- [ ] Regular backups configured

#### 6.2 Remove Development Tools

**Update `backend/composer.json`:**

Make sure dev dependencies won't be installed:

```bash
docker compose -f docker-compose.prod.yml exec php \
    composer install --no-dev --optimize-autoloader
```

This removes:
- WebProfiler
- Maker Bundle
- Debug Bundle
- PHPUnit

---

### **Phase 7: Performance Optimization** ⚡

#### 7.1 PHP Optimizations

**Create `docker/php/php.ini` for production:**

```ini
[PHP]
memory_limit = 256M
max_execution_time = 30
upload_max_filesize = 10M
post_max_size = 10M

[opcache]
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.interned_strings_buffer = 16
```

**Update Dockerfile to copy it:**
```dockerfile
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
```

#### 7.2 Symfony Optimizations

```bash
# Clear and warm up cache (already in Dockerfile)
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# Optimize autoloader (already in Dockerfile)
composer dump-autoload --optimize --classmap-authoritative
```

#### 7.3 Frontend Build

```bash
# Build React for production (if using React)
cd frontend
npm run build

# This creates optimized, minified files in frontend/build/
```

---

### **Phase 8: Monitoring & Logging** 📊

#### 8.1 Application Logs

Logs are stored in:
- `backend/var/log/prod.log` - Symfony application logs
- `/var/log/nginx/access.log` - Nginx access logs (in container)
- `/var/log/nginx/error.log` - Nginx error logs (in container)

**View Logs:**
```bash
# Symfony logs
docker compose -f docker-compose.prod.yml exec php tail -f var/log/prod.log

# Nginx logs
docker compose -f docker-compose.prod.yml logs -f nginx

# MySQL logs
docker compose -f docker-compose.prod.yml logs -f mysql

# All logs
docker compose -f docker-compose.prod.yml logs -f
```

#### 8.2 External Monitoring (Optional)

Consider integrating:
- **Sentry** - Error tracking
- **New Relic** - APM monitoring
- **Datadog** - Infrastructure monitoring
- **Uptime Robot** - Uptime monitoring

---

## 🚀 **Deployment Steps**

### **Initial Deployment**

```bash
# 1. Clone repository on production server
git clone https://github.com/yourusername/dropcrypt.git
cd dropcrypt

# 2. Create production environment files
cp .env.example .env
cp backend/.env.example backend/.env

# Edit both .env files with production values
nano .env
nano backend/.env

# 3. Generate secure keys
openssl rand -hex 32  # Use for APP_SECRET
openssl rand -hex 32  # Use for MESSAGE_ENCRYPTION_KEY
openssl rand -base64 32  # Use for DB passwords

# 4. Setup SSL certificates
mkdir -p ssl/
# Copy your SSL certificates to ssl/ directory

# 5. Build and start containers
docker compose -f docker-compose.prod.yml up -d --build

# 6. Run database migrations
docker compose -f docker-compose.prod.yml exec php \
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 7. Verify everything is running
docker compose -f docker-compose.prod.yml ps

# 8. Test the application
curl -I https://yourdomain.com
```

### **Updates / Redeployment**

```bash
# 1. Pull latest code
git pull origin main

# 2. Rebuild containers
docker compose -f docker-compose.prod.yml up -d --build

# 3. Run new migrations (if any)
docker compose -f docker-compose.prod.yml exec php \
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 4. Clear cache (if needed)
docker compose -f docker-compose.prod.yml exec php \
    php bin/console cache:clear --env=prod
```

---

## ✅ **Post-Deployment Verification**

### Check Everything Works:

```bash
# 1. Check container status
docker compose -f docker-compose.prod.yml ps

# 2. Check application health
curl -I https://yourdomain.com

# 3. Test API endpoint
curl -X POST https://yourdomain.com/api/messages/create \
    -H "Content-Type: application/json" \
    -d '{"content":"encrypted_content","keyHash":"test_hash","expiresAt":"2025-12-31T23:59:59Z"}'

# 4. Check logs for errors
docker compose -f docker-compose.prod.yml logs --tail=50

# 5. Verify SSL
curl -v https://yourdomain.com 2>&1 | grep "SSL certificate verify"
```

---

## 🆘 **Troubleshooting**

### Common Issues:

**500 Internal Server Error:**
```bash
# Check Symfony logs
docker compose -f docker-compose.prod.yml exec php tail -100 var/log/prod.log

# Clear cache
docker compose -f docker-compose.prod.yml exec php \
    php bin/console cache:clear --env=prod --no-warmup
```

**Database Connection Error:**
```bash
# Check MySQL is running
docker compose -f docker-compose.prod.yml ps mysql

# Test connection
docker compose -f docker-compose.prod.yml exec php \
    php bin/console doctrine:query:sql "SELECT 1"
```

**SSL Certificate Issues:**
```bash
# Verify certificate files exist
ls -l ssl/

# Check Nginx config
docker compose -f docker-compose.prod.yml exec nginx nginx -t

# Restart Nginx
docker compose -f docker-compose.prod.yml restart nginx
```

---

## 📝 **Production Checklist Summary**

Before going live:

### Critical:
- [ ] `APP_ENV=prod` everywhere
- [ ] `APP_DEBUG=0` in backend/.env
- [ ] Strong passwords generated for all services
- [ ] SSL certificate installed and working
- [ ] HTTPS redirect enabled
- [ ] Database migrations run
- [ ] Backups configured
- [ ] Rate limiting enabled

### Important:
- [ ] CORS_ALLOW_ORIGIN set to production domain
- [ ] Security headers configured
- [ ] WebProfiler routes work (only in dev, disabled in prod)
- [ ] Logs are being written
- [ ] Health checks passing
- [ ] Static assets cached

### Nice to Have:
- [ ] Monitoring setup (Sentry, etc.)
- [ ] Auto-renewal for SSL certificates
- [ ] Log rotation configured
- [ ] Performance testing done
- [ ] Load testing completed

---

## 🎯 **Quick Production Deploy Commands**

```bash
# Full production deployment
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec php \
    php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Zero-downtime update (if you set it up)
docker compose -f docker-compose.prod.yml up -d --no-deps --build php
docker compose -f docker-compose.prod.yml restart nginx
```

---

Your DropCrypt application is now production-ready! 🎉

