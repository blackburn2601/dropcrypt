# DropCrypt

A secure, end-to-end encrypted messaging system.

## Features

- End-to-end encryption using AES-256
- One-time view functionality
- Self-destructing messages
- Custom expiration times
- Unique access tokens

## Requirements

- Docker
- Docker Compose
- Git

## Quick Start

1. Clone the repository:
```bash
git clone https://github.com/yourusername/dropcrypt.git
cd dropcrypt
```

2. Run the automated setup:
```bash
./setup.sh
```

That's it! The application will be available at http://localhost

The setup script automatically:
- Creates necessary environment files
- Generates secure encryption keys
- Builds and starts Docker containers
- Installs all dependencies
- Sets up the database
- Runs migrations

## Manual Setup (if needed)

If you prefer to set up the application manually or need to customize the configuration:

1. Create environment files:
```bash
cp backend/.env.example backend/.env
```

2. Configure environment variables (see Environment Variables section below)

3. Start the Docker containers:
```bash
docker compose up -d --build
```

### Environment Variables

#### Backend (.env)
```env
# Application
APP_ENV=dev
APP_SECRET=your_secret_key_here
APP_DEBUG=1

# Database
DB_USER=dropcrypt
DB_PASSWORD=dropcrypt
DB_NAME=dropcrypt
DATABASE_URL="postgresql://${DB_USER}:${DB_PASSWORD}@postgres:5432/${DB_NAME}?serverVersion=15&charset=utf8"

# CORS Configuration
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

# Message Configuration
MESSAGE_ENCRYPTION_KEY=your_encryption_key_here
MESSAGE_DEFAULT_EXPIRY=24   # hours
MESSAGE_MAX_LENGTH=10000    # characters

# Rate Limiting
RATE_LIMIT_PER_MINUTE=60
```

Important environment variables explained:
- `APP_SECRET`: A random string used for security purposes (min. 32 characters)
- `APP_ENV`: Set to `dev` for development, `prod` for production
- `APP_DEBUG`: Set to `1` for development, `0` for production
- `DB_*`: Database connection parameters
- `CORS_ALLOW_ORIGIN`: Regex pattern for allowed origins
- `MESSAGE_ENCRYPTION_KEY`: Key used for message encryption (min. 32 characters)
- `MESSAGE_DEFAULT_EXPIRY`: Default message expiration time in hours
- `RATE_LIMIT_PER_MINUTE`: Maximum API requests per minute per IP

## Development

The project uses a Docker-based development environment:

- Backend: Symfony 6.4
- Frontend: React
- Database: PostgreSQL 15
- Infrastructure: Docker with Nginx, PHP-FPM

### Container Structure

- **nginx**: Web server, handles both frontend and API requests
- **php**: PHP-FPM container running the Symfony backend
- **frontend**: Node.js container for building the React application
- **postgres**: PostgreSQL database server

### Development Tips

1. **Local Development**
   ```bash
   # View logs
   docker compose logs -f

   # Access container shells
   docker compose exec php bash
   docker compose exec postgres psql -U dropcrypt -d dropcrypt
   ```

2. **Database Management**
   ```bash
   # Create database
   docker compose exec php bin/console doctrine:database:create

   # Run migrations
   docker compose exec php bin/console doctrine:migrations:migrate

   # Create new migration
   docker compose exec php bin/console make:migration
   ```

3. **Cache Management**
   ```bash
   # Clear cache
   docker compose exec php bin/console cache:clear
   ```

## Security Best Practices

1. **Production Environment**
   - Generate new secure keys:
     ```bash
     openssl rand -hex 32  # For APP_SECRET
     openssl rand -hex 32  # For MESSAGE_ENCRYPTION_KEY
     ```
   - Set strict environment variables:
     ```env
     APP_ENV=prod
     APP_DEBUG=0
     CORS_ALLOW_ORIGIN='^https?://your-domain\.com$'
     RATE_LIMIT_PER_MINUTE=30
     ```

2. **Security Headers**
   - HTTPS only in production
   - Strict CSP headers
   - XSS protection enabled
   - CSRF protection active

## Troubleshooting

1. **Container Issues**
   ```bash
   # Rebuild containers
   docker compose down -v
   docker compose up -d --build
   ```

2. **Permission Problems**
   ```bash
   # Reset file permissions
   docker compose exec php chown -R symfony:symfony .
   ```

3. **Database Issues**
   ```bash
   # Check database status
   docker compose exec postgres pg_isready -U dropcrypt
   ```

4. **Common Problems**
   - **502 Bad Gateway**: Check if PHP-FPM is running
   - **Database Connection Error**: Verify PostgreSQL is healthy
   - **Missing Dependencies**: Run setup script again

## License

MIT 