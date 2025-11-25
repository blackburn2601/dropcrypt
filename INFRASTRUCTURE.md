# DropCrypt Infrastructure

## Directory Structure

```
dropcrypt/
├── .env                          # Docker Compose environment variables
├── .env.example                  # Template for .env
├── docker-compose.yml            # Main orchestration file
├── setup.sh                      # Automated setup script
│
├── docker/                       # All Docker configurations
│   ├── nginx/
│   │   └── default.conf          # Nginx web server config
│   └── php/
│       ├── Dockerfile            # PHP-FPM container definition
│       └── docker-entrypoint.sh  # Container startup script
│
├── backend/                      # Symfony API backend
│   ├── .env                      # Symfony environment variables
│   ├── .env.example              # Template for backend/.env
│   ├── .env.test                 # Test environment config
│   ├── config/                   # Symfony configuration files
│   │   ├── packages/             # Bundle configurations
│   │   ├── routes.yaml           # Master routing (attribute-based)
│   │   └── services.yaml         # Service definitions
│   ├── src/                      # PHP source code
│   │   ├── Controller/           # API controllers
│   │   ├── Entity/               # Doctrine entities
│   │   └── Repository/           # Data repositories
│   ├── public/                   # Web root
│   │   ├── index.php             # Symfony entry point
│   │   └── js/                   # Client-side JavaScript
│   ├── migrations/               # Database migrations
│   └── templates/                # Twig templates (server-rendered HTML)
│
└── frontend/                     # React frontend (placeholder)
    └── Dockerfile                # Frontend build configuration
```

## Docker Services

### nginx (Port 80)
- **Role:** Web server and reverse proxy
- **Functions:**
  - Serves frontend static files
  - Proxies `/api` requests to PHP backend
  - Handles PHP-FPM communication
- **Config:** `docker/nginx/default.conf`

### php (Port 9000)
- **Role:** PHP-FPM application server
- **Stack:** PHP 8.2 + Symfony 6.4
- **Features:**
  - Auto-installs Composer dependencies on startup
  - Runs database migrations automatically
  - Optimized with Alpine Linux
- **Config:** `docker/php/Dockerfile` + `docker/php/docker-entrypoint.sh`

### mysql (Port 3307)
- **Role:** Database server
- **Version:** MySQL 8.0
- **Storage:** Persistent volume `mysql_data`
- **Auto-setup:** Database and user created on first run

## Configuration Files

### Environment Files

#### `.env` (Root)
Docker Compose service configuration:
- `APP_ENV` - Application environment (dev/prod)
- `DATABASE_URL` - Full database connection string
- `DB_*` - Database credentials and settings
- `DB_PORT` - External MySQL port (3307)

#### `backend/.env`
Symfony application configuration:
- `APP_SECRET` - Symfony security key
- `APP_DEBUG` - Debug mode flag
- `DATABASE_URL` - Database connection (uses Docker service names)
- `CORS_ALLOW_ORIGIN` - CORS policy regex
- `MESSAGE_ENCRYPTION_KEY` - Client-side encryption key
- `MESSAGE_DEFAULT_EXPIRY` - Default message lifetime (hours)
- `RATE_LIMIT_PER_MINUTE` - API rate limiting

### Docker Files

#### `docker-compose.yml`
Service orchestration defining:
- Service dependencies (php depends on mysql)
- Volume mounts (code, configs, data)
- Network configuration
- Port mappings

#### `docker/php/Dockerfile`
PHP container build:
- Base: `php:8.2-fpm-alpine`
- Installs: MySQL PDO, Zip, Git
- Copies: Composer dependencies
- Optimizes: Autoloader optimization

#### `docker/php/docker-entrypoint.sh`
Container startup script:
- Checks for `vendor/autoload.php`
- Auto-installs Composer dependencies if missing
- Fixes file permissions for www-data user
- Starts PHP-FPM process

#### `docker/nginx/default.conf`
Web server configuration:
- Routes frontend requests to static files
- Proxies API requests to PHP-FPM
- Handles PHP script execution
- Enables gzip compression

### Symfony Configuration

#### `backend/config/packages/*.yaml`
Bundle-specific configurations:
- `doctrine.yaml` - ORM and database config
- `doctrine_migrations.yaml` - Migration settings
- `framework.yaml` - Symfony core framework
- `nelmio_cors.yaml` - CORS policy
- `routing.yaml` - Routing configuration
- `security.yaml` - Authentication and authorization
- `validator.yaml` - Validation rules

#### `backend/config/routes.yaml`
Master routing configuration (attribute-based):
```yaml
controllers:
    resource:
        path: ../src/Controller/
        namespace: App\Controller
    type: attribute
```

Routes are defined via PHP attributes in controllers.

## Setup Instructions

### Quick Setup (Automated)

```bash
./setup.sh
```

The setup script will:
1. Create environment files from templates
2. Generate secure encryption keys
3. Build Docker containers
4. Start all services
5. Run database migrations

### Manual Setup

1. **Copy environment templates:**
   ```bash
   cp .env.example .env
   cp backend/.env.example backend/.env
   ```

2. **Generate secure keys:**
   ```bash
   # Generate APP_SECRET
   openssl rand -hex 32
   
   # Generate MESSAGE_ENCRYPTION_KEY
   openssl rand -hex 32
   ```
   
   Update `.env` and `backend/.env` with generated keys.

