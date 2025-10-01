# Step 1: Start with an official base image that has PHP and Apache
FROM php:8.2-apache

# Step 2: Install system dependencies needed by the application
# - openssh-client is for the 'ssh' command.
# - git, zip, unzip are for Composer.
# - sshpass is the utility we decided not to use, but including it can be useful for debugging.
RUN apt-get update && apt-get install -y \
    openssh-client \
    sshpass \
    git \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Step 3: Install the PHP extensions our application needs
# - pdo_sqlite for the database
# - sockets for the WebSocket server
RUN docker-php-ext-install pdo pdo_sqlite sockets

# Step 4: Install Composer (the PHP dependency manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Step 5: Copy application files into the container
# Set the working directory for the web server
WORKDIR /var/www/html

# First, copy composer files and install dependencies. This is efficient
# because Docker will cache this layer. It only re-runs if composer.json changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Now, copy the rest of the application code
COPY . .

# Step 6: Set correct ownership for web server files
RUN chown -R www-data:www-data /var/www/html

# Step 7: Expose the ports the container will listen on
# Port 80 for the Apache web server
EXPOSE 80
# Port 8080 for our WebSocket server
EXPOSE 8080