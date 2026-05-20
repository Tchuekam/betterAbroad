FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite

# Enable Apache modules
RUN a2enmod rewrite

# Update Apache port
RUN sed -i 's/Listen 80/Listen 7860/' /etc/apache2/ports.conf 
    && sed -i 's/:80/:7860/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 7860
CMD ["apache2-foreground"]
