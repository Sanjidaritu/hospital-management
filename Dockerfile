FROM php:8.3-apache

# Install MySQL/PDO extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Remove all Apache MPM modules first
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
    /etc/apache2/mods-enabled/mpm_*.conf

# Enable only prefork MPM
RUN a2enmod mpm_prefork rewrite

# Copy website files
COPY . /var/www/html/

# Configure Apache to listen on Railway's port
RUN sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' \
    /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
