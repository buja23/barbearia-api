# 🚀 GUIA COMPLETO - DEPLOY COM LARAVEL FORGE + DIGITAL OCEAN

**Data**: 11 de Março de 2026  
**Projeto**: Barbearia API  
**Tempo Estimado**: 30-45 minutos

---

## 📋 PRÉ-REQUISITOS

- [ ] Conta no [Laravel Forge](https://forge.laravel.com) (paga, ~$12/mês)
- [ ] Conta no [Digital Ocean](https://www.digitalocean.com) (paga, ~$5-6/mês droplet)
- [ ] Repositório Git (GitHub, GitLab, etc) com código commitado
- [ ] Domínio DNS apontando para Digital Ocean (opcional para começar)
- [ ] `.env` **NÃO** deve estar em Git

---

## 🔑 PASSO 1: CRIAR DROPLET NO DIGITAL OCEAN

### 1.1 Acesse Digital Ocean

1. Vá para: https://cloud.digitalocean.com
2. Clique em **Create** → **Droplets**

### 1.2 Configurar Droplet

```
Region: São Paulo (fra) ou Ásia (sgp) conforme clientes
OS: Ubuntu 24.04 x64 LTS (recomendado)
Tamanho: $6/mês (2GB RAM, 1 CPU, 50GB disk) - MÍNIMO
         $12/mês (2GB RAM, 2 CPU, 60GB disk) - RECOMENDADO

Backup: Deixar desabilitado (economiza)
IPv6: Ativar (não custa extra)
Monitoring: Desativar (por enquanto)
User Data: Deixar vazio (Forge faz isso)
SSH Key: Adicionar sua chave SSH

Hostname: barbearia-api-prod
```

**Custo**: ~$6-12/mês

### 1.3 Confirmar Criação

- Clique **Create Droplet**
- Aguarde ~2 minutos
- Copie o IP do Droplet (ex: 123.45.67.89)

---

## 🎯 PASSO 2: CONECTAR LARAVEL FORGE

### 2.1 Acessar Laravel Forge

1. Vá para: https://forge.laravel.com
2. Login com sua conta
3. Clique **Create Server**

### 2.2 Conectar ao Digital Ocean

```
Provider: DigitalOcean
Authentication: 
  - Clique "Connect DigitalOcean Account"
  - Autorize Forge (abre popup)
  - Permite que Forge gerencie seus droplets
```

### 2.3 Criar Server no Forge

```
Name: barbearia-api-prod
Provider: DigitalOcean
Region: São Paulo ou Asia
Size: Basic (6GB RAM)
Database: PostgreSQL
Backup: No
```

**Clique Create Server**

Forge vai:
- ✅ Acessar seu Droplet Digital Ocean
- ✅ Instalar Nginx + PHP 8.2/8.3
- ✅ Instalar PostgreSQL
- ✅ Configurar SSL/HTTPS (Let's Encrypt)
- ✅ Criar conta deploy com SSH

Tempo: ~5-10 minutos

---

## 📁 PASSO 3: CRIAR SITE NO FORGE

### 3.1 Dashboard do Forge

Depois que o server ficar **Active** (verde):

1. Clique no servidor
2. Clique **Create Site**

### 3.2 Configurar Site

```
Domain: seu-dominio.com.br OU ip-do-droplet
Project Type: Laravel
PHP Version: 8.2 ou 8.3
```

**Clique Create Site**

Forge vai criar:
- ✅ Diretório `/home/forge/seu-dominio.com.br`
- ✅ Nginx vhost
- ✅ Usuário `forge` com SSH
- ✅ SSL auto (depois de confirmar domínio)

---

## 🔗 PASSO 4: CONECTAR REPOSITÓRIO GIT

### 4.1 Gerar Deploy Key

No Forge, na página do site:

1. Clique **Source Control**
2. Clique **Connect Repository**
3. Escolha: **GitHub**, **GitLab**, etc

### 4.2 Autenticar com Git

```
GitHub:
- Clique "Connect with GitHub"
- Autorize Forge
- Ele mostra seus repositórios

GitLab:
- Clique "Connect with GitLab"
- Mesmos passos
```

### 4.3 Selecionar Repositório

```
Repository: seuusername/barbearia-api
Branch: main (ou production)
```

**Clique Install Repository**

Forge vai:
- ✅ Clonar seu repo
- ✅ Instalar composer (pode demorar 5-10 min)
- ✅ Gerar APP_KEY (se não existir)

---

## ⚙️ PASSO 5: CONFIGURAR VARIÁVEIS DE AMBIENTE (.env)

### 5.1 Acessar Ambiente Forge

No site do Forge, vá para **Environment**

### 5.2 Preencher `.env`

```env
#####################
# APLICAÇÃO
#####################
APP_NAME=Barbearia
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR

#####################
# DATABASE (Forge cria automaticamente)
#####################
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=barbearia_prod
DB_USERNAME=forge         # User padrão do Forge
DB_PASSWORD=SENHA_GERADA_PELO_FORGE  # Copiar de Forge

#####################
# EMAIL (SendGrid recomendado)
#####################
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.sua-chave-sendgrid-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seu-dominio.com.br
MAIL_FROM_NAME="Barbearia"

#####################
# ADMIN
#####################
ADMIN_NAME=Administrador
ADMIN_EMAIL=seu-email@seu-dominio.com.br
ADMIN_PASSWORD=AdminBarbearia@2025!Seguro#789

#####################
# SANCTUM
#####################
SANCTUM_EXPIRATION=1440

#####################
# RATE LIMITING
#####################
RATE_LIMIT_AUTH=5

#####################
# CORS
#####################
CORS_ALLOWED_ORIGINS=https://seu-frontend.com.br,https://admin.seu-dominio.com.br

#####################
# REDIS (Opcional - melhor performance)
#####################
# Se Forge tiver Redis:
CACHE_STORE=redis
QUEUE_CONNECTION=redis
# Se não tiver, usar:
# CACHE_STORE=database
# QUEUE_CONNECTION=database

#####################
# MERCADO PAGO (quando tiver credenciais)
#####################
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADO_PAGO_WEBHOOK_SECRET=
```

**Clique Save Environment**

---

## 🗄️ PASSO 6: CONFIGURAR BANCO DE DADOS

### 6.1 Criar Banco no Forge

Na página do servidor:

1. Vá para **Database** (ou clique CLI if available)
2. Criar banco: `barbearia_prod`
3. Usuário: `forge` (padrão, já tem acesso)

OU use SSH:

```bash
# SSH para o servidor
ssh -i ~/.ssh/id_rsa forge@seu-ip-do-droplet

# Acessar PostgreSQL (já está instalado)
sudo -u postgres psql

# Criar banco
CREATE DATABASE barbearia_prod OWNER forge;
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

# Sair
\q
exit
```

### 6.2 Verificar Variáveis

Voltando ao Forge, verificar que `.env` tem:

```env
DB_DATABASE=barbearia_prod
DB_USERNAME=forge
DB_PASSWORD=[a senha vai aparecer em Environment]
```

---

## 🚀 PASSO 7: DEPLOY AUTOMÁTICO

### 7.1 Configurar Deploy Script

No Forge, na página do site, vá para **Deploy Script**

Você vai ver isso:

```bash
#!/bin/bash

set -e

echo "Deploying application ..."

cd /home/forge/seu-dominio.com.br

git pull origin $FORGE_BRANCH

$COMPOSER install --no-interaction --prefer-dist --optimize-autoloader

if [ -f artisan ]; then
    $ARTIFACT cache:clear
    $ARTIFACT config:cache
    $ARTIFACT route:cache
    $ARTIFACT view:cache
    $ARTIFACT migrate --force
fi

echo "Application deployed!"
```

**Você pode deixar assim ou customizar.**

### 7.2 Primeiro Deploy Manual

No Forge, clique **Deploy Now** (ou **Manual Deploy**)

Ele vai:
- ✅ Fazer git pull
- ✅ Instalar dependências (composer install)
- ✅ Cache config/rotas
- ✅ Rodar migrações
- ✅ Pronto!

Tempo: ~3-5 minutos

---

## 🔒 PASSO 8: SSL/HTTPS (Let's Encrypt)

### 8.1 Se usar Domínio Real

No Forge, na página do site:

1. Clique **SSL Certificates**
2. Clique **Obtain Let's Encrypt Certificate**

```
Certificado para: seu-dominio.com.br (e www.seu-dominio.com.br)
```

**Clique Obtain Certificate**

Forge vai:
- ✅ Validar domínio
- ✅ Instalar certificado (gratuito, 3 meses de validade automática via cron)
- ✅ Redirecionar HTTP → HTTPS

### 8.2 Se usar IP direto

Deixa sem SSL por enquanto, depois adiciona domínio.

---

## ✅ PASSO 9: VERIFICAÇÕES PÓS-DEPLOY

### 9.1 Testar Site

```bash
# Testar HTTPS (se tiver domínio)
curl -I https://seu-dominio.com.br

# Resposta esperada:
# HTTP/2 200
# Server: nginx
# Strict-Transport-Security: max-age=31536000; includeSubDomains

# Testar API
curl https://seu-dominio.com.br/api/health

# Ou testar com IP
curl http://123.45.67.89/api/health
```

### 9.2 Ver Logs em Tempo Real

No Forge, clique no site → **Monitoring** 

Ou via SSH:

```bash
ssh forge@seu-ip-do-droplet
tail -f /home/forge/seu-dominio.com.br/storage/logs/laravel.log
```

### 9.3 Testar Login

```bash
curl -X POST https://seu-dominio.com.br/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste",
    "email": "teste@example.com",
    "password": "Teste@12345Forte",
    "password_confirmation": "Teste@12345Forte"
  }'
```

---

## 🔄 PASSO 10: DEPLOY AUTOMÁTICO COM GIT

### 10.1 Configurar Deploy Automático

No Forge, na página do site:

1. Clique **Deployments**
2. Ative **Auto Deploy**

```
Quando você faz:
  git push origin main

Forge automaticamente:
  ✅ Faz git pull
  ✅ Instala dependências
  ✅ Roda migrações
  ✅ Limpa cache
  ✅ Reinicia serviço (se necessário)
```

### 10.2 Webhook do GitHub/GitLab

Forge configura automaticamente.

Agora quando você faz:

```bash
# Local
git add .
git commit -m "Feature: Nova funcionalidade"
git push origin main

# Forge detecta automaticamente
# Deploy acontece em ~2-5 minutos
```

---

## 🔧 STEP 11: PROBLEMAS COMUNS E SOLUÇÕES

### Problema: "502 Bad Gateway"

```bash
# SSH no servidor
ssh forge@seu-ip

# Verificar status PHP-FPM
sudo systemctl status php8.2-fpm

# Reiniciar se necessário
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Problema: "No space left on device"

```bash
# Verificar espaço
df -h

# Limpar cache
cd /home/forge/seu-dominio.com.br
php artisan cache:clear
php artisan view:clear
```

### Problema: Migrações falhando

```bash
# SSH no servidor
ssh forge@seu-ip
cd /home/forge/seu-dominio.com.br

# Ver erro das migrações
php artisan migrate:status

# Reverter se necessário
php artisan migrate:rollback
php artisan migrate
```

### Problema: Email não funciona

```bash
# Testar email (no servidor)
php artisan tinker
> Mail::raw('Test', function($m) { 
    $m->to('seu-email@example.com')->subject('Teste'); 
  });
```

---

## 📊 PASSO 12: MONITORAMENTO

### 12.1 Uptime Monitoring

Configure no Forge ou use UptimeRobot:

```
https://uptimerobot.com
- Monitorar: https://seu-dominio.com.br/api/health
- Alertar se cair
- Enviar email se ficar offline 10+ min
```

### 12.2 Logs e Alertas

```bash
# Ver logs em tempo real (SSH no servidor)
ssh forge@seu-ip
tail -f /home/forge/seu-dominio.com.br/storage/logs/laravel.log

# Ver erros de Nginx
tail -f /var/log/nginx/error.log
```

### 12.3 Performance

Forge fornece:
- **Forge Dashboard**: RAM, CPU, Disk usage
- **Nginx Stats**: Requisições/segundo
- **Deploy History**: Histórico de deploys

---

## 🔐 PASSO 13: SEGURANÇA

### 13.1 SSH Keys

Forge já configura tudo automaticamente.

Para acessar o servidor:

```bash
# Sua chave SSH (gerada em preparação)
ssh -i ~/.ssh/id_rsa forge@seu-ip-do-droplet

# Ou configure no ~/.ssh/config:
Host barbearia-prod
  HostName seu-ip-do-droplet
  User forge
  IdentityFile ~/.ssh/id_rsa
  
# Depois é só:
ssh barbearia-prod
```

### 13.2 Firewall

Digital Ocean oferece Cloud Firewall.

Configure (opcional):

```
Entrada:
  - SSH (22) - apenas seu IP
  - HTTP (80) - qualquer um
  - HTTPS (443) - qualquer um
  
Saída:
  - Tudo liberado
```

### 13.3 Backup Automático

Forge oferece integração com backups.

Configure no Forge ou Digital Ocean:

```
Backup automático: Diárias (melhor) ou Semanais
Retention: 7-14 dias
```

---

## 📋 CHECKLIST FINAL

- [ ] Digital Ocean Droplet criado
- [ ] Laravel Forge server conectado
- [ ] Site criado no Forge
- [ ] Repositório Git conectado
- [ ] `.env` preenchido com valores corretos
- [ ] Banco de dados PostgreSQL criado
- [ ] Primeiro deploy feito com sucesso
- [ ] SSL/HTTPS funcionando (se usar domínio)
- [ ] Testes de requisição passando
- [ ] Logs verificados (sem erros críticos)
- [ ] Deploy automático ativado
- [ ] Uptime monitoring configurado

---

## 💰 CUSTO ESTIMADO

```
Digital Ocean Droplet:    $6-12/mês
Laravel Forge:            $12/mês
SendGrid (emails):        $20/mês (pague conforme uso, começa grátis 100/dia)
SSL (Let's Encrypt):      Gratuito ✅
Domain (Go Daddy, etc):   ~$12/ano
─────────────────────────────────
Total:                    ~$50-55/mês
```

---

## 🚀 PRÓXIMOS PASSOS

### Imediatamente:
1. Criar Droplet no Digital Ocean
2. Conectar no Forge
3. Fazer primeiro deploy

### Semana 1:
1. Testar com usuários
2. Configurar Mercado Pago (produção)
3. Monitorar logs

### Mês 1:
1. Análise de performance
2. Otimizar se necessário
3. Configurar backups

---

## 📞 SUPORTE

- **Forge Docs**: https://forge.laravel.com/docs
- **Digital Ocean Docs**: https://docs.digitalocean.com
- **Laravel**: https://laravel.com/docs

---

**Está pronto? Vamos começar! 🚀**
