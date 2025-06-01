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
- Node.js 18+ (for frontend development)

## Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/dropcrypt.git
cd dropcrypt
```

2. Create environment files:
```bash
cp backend/.env.example backend/.env
```

3. Start the Docker containers:
```bash
docker-compose up -d
```

4. Install backend dependencies:
```bash
docker-compose exec php composer install
```

5. Run database migrations:
```bash
docker-compose exec php bin/console doctrine:migrations:migrate
```

6. Install frontend dependencies:
```bash
cd frontend
npm install
```

7. Start the frontend development server:
```bash
npm run dev
```

The application will be available at:
- Frontend: http://localhost:3000
- Backend API: http://localhost:80

## Development

- Backend: Symfony 6.4
- Frontend: React with TypeScript
- Database: PostgreSQL
- Infrastructure: Docker with Nginx, PHP-FPM

## Security

- All messages are encrypted using AES-256
- Messages are automatically deleted after viewing
- Rate limiting is implemented
- CORS protection is enabled
- Input validation is enforced

## License

MIT 