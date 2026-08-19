FROM php:8.3-cli

# Install MySQL/PDO extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy website files
COPY . /var/www/html/

# Railway will provide PORT automatically
EXPOSE 8080

# Start PHP built-in server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /var/www/html"]
