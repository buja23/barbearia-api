# 🚀 Checklist de Segurança para Produção

## ✅ Segurança

- [ ] **APP_DEBUG=false** - Desabilitar modo debug
- [ ] **APP_ENV=production** - Definir ambiente como produção  
- [ ] **Cache Limpo** - `php artisan config:cache && php artisan route:cache`
- [ ] **Chave da App** - `php artisan key:generate` (se não existir)
- [ ] **Credentials Atualizadas** - Todas as variáveis de ambiente preenchidas corretamente
- [ ] **Banco de Dados Migrado** - `php artisan migrate --force`
- [ ] **Logs Rotacionados** - Usar `single` ou `stack` no LOG_CHANNEL
- [ ] **HTTPS Habilitado** - Certificado SSL válido
- [ ] **Rate Limiting** - Configurar `RATE_LIMIT_AUTH` apropriadamente
- [ ] **CORS Restrito** - `CORS_ALLOWED_ORIGINS` apenas com domínios confiáveis
- [ ] **Sanctum Expiration** - Tokens expiram em `SANCTUM_EXPIRATION` minutos

## 🔐 Configurações de Environment

### Variáveis Obrigatórias
```env
APP_DEBUG=false
APP_ENV=production
APP_URL=https://seu-dominio.com

DB_CONNECTION=pgsql
DB_HOST=seu-db-host
DB_DATABASE=seu-db-name
DB_USERNAME=seu-db-user
DB_PASSWORD=senha-forte-aleatorio

MERCADOPAGO_ACCESS_TOKEN=seu-token-producao
MERCADOPAGO_PUBLIC_KEY=sua-public-key
MERCADO_PAGO_WEBHOOK_SECRET=seu-webhook-secret

SANCTUM_EXPIRATION=1440
RATE_LIMIT_AUTH=5
CORS_ALLOWED_ORIGINS=https://seu-frontend.com,https://admin.seu-dominio.com

# Admin (MUDE ANTES DE PRODUÇÃO!)
ADMIN_EMAIL=seu-email-real@dominio.com
ADMIN_PASSWORD=senha-muito-forte-aleatorio-12345
```

## 📊 Performance

- [ ] **Query Caching** - Use `select:id,name,email` para não carregar dados desnecessários
- [ ] **N+1 Queries** - Verificar com `php artisan telescope` (se instalado)
- [ ] **Índices de Banco** - Confirmar que todas as ForeignKeys têm índices
- [ ] **Compressão Gzip** - Ativar em Nginx/Apache
- [ ] **Assets Minificados** - `npm run build` executado

## 🔒 Acesso e Permissões

- [ ] **Arquivo .env** - Nunca commitar em Git (adicionar ao `.gitignore`)
- [ ] **Diretório storage/** - Permissões 755, arquivos 644
- [ ] **Diretório bootstrap/cache/** - Permissões 755
- [ ] **SSH Keys** - Usar chaves SSH para acesso ao servidor
- [ ] **Firewall** - Portas 80, 443 abertas; outras fechadas
- [ ] **VHOST** - Apontar DocumentRoot para `public/`

## 📧 Emails

- [ ] **Mail Driver** - Usar SMTP real (não `log` ou `array`)
- [ ] **SPF/DKIM** - Configurar registros DNS
- [ ] **From Address** - Usar domínio verificado
- [ ] **Credenciais** - Separadas por ambiente (.env)

## 🧪 Testes

- [ ] **Unit Tests** - `php artisan test`
- [ ] **Feature Tests** - Testar fluxo de pagamento sem dinheiro real
- [ ] **Segurança** - Testar autenticação, rate limiting, validações
- [ ] **Backup** - Verificar plano de backup automático do banco

## 📈 Monitoramento

- [ ] **Logs** - Configurar rotação e armazenamento seguro
- [ ] **Sentry/Bugsnag** - Configurar rastreamento de erros
- [ ] **Uptime Monitoring** - Serviço tipo UptimeRobot
- [ ] **SSL Certificate** - Verificar validade e renovação automática

## 🗑️ Limpeza

- [ ] **Remover Seeder de Desenvolvimento** - Não rodar em produção
- [ ] **Remover Rotas Debug** - Verificar se há `dd()` ou `dump()` no código
- [ ] **Remover Comentários Sensíveis** - Credenciais comentadas, etc
- [ ] **Limpar Arquivos Temporários** - `php artisan storage:link`

## 🚨 Pós-Deploy

1. **Verificar Logs**: `tail -f storage/logs/laravel.log`
2. **Testar Auth**: `/api/login` e `/api/register`
3. **Testar Webhook**: Simular webhook do Mercado Pago
4. **Verificar Banco**: Confirmar migração de dados
5. **Teste de Carga**: Usar ferramenta como Apache Bench ou K6

---

**Última Atualização**: 2026-03-11
