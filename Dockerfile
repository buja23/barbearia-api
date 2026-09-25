# Atualizado para a versão PHP 8.4 com Apache
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

# Habilitar mod_rewrite do Apache (necessário para as rotas do Laravel)
RUN a2enmod rewrite

# Copiar os ficheiros do projeto para o contentor
COPY . /var/www/html

# Ajustar permissões para as pastas de cache e storage
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Instalar as dependências do Laravel
RUN composer install --optimize-autoloader --no-dev

# Gerar a app key e correr as migrations na hora de iniciar o servidor
CMD php artisan storage:link && php artisan migrate --force && apache2-foreground