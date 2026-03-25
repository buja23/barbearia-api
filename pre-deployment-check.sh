#!/bin/bash

# 🚀 SCRIPT DE PRÉ-DEPLOYMENT PARA PRODUÇÃO
# Use este script ANTES de fazer o deploy
# Ele verifica tudo e prepara o projeto

echo "==================================================="
echo "  🚀 VERIFICAÇÃO PRÉ-DEPLOYMENT"
echo "  Barbearia API - Produção"
echo "==================================================="
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para verificar
check() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅${NC} $2"
    else
        echo -e "${RED}❌${NC} $2"
    fi
}

# Contadores
PASSED=0
FAILED=0

echo ""
echo "📋 VERIFICAÇÕES DE SEGURANÇA:"
echo "---"

# 1. Verificar .env existe
if [ -f .env ]; then
    echo -e "${GREEN}✅${NC} .env encontrado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} .env NÃO encontrado"
    ((FAILED++))
fi

# 2. Verificar APP_KEY
if grep -q "^APP_KEY=base64:" .env; then
    echo -e "${GREEN}✅${NC} APP_KEY configurada"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} APP_KEY não configurada"
    ((FAILED++))
fi

# 3. Verificar APP_DEBUG=false
if grep -q "^APP_DEBUG=false" .env; then
    echo -e "${GREEN}✅${NC} APP_DEBUG=false"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} APP_DEBUG não foi desabilitado"
    ((FAILED++))
fi

# 4. Verificar APP_ENV
APP_ENV=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
echo "   Ambiente: $APP_ENV"
if [ "$APP_ENV" = "production" ]; then
    echo -e "${GREEN}✅${NC} APP_ENV=production"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠️${NC} APP_ENV=$APP_ENV (para produção deve ser 'production')"
    ((FAILED++))
fi

# 5. Verificar database configurado
if grep -q "^DB_HOST=" .env && [ ! -z "$(grep '^DB_HOST=' .env | cut -d'=' -f2)" ]; then
    echo -e "${GREEN}✅${NC} Database configurado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} Database NÃO configurado"
    ((FAILED++))
fi

# 6. Verificar ADMIN_PASSWORD foi alterada
ADMIN_PASS=$(grep "^ADMIN_PASSWORD=" .env | cut -d'=' -f2)
if [ "$ADMIN_PASS" = "ChangeMe@12345" ]; then
    echo -e "${RED}❌${NC} ADMIN_PASSWORD ainda é a padrão - ALTERE ANTES DE DEPLOY!"
    ((FAILED++))
else
    echo -e "${GREEN}✅${NC} ADMIN_PASSWORD alterada"
    ((PASSED++))
fi

# 7. Verificar CORS configurado
if grep -q "^CORS_ALLOWED_ORIGINS=" .env && ! grep -q "localhost" <(grep "^CORS_ALLOWED_ORIGINS=" .env); then
    echo -e "${GREEN}✅${NC} CORS configurado (sem localhost)"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠️${NC} CORS pode incluir localhost - verifique em produção"
    ((FAILED++))
fi

# 8-EXTRA. Verificar MAIL_MAILER não é 'log' em produção
MAIL_MAILER=$(grep "^MAIL_MAILER=" .env | cut -d'=' -f2)
if [ "$MAIL_MAILER" = "log" ]; then
    echo -e "${RED}❌${NC} MAIL_MAILER=log - emails não serão enviados em produção!"
    ((FAILED++))
else
    echo -e "${GREEN}✅${NC} MAIL_MAILER=$MAIL_MAILER"
    ((PASSED++))
fi

# 8-EXTRA-B. Verificar LOG_LEVEL não é 'debug'
LOG_LEVEL=$(grep "^LOG_LEVEL=" .env | cut -d'=' -f2)
if [ "$LOG_LEVEL" = "debug" ]; then
    echo -e "${YELLOW}⚠️${NC} LOG_LEVEL=debug - muito verboso para produção (use info ou notice)"
    ((FAILED++))
else
    echo -e "${GREEN}✅${NC} LOG_LEVEL=$LOG_LEVEL"
    ((PASSED++))
fi

