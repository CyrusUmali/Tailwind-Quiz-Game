# Use official PHP 8.2 image with Apache
FROM php:8.2-apache

# Install required dependencies for MongoDB extension
RUN apt-get update && apt-get install -y \
    libssl-dev \
    unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite \
    && apt-get clean

# Set working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . /var/www/html/

# Set permissions (optional, helps with Apache)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
