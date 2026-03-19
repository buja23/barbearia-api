# 🚀 DEPLOY IMPRÍVEL CHECKLIST - LARAVEL FORGE + DIGITAL OCEAN

Imprima este documento e marque conforme progride!

---

## 📅 DATA: ________________     HORA INÍCIO: ________________

---

## 🎯 FASE 1: PREPARAÇÃO LOCAL (30 MIN)

```
Antes de fazer qualquer coisa, prepare tudo localmente:

[ ] Verificar que projeto não tem erros
    $ composer audit
    ✓ Resultado: 0 vulnerabilidades

[ ] Verificar testes passam
    $ php artisan test
    ✓ Alguns testes passam

[ ] .env atualizado com APP_ENV=production
    $ grep APP_ENV .env
    ✓ APP_ENV=production

[ ] APP_DEBUG=false
    $ grep APP_DEBUG .env
    ✓ APP_DEBUG=false

[ ] Código commitado em Git
    $ git status
    ✓ Nothing to commit

[ ] Ter conta SendGrid criada
    ✓ API Key: SG._________________ 
    
[ ] Ter seu email pronto
    ✓ Email: ________________________________
```

**TEMPO FASE 1**: _________ (esperado: 5-10 min)

---

## 🔷 FASE 2: DIGITAL OCEAN SETUP (10 MIN)

```
[ ] Conta DigitalOcean criada
    www.digitalocean.com
    
[ ] Meio de pagamento adicionado
    Console → Billing → Add Payment
    
[ ] Droplet criado
    Settings:
    - Ubuntu 24.04 LTS
    - Region: São Paulo (fra) / Asia (sgp)
    - Size: $6 (2GB RAM, 1x CPU, 50GB disk)
    - Hostname: barbearia-api-prod
    
[ ] Droplet está ACTIVE (verde)
    ✓ Aguardado: ~3 minutos
    
[ ] IP do Droplet copiado
    ✓ IP: ___________________________
    
[ ] SSH Key configurada (se usar password, OK também)
```

**TEMPO FASE 2**: _________ (esperado: 10 min)

**IP NA MINHA NOTA**: ___________________________

---

## 🟠 FASE 3: LARAVEL FORGE SETUP (15 MIN)

```
[ ] Conta Forge criada
    www.forge.laravel.com
    
[ ] Forge conectado com DigitalOcean
    Create Server → DigitalOcean
    → Authorize → Pop-up autorização
    
[ ] Server sendo criado no Forge
    Name: barbearia-api-prod
    Provider: DigitalOcean
    Database: PostgreSQL
    → Create Server (até 10 min)
    
[ ] Server ficou ACTIVE (verde)
    Dashboard → Server deve estar verde
    
[ ] Domínio do site pronto (ou IP)
    Site Domain: seu-dominio.com.br OU
    Site Domain: 123.45.67.89 (IP)
    
[ ] Site criado no Forge
    Create Site → Laravel → PHP 8.2 → Create
    
[ ] Repositório conectado
    Source Control → Connect Repository
    → GitHub/GitLab → Authorize
    → Select: seu-repo/barbearia-api
    → Branch: main
    → Install Repository (5-10 min)
```

**TEMPO FASE 3**: _________ (esperado: 15 min)

**TOTAL TEMPO ATÉ AQUI**: _________ (esperado: ~35 min)

---

## 💚 FASE 4: CONFIGURAR ENVIRONMENT & BANCO (10 MIN)

```
[ ] Ir para Site → Environment

[ ] COPIAR E COLAR todo o .env abaixo (veja FORGE_ENV_SETUP.md)
    ✓ APP_ENV=production
    ✓ APP_DEBUG=false
    ✓ APP_KEY=[copiado automático]
    ✓ DB_PASSWORD=[copiar do Forge Database tab]
    ✓ MAIL_PASSWORD=[SG.sua-chave-sendgrid]
    ✓ ADMIN_EMAIL=[seu-email@seu-dominio.com.br]

[ ] Clicou SAVE no Forge
    ✓ Environment salvo (deve ter check verde)

[ ] Banco de dados criado
    [ ] Opção A: CLI do Forge
    [ ] Opção B: SSH manual:
        ssh forge@123.45.67.89
        sudo -u postgres psql
        CREATE DATABASE barbearia_prod;
        \q
        
[ ] DB_PASSWORD está correto
    $ curl -I http://seu-ip/api/health (depois)
    ✓ Não deve dar erro de conexão
```

**TEMPO FASE 4**: _________ (esperado: 10 min)

---

## 🟢 FASE 5: DEPLOY (5-10 MIN)