# 8-EXTRA-C. Verificar QUEUE_CONNECTION não é 'sync'
QUEUE_CONN=$(grep "^QUEUE_CONNECTION=" .env | cut -d'=' -f2)
if [ "$QUEUE_CONN" = "sync" ]; then
    echo -e "${RED}❌${NC} QUEUE_CONNECTION=sync - notificações e jobs rodam de forma síncrona (lento)!"
    ((FAILED++))
else
    echo -e "${GREEN}✅${NC} QUEUE_CONNECTION=$QUEUE_CONN"
    ((PASSED++))
fi

# 8-EXTRA-D. Verificar MERCADOPAGO_ACCESS_TOKEN configurado
if grep -q "^MERCADOPAGO_ACCESS_TOKEN=" .env && [ ! -z "$(grep '^MERCADOPAGO_ACCESS_TOKEN=' .env | cut -d'=' -f2)" ]; then
    echo -e "${GREEN}✅${NC} MERCADOPAGO_ACCESS_TOKEN configurado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} MERCADOPAGO_ACCESS_TOKEN não configurado"
    ((FAILED++))
fi

echo ""
echo "📦 VERIFICAÇÕES DE DEPENDÊNCIAS:"
echo "---"

# 8. Verificar composer.lock existe
if [ -f composer.lock ]; then
    echo -e "${GREEN}✅${NC} composer.lock encontrado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} composer.lock NÃO encontrado"
    ((FAILED++))
fi

# 9. Verificar composer audit
echo -e "${YELLOW}⏳${NC} Verificando dependências via composer audit..."
if command -v composer &> /dev/null; then
    AUDIT_RESULT=$(composer audit 2>&1 | grep -i "No security")
    if [ ! -z "$AUDIT_RESULT" ]; then
        echo -e "${GREEN}✅${NC} Sem vulnerabilidades em dependências"
        ((PASSED++))
    else
        echo -e "${RED}❌${NC} Há vulnerabilidades em dependências - execute: composer audit"
        ((FAILED++))
    fi
else
    echo -e "${YELLOW}⚠️${NC} Composer não encontrado - pule este passo"
fi

echo ""
echo "📁 VERIFICAÇÕES DE ARQUIVOS:"
echo "---"

# 10. Verificar artisan existe
if [ -f artisan ]; then
    echo -e "${GREEN}✅${NC} artisan encontrado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} artisan NÃO encontrado"
    ((FAILED++))
fi

# 11. Verificar storage/ existe
if [ -d storage ]; then
    echo -e "${GREEN}✅${NC} storage/ encontrado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} storage/ NÃO encontrado"
    ((FAILED++))
fi

# 12. Verificar bootstrap/cache existe
if [ -d bootstrap/cache ]; then
    echo -e "${GREEN}✅${NC} bootstrap/cache/ encontrado"
    ((PASSED++))
else
    echo -e "${RED}❌${NC} bootstrap/cache/ NÃO encontrado"
    ((FAILED++))
fi

echo ""
echo "🔐 VERIFICAÇÕES DE SEGURANÇA DO SERVIDOR:"
echo "---"

# 13. Verificar .env não está em git
if ! grep -q "\.env" .gitignore 2>/dev/null; then
    echo -e "${RED}❌${NC} .env pode estar em git - adicione a .gitignore"
    ((FAILED++))
else
    echo -e "${GREEN}✅${NC} .env está no .gitignore"
    ((PASSED++))
fi

# 14. Verificar permissões do .env
if [ -f .env ]; then
    PERMS=$(stat -c "%a" .env 2>/dev/null || stat -f "%OLp" .env | sed 's/.*\(...\)$/\1/')
    if [ "$PERMS" = "600" ] || [ "$PERMS" = "400" ]; then
        echo -e "${GREEN}✅${NC} .env com permissões seguras ($PERMS)"
        ((PASSED++))
    else
        echo -e "${YELLOW}⚠️${NC} .env com permissões $PERMS - recomendado 600"
        ((FAILED++))
    fi
fi

echo ""
echo "==================================================="
echo "📊 RESULTADO:"
echo "==================================================="
echo -e "Passou: ${GREEN}$PASSED${NC}"
echo -e "Falhou: ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅ TUDO PRONTO PARA DEPLOY EM PRODUÇÃO!${NC}"
    exit 0
else
    echo -e "${RED}❌ Há problemas a serem resolvidos acima${NC}"
    exit 1
fi
