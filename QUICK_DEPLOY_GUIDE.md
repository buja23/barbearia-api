# ⚡ QUICK START - DEPLOY FORGE + DIGITAL OCEAN (30 MIN)

**Tempo Total**: ~30-45 minutos

---

## 📋 CHECKLIST RÁPIDO

```
DIGITAL OCEAN:
☐ 1. Criar conta DigitalOcean.com ($5+ voucher grátis)
☐ 2. Criar Droplet (Ubuntu 24.04 LTS, $6+/mês)
☐ 3. Copiar IP do Droplet

LARAVEL FORGE:
☐ 4. Criar conta forge.laravel.com ($12/mês)
☐ 5. Conectar Forge com DigitalOcean
☐ 6. Criar Server (automático)
☐ 7. Esperar Server ficar ativo (5-10 min)

SITE NO FORGE:
☐ 8. Criar Site (Laravel)
☐ 9. Conectar Repositório Git
☐ 10. Preencher .env
☐ 11. Criar Database PostgreSQL
☐ 12. Fazer Deploy

PÓS-DEPLOY:
☐ 13. Verificar logs
☐ 14. Testar endpoints
☐ 15. Ativar Auto Deploy

DOMÍNIO (OPCIONAL - depois):
☐ 16. Apontar DNS para Forge
☐ 17. Obter SSL/HTTPS automático
```

---

## 🎯 PASSO 1-3: DIGITAL OCEAN SETUP (5 MIN)

```bash
1. Ir para: https://www.digitalocean.com
2. Sign Up / Login
3. Menu → Manage → Billing → Add Payment Method
4. Voltar ao Console
5. Clique "Create" → "Droplet"

CONFIGURAR:
□ Region: São Paulo (fra) ou Asia (sgp)
□ OS: Ubuntu 24.04 x64 LTS
□ Size: Basic ($6/mês) - 2GB RAM, 1 CPU, 50GB disk
□ Authentication: SSH Key (ou password)
□ Hostname: barbearia-api-prod
□ Clique "Create Droplet"

⏳ Aguardar 2-3 minutos até ficar "Active" ✅

✅ CÓPIA DO IP (ex: 123.45.67.89)
```

---

## 🎯 PASSO 4-7: LARAVEL FORGE SETUP (10 MIN)

```bash
1. Ir para: https://forge.laravel.com
2. Sign Up / Login
3. Clique "Create Server"

CONFIGURAR:
□ Name: barbearia-api-prod
□ Provider: DigitalOcean
□ Clique "Connect DigitalOcean Account"
□ Pop-up: Authorizar Forge
□ Voltar ao Forge
□ Size: Basic (6GB RAM)
□ Database: PostgreSQL
□ Backup: Off
□ Clique "Create Server"

⏳ AGUARDAR 5-10 MINUTOS (Forge instala tudo)
   - Nginx
   - PHP 8.2
   - PostgreSQL
   - SSL pronto

✅ Server deve ficar VERDE (Active)
```

---

## 🎯 PASSO 8-12: CRIAR SITE & FAZER DEPLOY (15 MIN)

### A. CRIAR SITE

```
No Forge Dashboard:
1. Clique no seu Server
2. Clique "Create Site"

CONFIGURAR:
□ Domain: seu-dominio.com.br (OU deixa com IP)
□ Project Type: Laravel
□ PHP Version: 8.2 ou 8.3
□ Clique "Create Site"

✅ Site criado
```

### B. CONECTAR GIT

```
Site Dashboard no Forge:
1. Clique "Source Control"
2. Clique "Connect Repository"
3. Escolha: GitHub, GitLab, etc
4. Autorize Forge (pop-up)
5. Selecione: seu-repo/barbearia-api
6. Branch: main (ou production)
7. Clique "Install Repository"

⏳ Forge faz git clone + composer install (5 min)
```

### C. CONFIGURAR .ENV

```
Site Dashboard → "Environment"

Cole isso (ALTERE valores em CAPS):

APP_NAME=Barbearia
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:SAQMFExUs4ymz1Tzbo26bt6enpI9eHpKyx4kcltM7W4=
APP_URL=https://seu-dominio.com.br
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=barbearia_prod
DB_USERNAME=forge
DB_PASSWORD=COPIE_DE_FORGE_DATABASE_TAB

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.SUA_KEY_SENDGRID
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seu-dominio.com.br

ADMIN_NAME=Administrador
ADMIN_EMAIL=seu-email@seu-dominio.com.br
ADMIN_PASSWORD=AdminBarbearia@2025!Seguro#789

CACHE_STORE=database
QUEUE_CONNECTION=database

SANCTUM_EXPIRATION=1440
RATE_LIMIT_AUTH=5

CORS_ALLOWED_ORIGINS=https://seu-frontend.com.br

Clique "Save"
```

