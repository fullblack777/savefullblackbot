# Dockerfile
FROM php:8.2-apache

# Instalar extensões
RUN docker-php-ext-install json

# Copiar arquivos
COPY . /var/www/html/

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html/data && \
    chmod -R 755 /var/www/html/data

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
