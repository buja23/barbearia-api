# 📋 .ENV PRONTO PARA COPIAR NO FORGE

**Copie e cole isto em: Forge → Site → Environment**

---

## ⚠️ ANTES DE COLAR

Você precisa ter:

1. **SendGrid Account**: 
   - Ir para: https://sendgrid.com
   - Criar chave API
   - Copiar chave (começa com `SG.`)

2. **Seu Email Real**:
   - Seu email pessoal ou do domínio
   - Será o ADMIN

3. **Seu Domínio** (opcional):
   - Pode ser deixado em branco se usar IP

---

## 📋 .ENV COMPLETO (COPIE E COLE)

```env
#####################
# APLICAÇÃO
#####################
APP_NAME=Barbearia
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:SAQMFExUs4ymz1Tzbo26bt6enpI9eHpKyx4kcltM7W4=
APP_URL=https://barbearia.local
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

#####################
# LOGGING
#####################
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

#####################
# DATABASE - PostgreSQL (Forge fornece)
#####################
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=barbearia_prod
DB_USERNAME=forge
DB_PASSWORD=[COPIAR DE FORGE: Database tab - password field]

#####################
# SESSION
#####################
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

#####################
# BROADCAST
#####################
BROADCAST_CONNECTION=log

#####################
# FILE STORAGE
#####################
FILESYSTEM_DISK=local

#####################
# QUEUE & CACHE
#####################
QUEUE_CONNECTION=database
CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

#####################
# REDIS (Opcional - deixar comentado)
#####################
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

#####################
# EMAIL - SendGrid (SMTP)
#####################
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=[COPIAR DE SENDGRID: API Key começa com SG.]
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@seu-dominio.com.br
MAIL_FROM_NAME="Barbearia"

#####################
# AWS (deixar vazio - não usar por enquanto)
#####################
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

#####################
# VITE (Frontend)
#####################
VITE_APP_NAME="Barbearia"

#####################
# ADMIN CREDENTIALS
#####################
ADMIN_NAME=Administrador
ADMIN_EMAIL=[TROCAR PARA SEU EMAIL: seu-email@seu-dominio.com.br]
ADMIN_PASSWORD=AdminBarbearia@2025!Seguro#789

#####################
# SANCTUM TOKEN
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
# MERCADO PAGO (deixar vazio por enquanto)
#####################
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADO_PAGO_WEBHOOK_SECRET=
MERCADOS_PAGO_TEST_CPF=19119119100
```

---

## 🔄 PASSO A PASSO NO FORGE

### 1. Acessar Environment

```
Forge Dashboard
→ Seu Server
→ Seu Site (seu-dominio.com.br)
→ Tab "Environment"
```

### 2. Copiar o .env Acima

- [ ] Copie TODO o texto acima (exceto "MERCADO PAGO" que está vazio)

### 3. Alterações Necessárias (IMPORTANTE!)

Antes de colar, procure e altere APENAS estes valores:

**A. SendGrid API Key:**
```
Ir para: https://sendgrid.com
Profile → API Keys → Create API Key
Copiar a chave (começa com SG.)
Colar em: MAIL_PASSWORD=SG.sua-chave-aqui
```

**B. Seu Email:**
```
Alterar: ADMIN_EMAIL=seu-email@seu-dominio.com.br
Exemplo: ADMIN_EMAIL=joao@seudominio.com.br
```

**C. Seu Domínio (se tiver):**
```
Alterar: APP_URL=https://seu-dominio-real.com.br
Alterar: MAIL_FROM_ADDRESS=noreply@seu-dominio-real.com.br
Alterar: CORS_ALLOWED_ORIGINS=https://seu-dominio-real.com.br
```

**D. DB_PASSWORD (do Forge):**
```
No Forge:
1. Clique em "Database" (ou "Files")
2. Procure a senha do "forge" user
3. Copiar em: DB_PASSWORD=senha-do-forge
```

### 4. Colar no Forge

1. Na caixa "Environment" do Forge
2. Limpar conteúdo anterior (Ctrl+A, Delete)
3. Colar o .env (Ctrl+V)
4. Clique **SAVE**

---

## ✅ APÓS SALVAR

Forge vai:
- ✅ Validar sintaxe
- ✅ Salvar variáveis
- ✅ Reiniciar aplicação

Se mostrou **green check** = Sucesso!

Se mostrou **red X** = Há erro (procure a linha com problema)

---

## 🔐 EXPLICAÇÃO DOS VALORES

| Variável | O que é | Valor |
|----------|---------|-------|
| APP_ENV | Modo produção | `production` |
| APP_DEBUG | Mostrar erros? | `false` (NUNCA true) |
| APP_KEY | Chave de criptografia | Deixar como está |
| DB_HOST | IP do banco | `127.0.0.1` (localhost) |
| DB_PASSWORD | Senha do banco | Copiar do Forge |
| MAIL_USERNAME | SMTP username | `apikey` (SendGrid) |
| MAIL_PASSWORD | SMTP password | Sua chave SendGrid |
| ADMIN_PASSWORD | Senha de acesso | Predefini uma forte |
| SANCTUM_EXPIRATION | Token válido por | `1440` min = 24h |
| RATE_LIMIT_AUTH | Max tentativas login | `5` por minuto |

---

## 🚨 ERROS COMUNS

### ❌ "MAIL_PASSWORD invalid"

```
Solução:
1. Ir em https://sendgrid.com
2. Criar nova API Key
3. Copiar chave
4. Colar em MAIL_PASSWORD
5. Salvar
```

### ❌ "DB_PASSWORD rejected"

```
Solução:
1. Clicar "Database" no Forge
2. Copiar a senha exata
3. Colar em DB_PASSWORD
4. Salvar
```

### ❌ "ADMIN_EMAIL inválido"

```
Solução:
Email deve ser: usuario@dominio.com
NÃO pode ter espaços ou caracteres especiais
```

---

## 🎯 PRÓXIMO PASSO

Depois de salvar:

1. Volte para "Deploy Script"
2. Clique **Deploy Now**
3. Aguarde 3-5 minutos
4. Status deve virar VERDE ✅

Se virar RED, clique para ver erro nos logs.

---

## 📞 CHECKLIST FINAL

- [ ] SendGrid API Key copiada
- [ ] Email pessoal configurado
- [ ] DB_PASSWORD do Forge copiado
- [ ] .env colado no Forge
- [ ] Clicou SAVE
- [ ] Sem erros (green check)
- [ ] Deploy feito
- [ ] Status verde

**Pronto! 🚀**
