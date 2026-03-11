#!/bin/bash
# fix-vulnerabilities.sh
# Script para atualizar pacotes vulneráveis no Composer

cd /home/buja/projetos/barbearia-api

echo "🔧 Iniciando resolução de vulnerabilidades do Composer..."
echo ""

# Backup do composer.lock
echo "📦 Fazendo backup de composer.lock..."
cp composer.lock composer.lock.backup.$(date +%Y-%m-%d_%H%M%S)
echo "✅ Backup criado"
echo ""

# Update de pacotes vulneráveis
echo "🔄 Atualizando dependências completas..."
composer update --with-all-dependencies --no-interaction --ignore-platform-reqs

echo ""
echo "✅ Atualização concluída"
echo ""

# Audit final
echo "🔍 Executando composer audit..."
composer audit

echo ""
echo "📝 Resumo: Se 'Found 0 security vulnerability advisories', então está resolvido!"
