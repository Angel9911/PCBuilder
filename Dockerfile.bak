# Use PHP 8.1 with FPM
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y \
    curl \
    zip \
    unzip \
    git \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libssl-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www

# If you have a local .env file, explicitly copy it (optional, but ensures the .env is inside Docker container)
COPY .env /var/www/.env

# Set correct permissions
RUN chown -R www-data:www-data /var/www

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-scripts

# Expose the correct port
EXPOSE 8080

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]