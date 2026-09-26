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