```
[ ] Ir para Site → Deploy Script
    (verificar que está correto, deixa padrão)

[ ] Clicou "Deploy Now"
    ✓ Status muda para: Deploying...
    ✓ Aguardar verde (3-5 min)
    
[ ] Deploy terminou
    ✓ Status: verde com "Deployed"
    ✓ Nenhuma mensagem de erro (red)

[ ] Ver logs do deploy
    Clique em deploy para ver detalhes
    ✓ Deve ter: "Application deployed!"
```

**TEMPO FASE 5**: _________ (esperado: 5-10 min)

---

## ✅ FASE 6: VALIDAÇÃO (5 MIN)

```
[ ] Testar conexão HTTP
    $ curl -I http://seu-ip/
    ✓ Resposta 200 ou 301 (redirect para HTTPS)

[ ] Testar API
    $ curl http://seu-ip/api/health
    ✓ Resposta em JSON

[ ] Ver logs
    SSH: ssh forge@seu-ip
    $ tail -f storage/logs/laravel.log
    ✓ Sem erros críticos (FATAL, ERROR)

[ ] Testar registro
    $ curl -X POST http://seu-ip/api/register \
      -H "Content-Type: application/json" \
      -d '{"name":"Test","email":"test@example.com",...}'
    ✓ Resposta: {"access_token":"..."}

[ ] Banco conecta
    SSH: ssh forge@seu-ip
    $ php artisan migrate:status
    ✓ Migrations: todos "Yes" (executados)
```

**TEMPO FASE 6**: _________ (esperado: 5 min)

---

## 🎉 FASE 7: FINALIZAÇÃO (5 MIN)

```
[ ] Auto Deploy ativado
    Site → Deployments → Toggle "Auto Deploy"
    
[ ] Domínio apontado (OPCIONAL agora)
    [ ] Se usar domínio real:
        1. Compre domínio (Godaddy, Namecheap)
        2. Configure DNS para: seu-ip-forge
        3. Espere propagar (até 24h)
        4. Em Forge: Site → SSL → Obtain Let's Encrypt
    
[ ] Uptime monitoring (OPCIONAL)
    [ ] Configure UptimeRobot (gratuito):
        https://uptimerobot.com
        Monitor: http://seu-ip/api/health
        Alert: seu-email@dominio.com

[ ] Backup automático
    [ ] Digital Ocean: Enable Backups (ou $ script)
    [ ] Database: Cron backup diário
```

**TEMPO FASE 7**: _________ (esperado: 5 min)

---

## 📊 RESULTADO FINAL

```
┌─────────────────────────────────────────┐
│         STATUS FINAL                    │
├─────────────────────────────────────────┤
│  ✓ API Online:  http://seu-ip           │
│  ✓ Database:    PostgreSQL ativo        │
│  ✓ Logs:        Limpos, sem erros       │
│  ✓ Deploy:      Automático habilitado   │
│                                         │
│  🚀 SITE ESTÁ PRONTO PARA USAR!         │
└─────────────────────────────────────────┘
```

**TEMPO TOTAL**: _________ (esperado: 45 min)

---

## 🆘 PROBLEMAS DURANTE DEPLOY

### Se Deploy ficou RED:

```
1. Clique no deploy vermelho
2. Ver mensagem de erro
3. Comuns:
   - "DB connection failed" → DB_PASSWORD errado
   - "Not found app.key" → APP_KEY vazio
   - "Syntax error .env" → Caracteres inválidos
4. Corrigir em Site → Environment
5. Fazer Deploy novamente
```

### Se API retorna 502:

```
1. SSH: ssh forge@seu-ip
2. Reiniciar: sudo systemctl restart php8.2-fpm
3. Verificar logs: tail -f storage/logs/laravel.log
4. Se erro persiste, ver: tail -f /var/log/php8.2-fpm.log
```

### Se banco não conecta:

```
1. Verificar DB_PASSWORD está certo
2. SSH e testar: psql -U forge -d barbearia_prod
3. Se não existe database:
   sudo -u postgres psql
   CREATE DATABASE barbearia_prod;
4. Re-deploy
```

---

## 📝 ANOTAÇÕES

```
Meu IP do Droplet:
_____________________________

Meu Domain (se tiver):
_____________________________

SendGrid API Key (SG.):
_____________________________

Admin Email:
_____________________________

Banco PostgreSQL Password:
_____________________________

URLs importantes:
- Forge Dashboard: https://forge.laravel.com
- Digital Ocean: https://cloud.digitalocean.com
- Site: http://_____________________________
```

---

## 🎉 PRONTO!

```
SEU SITE ESTÁ NO AR!

🌐 Acesse: http://seu-ip-ou-dominio
📱 API: http://seu-ip-ou-dominio/api/health
👤 Admin (depois): https://seu-dominio/admin
```

---

**Data Conclusão: ________________     Hora Fim: ________________**

**Total de Tempo Gasto: ________________**

**Pronto para Produção? ☑ SIM  ☐ Há problemas**

