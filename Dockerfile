FROM php:8.4-cli

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    zip \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing app files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Startup script
COPY app-startup.sh /app-startup.sh
RUN chmod +x /app-startup.sh

# Expose port 8000 for php artisan serve
EXPOSE 8000

CMD ["/app-startup.sh"]
