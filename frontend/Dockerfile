FROM node:18-alpine

WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm ci --only=production

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Use multi-stage build for production
FROM nginx:alpine
COPY --from=0 /app/build /usr/share/nginx/html
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=3s \
  CMD wget -q --spider http://localhost:80/ || exit 1 