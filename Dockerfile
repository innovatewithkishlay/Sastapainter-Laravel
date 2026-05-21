FROM php:8.2-cli

# Install dependencies and PostgreSQL drivers
RUN apt-get update && apt-get install -y libpq-dev zip unzip git \
    && docker-php-ext-install pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory to your backend folder
WORKDIR /app

# Copy only the backend folder files into the container
COPY backend/ /app/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Give Laravel permission to write to its storage folders
RUN chown -R www-data:www-data storage bootstrap/cache

# Start the Laravel server on the port Render gives us
CMD php artisan serve --host=0.0.0.0 --port=$PORT
