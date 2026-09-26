# Imagem base do PHP 8.4 com Apache
FROM php:8.4-apache

# Instalar bibliotecas de sistema necessárias para as extensões do PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip intl gd

# Instalar o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar o Apache para apontar para a pasta public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Copiar os ficheiros do projeto para o contentor
COPY . /var/www/html

# CRIAR AS PASTAS DE SESSÃO E CACHE QUE O GIT IGNORA
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache/data

# Ajustar permissões totais para o Apache conseguir gravar as sessões
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Instalar dependências
RUN composer install --optimize-autoloader --no-dev

# Limpar cache, criar links e migrar na inicialização
CMD php artisan optimize:clear && php artisan storage:link && php artisan migrate --force && apache2-foreground

CMD chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache && php artisan optimize:clear && php artisan storage:link && php artisan migrate --force && apache2-foreground