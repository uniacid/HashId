FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    bash \
    sqlite-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy the HashId bundle source
COPY ../ /hashid-bundle/

# Create app directories
RUN mkdir -p /app/symfony-6.4 /app/symfony-7.0

# Set proper permissions
RUN chown -R www-data:www-data /app

USER www-data

# Install dependencies for Symfony 6.4
WORKDIR /app/symfony-6.4
COPY --chown=www-data:www-data symfony-6.4/composer.json symfony-6.4/composer.lock* ./
RUN composer install --no-scripts --no-autoloader

# Copy Symfony 6.4 application
COPY --chown=www-data:www-data symfony-6.4/ ./
RUN composer dump-autoload --optimize

# Install dependencies for Symfony 7.0
WORKDIR /app/symfony-7.0
COPY --chown=www-data:www-data symfony-7.0/composer.json symfony-7.0/composer.lock* ./
RUN composer install --no-scripts --no-autoloader

# Copy Symfony 7.0 application
COPY --chown=www-data:www-data symfony-7.0/ ./
RUN composer dump-autoload --optimize

# Back to root app directory
WORKDIR /app

# Expose ports
EXPOSE 8064 8070

# Start script
COPY --chown=www-data:www-data docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]