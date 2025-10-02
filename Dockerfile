# Step 1: Start with an official base image that has PHP and Apache
FROM php:8.2-apache

# Step 2: Install system dependencies needed by the application
RUN apt-get update && apt-get install -y \
    openssh-client \
    git \
    zip \
    unzip \
    libsqlite3-dev \
    # ✨ --- THIS IS THE FIX --- ✨
    # Add the SQLite3 development library needed to build the pdo_sqlite extension
    && rm -rf /var/lib/apt/lists/*

# Step 3: Install the PHP extensions our application needs
# This will now work because libsqlite3-dev is installed.
RUN docker-php-ext-install pdo pdo_sqlite sockets

# Step 4: Install Composer (the PHP dependency manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Step 5: Copy application files into the container
WORKDIR /var/www/html

# First, copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Now, copy the rest of the application code
COPY . .

# Step 6: Set correct ownership for web server files
RUN chown -R www-data:www-data /var/www/html

# Step 7: Expose the ports the container will listen on
EXPOSE 80
EXPOSE 8080