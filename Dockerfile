FROM php:8.2-apache

# Install MariaDB and dependencies
RUN apt-get update && apt-get install -y 
    mariadb-server 
    mariadb-client 
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Update Apache port
RUN sed -i 's/Listen 80/Listen 7860/' /etc/apache2/ports.conf 
    && sed -i 's/:80/:7860/' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

# Setup MySQL permissions
RUN mkdir -p /var/run/mysqld /var/lib/mysql && 
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql && 
    chmod -R 777 /var/run/mysqld /var/lib/mysql

# Initialize DB
RUN mysql_install_db --user=mysql --datadir=/var/lib/mysql

# Permissions
RUN chown -R www-data:www-data /var/www/html && 
    chmod -R 755 /var/www/html

EXPOSE 7860

# Startup script
RUN echo '#!/bin/bash

/usr/bin/mariadbd-safe --user=mysql --datadir=/var/lib/mysql &

for i in {1..30}; do

    if mysqladmin ping -h localhost --silent; then break; fi

    sleep 1

done

mysql -e "CREATE DATABASE IF NOT EXISTS betterabroad;"

if [ -f "DATABASE/schema.sql" ]; then

    mysql betterabroad < DATABASE/schema.sql

fi

apache2-foreground' > /usr/local/bin/start.sh 
    && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
