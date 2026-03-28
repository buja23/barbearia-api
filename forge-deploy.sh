#!/usr/bin/env bash
# =============================================================
#  SCRIPT DE DEPLOY — Laravel Forge
#  Cole este script em: Forge → Site → Deploy Script
# =============================================================
set -e  # Para imediatamente em qualquer erro

echo ""
echo "======================================================"
echo "  🚀 DEPLOY INICIADO — $(date '+%d/%m/%Y %H:%M:%S')"
echo "======================================================"

# --- 1. Entrar no diretório da aplicação ---
cd /home/forge/barbearia-api.on-forge.com  # ⚠️ Altere para o caminho real no Forge

# --- 2. Ativar modo de manutenção (evita requisições durante o deploy) ---
echo "[1/9] Ativando modo de manutenção..."
php artisan down --refresh=15 --retry=10

# --- 3. Pegar as últimas alterações do Git ---
echo "[2/9] Atualizando código (git pull)..."
git pull origin $FORGE_SITE_BRANCH

# --- 4. Instalar/atualizar dependências PHP (sem dev em produção) ---
echo "[3/9] Instalando dependências Composer..."
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

# --- 5. Rodar migrations ---
echo "[4/9] Rodando migrations..."
php artisan migrate --force

# --- 6. Limpar e recompilar caches ---
echo "[5/9] Recompilando caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# --- 7. Compilar assets frontend (se usar Vite/Node) ---
echo "[6/9] Compilando assets frontend..."
npm ci --silent
npm run build

# --- 8. Restartar serviços ---
echo "[7/9] Reiniciando queue workers..."
php artisan queue:restart

echo "[8/9] Reiniciando PHP-FPM..."
( flock -w 10 9 || exit 1
  echo 'Restarting FPM...'; sudo -S service php8.2-fpm reload ) 9>/tmp/fpmlock

# --- 9. Sair do modo de manutenção ---
echo "[9/9] Saindo do modo de manutenção..."
php artisan up

echo ""
echo "======================================================"
echo "  ✅ DEPLOY CONCLUÍDO — $(date '+%d/%m/%Y %H:%M:%S')"
echo "======================================================"
