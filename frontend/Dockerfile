# TODO: Add React application files (package.json, src/, public/)
# This branch currently uses Twig templates in backend/templates/
# Other branches (main, DRC-8-001) have complete React frontends

FROM node:18-alpine

WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm ci --only=production

# Copy source code
COPY . .

# Build the application
RUN npm run build

