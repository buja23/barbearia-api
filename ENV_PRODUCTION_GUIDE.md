# 📋 GUIA DE CONFIGURAÇÃO .ENV PARA PRODUÇÃO

**Este arquivo mostra EXATAMENTE o que você precisa configurar antes de fazer deploy**

---

## ⚠️ IMPORTANTE: SEGURANÇA DO .ENV

```bash
# NUNCA commitar .env em Git!
# Adicionar ao .gitignore (já está, confirme)
grep .env .gitignore

# Em produção, dar permissões seguras
chmod 600 .env

# Usar CI/CD (GitHub Actions, GitLab CI) para gerenciar credenciais
# OU copiar manualmente com cuidado
```

---

## 📝 EXEMPLO DE .ENV PARA PRODUÇÃO

Copie este arquivo e ALTERE os valores marcados com ⚠️:

```env
###########################################################
# 🏢 APLICAÇÃO - PRODUÇÃO
###########################################################

APP_NAME=Barbearia
APP_ENV=production                                    # ✅ DEVE SER: production
APP_KEY=base64:SEU_APP_KEY_AQUI                       # ✅ Já gerado (não alterar)
APP_DEBUG=false                                        # ✅ ALWAYS false em produção
APP_URL=https://seu-dominio-real.com.br              # ⚠️  ALTERAR: seu domínio (HTTPS!)

APP_TIMEZONE=America/Sao_Paulo                        # ✅ Correto para Brasil
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file


###########################################################
# 🔑 CRIPTOGRAFIA
###########################################################

BCRYPT_ROUNDS=12                                       # ✅ Seguro (padrão)


###########################################################
# 📊 LOGGING
###########################################################

LOG_CHANNEL=stack                                      # ✅ Usar stack
LOG_STACK=single                                       # ✅ single = arquivo único
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning                                      # ⚠️  Em produção: warning ou error


###########################################################
# 🗄️  BANCO DE DADOS - POSTGRESQL
###########################################################

DB_CONNECTION=pgsql                                    # ✅ PostgreSQL em produção (melhor que SQLite)
DB_HOST=seu-db.producao.com.br                       # ⚠️  ALTERAR: seu host do BD
DB_PORT=5432                                           # ✅ Porta padrão PostgreSQL
DB_DATABASE=barbearia_prod                            # ⚠️  ALTERAR: nome BD produção
DB_USERNAME=seu_db_user                               # ⚠️  ALTERAR: usuário BD
DB_PASSWORD=SENHA_FORTE_ALEATORIO_MIN_20_CHARS       # ⚠️  ALTERAR: senha BD (FORTE!)


###########################################################
# 🔐 SESSION
###########################################################

SESSION_DRIVER=database                                # ✅ Armazenar em BD
SESSION_LIFETIME=120                                   # ✅ 2 horas
SESSION_ENCRYPT=false                                  # ✅ BD já encripta


###########################################################
# 📡 BROADCAST
###########################################################

BROADCAST_CONNECTION=log                               # ✅ OK para esta app


###########################################################
# 📁 FILE STORAGE
###########################################################

FILESYSTEM_DISK=local                                  # ✅ OK (pode ser S3 depois)


###########################################################
# ⏳ QUEUE
###########################################################

QUEUE_CONNECTION=database                              # ✅ OK para produção pequena
# Se tiver muitas transações, usar Redis:
# QUEUE_CONNECTION=redis


###########################################################
# 💾 CACHE
###########################################################

CACHE_STORE=database                                   # ✅ OK (Redis é melhor se houver muitos acessos)
# Para melhor performance em produção, usar:
# CACHE_STORE=redis


###########################################################
# 🔴 REDIS (para Queue/Cache - OPCIONAL em produção)
###########################################################

REDIS_CLIENT=phpredis
REDIS_HOST=redis.producao.com.br                     # ⚠️  Se usar Redis, alterar host
REDIS_PASSWORD=sua_senha_redis                        # ⚠️  Se usar Redis, alterar senha
REDIS_PORT=6379


###########################################################
# 📧 EMAIL - SMTP (NÃO USE LOG EM PRODUÇÃO!)
###########################################################

MAIL_MAILER=smtp                                       # ✅ MUST BE smtp em produção
MAIL_HOST=smtp.seuservidor.com                       # ⚠️  ALTERAR: seu host SMTP
MAIL_PORT=587                                          # ⚠️  Ou 465 para SSL
MAIL_USERNAME=seu-email@seu-dominio.com.br           # ⚠️  ALTERAR: seu email
MAIL_PASSWORD=sua-senha-smtp                          # ⚠️  ALTERAR: senha SMTP
MAIL_ENCRYPTION=tls                                    # ⚠️  Ou 'ssl' se porta 465
MAIL_FROM_ADDRESS=noreply@seu-dominio.com.br         # ⚠️  Usar email no seu domínio
MAIL_FROM_NAME="Barbearia"


###########################################################
# 🌐 AWS / CLOUD STORAGE (OPCIONAL agora, SIM depois)
###########################################################

AWS_ACCESS_KEY_ID=                                     # Deixar vazio se não usar S3
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=seu-bucket-s3
AWS_USE_PATH_STYLE_ENDPOINT=false


###########################################################
# 💳 MERCADO PAGO - QUANDO TIVER CREDENCIAIS!
###########################################################

# ⚠️  NÃO TEM AINDA - Preencher quando for deploy com pagamentos
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADO_PAGO_WEBHOOK_SECRET=


###########################################################
# 🎨 FRONTEND (VITE)
###########################################################

VITE_APP_NAME="Barbearia"


###########################################################
# 👤 ADMIN INICIAL (ALTERAR IMEDIATAMENTE!)
###########################################################

ADMIN_NAME=Administrador
ADMIN_EMAIL=seu-email-real@seu-dominio.com.br        # ⚠️  ALTERAR: seu email real
ADMIN_PASSWORD=UmaSenhaForteAleatorio@123456789      # ⚠️️  ALTERAR: senha >20 chars, com MAIÚS + números + símbolos


###########################################################
# 🎫 SANCTUM - API TOKEN EXPIRATION
###########################################################

SANCTUM_EXPIRATION=1440                                # ✅ 24 horas (pode ajustar)


###########################################################
# 🚦 RATE LIMITING
###########################################################

RATE_LIMIT_AUTH=5                                      # ✅ 5 tentativas/min no login


###########################################################
# 🔌 CORS - CROSS-ORIGIN (CRÍTICO!)
###########################################################

CORS_ALLOWED_ORIGINS=https://seu-frontend.com.br,https://admin.seu-dominio.com.br
# ❌ NUNCA deixar: http://localhost,*
# ✅ SEMPRE HTTPS em produção
# ✅ Apenas domínios confiáveis


###########################################################
# 🔍 APLICAÇÕES DE MONITORAMENTO (OPCIONAL - Recomendado)
###########################################################

# Sentry - Rastreamento de erros
# SENTRY_LARAVEL_DSN=https://seu-dsn@sentry.io/seu-project-id

# Bugsnag - Alertas
# BUGSNAG_API_KEY=sua-api-key


###########################################################
# 📲 NOTIFICAÇÕES (OPCIONAL)
###########################################################

# Slack
# LOG_SLACK_WEBHOOK_URL=

# Telegram
# TELEGRAM_BOT_TOKEN=
# TELEGRAM_CHAT_ID=
```