3. **Start Docker services:**
   ```bash
   docker compose up -d --build
   ```

4. **Run database migrations:**
   ```bash
   docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. **Verify installation:**
   ```bash
   docker compose ps
   curl http://localhost
   ```

## Architecture Notes

### Current State (DRC-8-002-ModernTheme)
- **Backend:** Symfony 6.4 with both HTML templates and API endpoints
- **Frontend:** Placeholder (Twig templates in backend)
- **Database:** MySQL 8.0
- **Pattern:** Mixed (traditional server-rendered + API routes)

### Routes
- HTML routes: `/`, `/message/*` (Twig templates)
- API routes: `/message/api/*` (JSON endpoints)

### Future Migration
Other branches (main, DRC-8-001) have complete React frontends. To migrate:
1. Copy React app from another branch to `frontend/`
2. Remove Twig templates and HTML routes
3. Keep only API endpoints in MessageController
4. Update nginx config to serve React build

## Maintenance

### Update Dependencies

**PHP (Composer):**
```bash
docker compose exec php composer update
```

**Frontend (npm):**
```bash
# Once React app is added:
docker compose run --rm frontend npm update
```

### Database Operations

**Run migrations:**
```bash
docker compose exec php php bin/console doctrine:migrations:migrate
```

**Create new migration:**
```bash
docker compose exec php php bin/console make:migration
```

**Execute SQL:**
```bash
docker compose exec php php bin/console doctrine:query:sql "SELECT * FROM message"
```

**Direct MySQL access:**
```bash
docker compose exec mysql mysql -u dropcrypt -pdropcrypt dropcrypt
```

### View Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f php
docker compose logs -f nginx
docker compose logs -f mysql
```

### Rebuild Containers

```bash
# Full rebuild
docker compose down
docker compose up -d --build

# Rebuild specific service
docker compose up -d --build php
```

## Development Workflow

### Local Development
Files are mounted as volumes, so changes are reflected immediately:
- Backend code: `./backend` → `/var/www`
- Nginx config: `./docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf`

### No Restart Needed For:
- PHP code changes (auto-reloaded by PHP-FPM)
- Template changes (Twig auto-reloads in dev mode)

### Restart Required For:
- Environment variable changes: `docker compose restart php`
- Nginx config changes: `docker compose restart nginx`
- Composer dependency changes: `docker compose restart php`

## Important Notes

### Vendor Directory
- **DO NOT** commit `backend/vendor/` to git
- Auto-installed by entrypoint script on container startup
- Mounted as anonymous volume in docker-compose.yml

### Cache Directory
- `backend/var/cache/` - auto-generated, gitignored
- Clear with: `docker compose exec php php bin/console cache:clear`

### Database Persistence
- Data stored in Docker volume `mysql_data`
- Survives container restarts
- To reset: `docker compose down -v` (⚠️ deletes all data)

### Port Conflicts
- Port 80: Nginx (HTTP)
- Port 3307: MySQL (external access)
- If ports are in use, update `docker-compose.yml`

## Security Considerations

### Production Checklist
- [ ] Change all default passwords
- [ ] Generate new `APP_SECRET` and `MESSAGE_ENCRYPTION_KEY`
- [ ] Set `APP_ENV=prod` and `APP_DEBUG=0`
- [ ] Update `CORS_ALLOW_ORIGIN` to your domain
- [ ] Use HTTPS (setup reverse proxy with SSL)
- [ ] Restrict MySQL port access (remove external port or firewall)
- [ ] Review and adjust `RATE_LIMIT_PER_MINUTE`
- [ ] Enable Symfony production optimizations

### Environment File Security
- **NEVER** commit `.env` files to git
- Use `.env.example` for templates only
- Store production secrets in secure vault
- Rotate keys periodically

## Troubleshooting

### Container Won't Start
```bash
# Check logs
docker compose logs php

# Common issues:
# - Missing .env file → copy from .env.example
# - Port already in use → change ports in docker-compose.yml
# - Syntax error in config → check YAML indentation
```

### Database Connection Failed
```bash
# Check MySQL is running
docker compose ps mysql

# Check MySQL logs
docker compose logs mysql

# Verify credentials match
cat .env | grep DB_
cat backend/.env | grep DATABASE_URL
```

### Composer Dependencies Missing
```bash
# Manually install (entrypoint should auto-install)
docker compose exec php composer install --no-interaction
```

### Permission Errors
```bash
# Fix ownership (www-data user in container)
docker compose exec php chown -R www-data:www-data /var/www
```

## Refactoring History

### 2025-11-25: Infrastructure Consolidation
**Changes:**
- Consolidated 2 duplicate Dockerfiles → 1 (`docker/php/Dockerfile`)
- Removed 3 compose files → 1 (`docker-compose.yml`)
- Moved Docker configs from `backend/docker/` → `docker/php/`
- Deleted redundant route config directory (`backend/config/routes/`)
- Removed 4 empty placeholder `.gitignore` files
- Created `.env.example` templates
- Cleaned up environment files

**Benefits:**
- Single source of truth for Docker configs
- All infrastructure in central `docker/` directory
- Clearer project structure
- Better documentation

**Files Removed:** 11 files, 2 directories
**Files Created:** 3 (.env.example files + this doc)