### D. CRIAR BANCO DE DADOS

```
Site Dashboard → "Database"

OU clique "CLI" → SSH:

ssh forge@SEU-IP-OU-DOMINIO

sudo -u postgres psql
CREATE DATABASE barbearia_prod;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
\q
exit
```

### E. FAZER DEPLOY

```
Site Dashboard:
1. Clique "Deploy Now" (ou Manual Deploy)

Forge vai:
✅ git pull
✅ composer install
✅ php artisan config:cache
✅ php artisan route:cache
✅ php artisan migrate --force
✅ Pronto!

⏳ Aguarde 3-5 minutos
```

**Status verde = Sucesso! ✅**

---

## ✅ PASSO 13-15: VALIDAR & CONFIGURAR AUTO-DEPLOY

### A. TESTAR ENDPOINTS

```bash
# Via curl
curl -I http://SEU-IP/api/health

# Resposta esperada:
# HTTP/1.1 200 OK

# Testar registro
curl -X POST http://SEU-IP/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Teste",
    "email":"teste@example.com",
    "password":"Teste@12345Forte",
    "password_confirmation":"Teste@12345Forte"
  }'
```

### B. VER LOGS

```
Site Dashboard → "Monitoring"
OU
Site Dashboard → "Files" → storage/logs/laravel.log
OU
SSH: tail -f /home/forge/SEU-DOMINIO/storage/logs/laravel.log
```

### C. ATIVAR AUTO-DEPLOY

```
Site Dashboard → "Deployments"
Toggle: "Auto Deploy"

Pronto! Agora cada git push automáticamente faz deploy.
```

---

## 📱 PÓS-DEPLOY (PRÓXIMOS PASSOS)

### Se quer usar DOMÍNIO real:

```
1. Compre domínio (Go Daddy, Namecheap, etc)
2. Configure DNS para apontar para Forge
3. No Forge: Site → SSL Certificates → Obtain Let's Encrypt
4. Pronto! HTTPS automático

Mas pode usar IP por enquanto: http://SEU-IP/
```

### MONITORAMENTO:

```
1. Configure UptimeRobot (gratuito)
   → Monitorar: http://seu-ip/api/health
   → Alertar se cair

2. Verifique logs regularmente
   
3. Configure email para receber alertas do Forge
```

---

## 🔐 SENHAS & CREDENCIAIS

Você tem:

```
1. SSH Key para acessar servidor
   ssh forge@SEU-IP

2. Banco PostgreSQL
   User: forge
   Password: [ver em Forge Database tab]

3. Admin Filament
   Email: seu-email@seu-dominio.com
   Password: AdminBarbearia@2025!Seguro#789

4. SendGrid (ou seu SMTP)
   API Key: SG.sua-chave

⚠️ NUNCA compartilhe essas credenciais!
⚠️ NUNCA commite .env em Git!
```

---

## 🚨 TROUBLESHOOTING

### "502 Bad Gateway"

```bash
ssh forge@SEU-IP
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### "Migrate falhou"

```bash
ssh forge@SEU-IP
cd /home/forge/seu-dominio
php artisan migrate:status
php artisan migrate:rollback
php artisan migrate --force
```

### "Memória insuficiente"

```bash
# Aumentar Droplet no Digital Ocean
# OU otimizar código
```

---

## 💰 CUSTOS

```
Digital Ocean Droplet ($6/mês):    ✅
Laravel Forge ($12/mês):           ✅
SSL/HTTPS:                         ✅ Grátis
Domain (opcional):                 ~$12/ano
Email (SendGrid):                  Grátis até 100/dia
─────────────────────────────────
Total Inicial:                     $18/mês
```

---

## 🎉 PRONTO!

Seu site está online em:
- **IP**: http://123.45.67.89 ← Funciona AGORA
- **Domínio**: https://seu-dominio.com (depois)

---

**Você está PRONTO para começar! Sucesso! 🚀**
