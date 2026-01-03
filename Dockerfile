# Use the official PHP 8.2 image.
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    # Install PostgreSQL client
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . .

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/html

# Change current user to www
USER www-data

# Expose port 8000 and start php-fpm server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
