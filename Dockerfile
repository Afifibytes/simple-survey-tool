# Use a pre-built Laravel-ready image
FROM webdevops/php-nginx:8.2-alpine AS base

# Install additional dependencies
RUN apk add --no-cache \
    nodejs \
    npm \
    mysql-client \
    redis

# Set working directory
WORKDIR /app

COPY package*.json ./
RUN npm ci
COPY . .
RUN npm ci && npm run build


# Development stage
FROM base AS development

# Copy application files
COPY . /app

# Install Composer dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
RUN npm install && npm run build

# Set proper permissions
RUN chown -R application:application /app \
    && chmod -R 755 /app/storage \
    && chmod -R 755 /app/bootstrap/cache

# Expose port
EXPOSE 80

# Use the default webdevops entrypoint
CMD ["supervisord"]

# Production stage
FROM base AS production

# Copy application files
COPY . /app

# Install Composer dependencies (production only)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
RUN rm -rf node_modules && npm ci --only=production

# Remove Node.js to reduce image size
RUN apk del nodejs npm

# Set proper permissions
RUN chown -R application:application /app \
    && [ -d /app/storage ] && chmod -R 755 /app/storage || true \
    && [ -d /app/bootstrap/cache ] && chmod -R 755 /app/bootstrap/cache || true

# Expose port
EXPOSE 80

# Use the default webdevops entrypoint
CMD ["supervisord"]