---

## ✅ CHECKLIST DE VALORES A ALTERAR

Antes de fazer deploy, CONFIRME que alterou TODOS estes:

```
[ ] APP_ENV = production (não local)
[ ] APP_DEBUG = false
[ ] APP_URL = https://seu-dominio.com (HTTPS!)
[ ] DB_HOST = seu-host-producao
[ ] DB_DATABASE = barbearia_prod (ou nome correto)
[ ] DB_USERNAME = usuario-seguro
[ ] DB_PASSWORD = SENHA_FORTE (min 20 chars)
[ ] MAIL_MAILER = smtp (não log)
[ ] MAIL_HOST = seu-smtp
[ ] MAIL_USERNAME = seu-email
[ ] MAIL_PASSWORD = sua-senha-smtp
[ ] MAIL_FROM_ADDRESS = noreply@seu-dominio
[ ] ADMIN_EMAIL = seu-email-real@seu-dominio.com.br
[ ] ADMIN_PASSWORD = SENHA_MUITO_FORTE (min 20 chars)
[ ] CORS_ALLOWED_ORIGINS = sem localhost, HTTPS
[ ] LOG_LEVEL = warning (não debug)
```

---

## 🔥 VALORES PERIGOSOS (NÃO USAR)

❌ **NUNCA EM PRODUÇÃO**:

```env
APP_DEBUG=true
APP_ENV=local
APP_URL=http://localhost
MAIL_MAILER=log
MAIL_DRIVER=array
CORS_ALLOWED_ORIGINS=*
CORS_ALLOWED_ORIGINS=http://localhost
LOG_LEVEL=debug
ADMIN_PASSWORD=ChangeMe@12345
DB_PASSWORD=password
MERCADOPAGO_ACCESS_TOKEN=$sandbox_token
```

---

## 📊 EXEMPLO DE VALORES REAIS

(Use este como template, NÃO copie direto!)

```env
# Para API: https://api.seusite.com.br
APP_URL=https://api.seusite.com.br

# Para Admin Filament: https://admin.seusite.com.br
# Adicionar CORS: CORS_ALLOWED_ORIGINS=...,https://admin.seusite.com.br

# Email: SendGrid
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.seu-chave-sendgrid
MAIL_FROM_ADDRESS=suporte@seusite.com.br

# Email: Gmail (ative 2FA + App Password se usar)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-app-password-16-chars
MAIL_ENCRYPTION=tls

# Email: Seu próprio servidor SMTP
MAIL_HOST=mail.seuservidor.com
MAIL_PORT=587
MAIL_USERNAME=noreply@seuservidor.com
MAIL_PASSWORD=senha-do-email-noreply
```

---

## 🚀 COMO GERAR VALORES SEGUROS

### Gerar senha forte:
```bash
# Linux/Mac
openssl rand -base64 32

# Resultado: OmY3N2F2ZDdmZDdhMGY2ZjdhMjcwMDM2YTEwYjg4NjI=
```

### Gerar APP_KEY (se necessário):
```bash
php artisan key:generate
# Já deixa no .env automaticamente
```

---

## 📋 SETUP FINAL

Depois de preencher o `.env`:

```bash
# 1. Testar conexão BD
php artisan db:show

# 2. Executar migrações
php artisan migrate --force

# 3. Cache configuração
php artisan config:cache

# 4. Ver valores (para verificar)
php artisan config:show APP_ENV
php artisan config:show APP_DEBUG
php artisan config:show DB_HOST
```

---

## ⚠️ CUIDADOS COM SEGURANÇA

1. **NUNCA** compartilhar `.env` por email/chat
2. **NUNCA** commitar em Git
3. **SEMPRE** usar conexão SSH para alterar no servidor
4. **SEMPRE** fazer backup antes de mudar credenciais
5. **SEMPRE** usar HTTPS (não HTTP)
6. **SEMPRE** testar em staging antes de produção

---

**Última atualização**: 11 de Março de 2026
