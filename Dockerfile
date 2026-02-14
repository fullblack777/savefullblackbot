# Dockerfile
FROM php:8.2-apache

# Instalar extensões necessárias (curl já vem, json já vem)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar arquivos
COPY . /var/www/html/

# Criar pastas necessárias e dar permissões
RUN mkdir -p /var/www/html/data /var/www/html/api && \
    chown -R www-data:www-data /var/www/html/data && \
    chmod -R 755 /var/www/html/data && \
    chmod -R 755 /var/www/html/api

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configurar Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configurar PHP
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
