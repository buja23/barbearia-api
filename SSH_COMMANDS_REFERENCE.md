# 🛠️ SSH COMMANDS & TROUBLESHOOTING - FORGE + DIGITAL OCEAN

**Use estes comandos via SSH no servidor Forge**

---

## 🔑 CONECTAR VIA SSH

### Opção 1: Direto

```bash
ssh forge@SEU-IP-OU-DOMINIO
# Exemplo: ssh forge@123.45.67.89
```

### Opção 2: Com Chave SSH Específica

```bash
ssh -i ~/.ssh/id_rsa forge@123.45.67.89
```

### Opção 3: Configurar ~/.ssh/config

```bash
# Adicione no arquivo ~/.ssh/config:
Host barbearia-prod
  HostName 123.45.67.89
  User forge
  IdentityFile ~/.ssh/id_rsa
  
# Depois é só:
ssh barbearia-prod
```

---

## 📁 NAVEGAÇÃO NO SERVIDOR

```bash
# Entrar no diretório do site
cd /home/forge/seu-dominio.com.br

# Listar arquivos
ls -la

# Ver estrutura
tree -L 2

# Tamanho dos diretórios
du -sh *
```

---

## 📋 COMANDOS ARTISAN (via SSH)

### Ver Logs em Tempo Real

```bash
# Log principal
tail -f storage/logs/laravel.log

# Apenas erros
tail -f storage/logs/laravel.log | grep -i error

# Últimas 50 linhas
tail -50 storage/logs/laravel.log

# Procurar por string
grep "payment" storage/logs/laravel.log
```

### Cache e Configuração

```bash
# Limpar todos os caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Recriar caches (para produção)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ver configured values
php artisan config:show APP_ENV
php artisan config:show DB_HOST
```

### Banco de Dados

```bash
# Verificar migrations
php artisan migrate:status

# Rodar migrations
php artisan migrate

# Se estiver em production, adicionar --force
php artisan migrate --force

# Rollback última migration
php artisan migrate:rollback

# Refreshar (cuidado! DELETA dados)
php artisan migrate:refresh --force
```

### Testes

```bash
# Rodar todos os testes
php artisan test

# Apenas Feature tests
php artisan test --filter=Feature

# Apenas Unit tests
php artisan test --filter=Unit

# Com output verboso
php artisan test -v

# Parar no primeiro erro
php artisan test --stop-on-failure
```

### Seeds (dados iniciais)

```bash
# Rodar seeder específico
php artisan db:seed --class=DatabaseSeeder

# Todos os seeders
php artisan db:seed

# Com migration (cuidado!)
php artisan migrate:refresh --seed --force
```

### Tinker (Terminal Interativo PHP)

```bash
# Entrar no Tinker
php artisan tinker

# Dentro do Tinker:
> User::count()        # Contar usuários
> User::first()        # Primeiro usuário
> User::find(1)        # Usuário com ID=1
> DB::statement('SELECT COUNT(*) FROM users')
> exit                 # Sair
```

---

## 🔧 COMANDOS DO SERVIDOR

### Status de Serviços

```bash
# PHP-FPM
sudo systemctl status php8.2-fpm

# Nginx
sudo systemctl status nginx

# PostgreSQL
sudo systemctl status postgresql

# Redis (se instalado)
sudo systemctl status redis-server
```

### Reiniciar Serviços

```bash
# PHP-FPM
sudo systemctl restart php8.2-fpm

# Nginx
sudo systemctl restart nginx

# PostgreSQL
sudo systemctl restart postgresql

# Todos
sudo systemctl restart php8.2-fpm nginx postgresql
```

### Ver Logs do Sistema

```bash
# Nginx errors
tail -f /var/log/nginx/error.log

# Nginx access
tail -f /var/log/nginx/access.log

# PHP-FPM
tail -f /var/log/php8.2-fpm.log

# Sistema geral
journalctl -xe
```

---

## 💾 BANCO DE DADOS (PostgreSQL)

### Acessar PostgreSQL

```bash
# Como root
sudo -u postgres psql

# Como user forge
psql -U forge -d barbearia_prod -h 127.0.0.1
```

### Comandos SQL Úteis

```sql
-- Ver all databases
\l

-- Conectar database
\c barbearia_prod

-- Ver all tables
\dt

-- Ver estrutura de uma table
\d users

-- Contar registros
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM appointments;
SELECT COUNT(*) FROM orders;

-- Ver últimos usuários
SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT 5;

-- Sair
\q
```

### Backup e Restore

```bash
# Backup (fora do psql)
pg_dump -U forge barbearia_prod > backup_$(date +%Y%m%d).sql

# Restore
psql -U forge barbearia_prod < backup_20260311.sql

# Comprimir backup
pg_dump -U forge barbearia_prod | gzip > backup_$(date +%Y%m%d).sql.gz
```

---

## 📊 MONITORAMENTO

### Uso de Recursos

```bash
# CPU e Memória em tempo real
top

# Sair do top: pressione 'q'

# Mais detalhado
htop  # Se instalado

# Espaço em disco
df -h

# Espaço detalhado por diretório
du -sh /home/forge/*

# Processos específicos
ps aux | grep php
ps aux | grep nginx
```

### Conectividade

```bash
# Testar se nginx responde
curl -I http://localhost

# Testar API
curl http://localhost/api/health

# Com verbose
curl -v http://localhost/api/health

# Testar POST
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@test.com","password":"Test@123456"}'
```

---

## 🚨 TROUBLESHOOTING

### Problema: 502 Bad Gateway

```bash
# Verificar status PHP-FPM
sudo systemctl status php8.2-fpm

# Ver logs PHP-FPM
tail -f /var/log/php8.2-fpm.log

# Reiniciar
sudo systemctl restart php8.2-fpm

# Se ainda não funcionar, reiniciar nginx
sudo systemctl restart nginx
```

### Problema: "No space left on device"

```bash
# Ver uso de disco
df -h

# Procurar arquivos grandes
find /home/forge -size +100M -type f

# Limpar logs antigos
rm /home/forge/seu-dominio/storage/logs/*.log

# Limpar Laravel cache
rm -rf /home/forge/seu-dominio/bootstrap/cache/*
rm -rf /home/forge/seu-dominio/storage/framework/cache/*

# Limpar npm/composer cache (se houver)
npm cache clean --force
composer clear-cache
```

### Problema: Database Connection Refused

```bash
# Verificar se PostgreSQL está rodando
sudo systemctl status postgresql

# Ver logs PostgreSQL
sudo tail -f /var/log/postgresql/postgresql.log

# Reiniciar
sudo systemctl restart postgresql

# Testar conexão
psql -U forge -h 127.0.0.1 -d barbearia_prod -c "SELECT 1;"
```

### Problema: Email não funciona

```bash
# Testar conexão SMTP
telnet smtp.sendgrid.net 587

# Ver logs de email no Laravel
grep -i "mail" storage/logs/laravel.log

# Testar envio via Tinker
php artisan tinker
> Mail::raw('Test', fn($m) => $m->to('seu-email@test.com')->subject('Test'));
```

### Problema: Migrações falhando

```bash
# Ver status
php artisan migrate:status

# Ver erro específico
php artisan migrate --step

# Reverter última migration
php artisan migrate:rollback

# Reverter tudo
php artisan migrate:refresh --force --seed

# Executar novamente
php artisan migrate --force
```

---

## 🔐 SEGURANÇA

### Ver Permissões de Arquivos

```bash
# Ver permissões
ls -la storage/

# Verificar .env (deve ser 600)
ls -la .env

# Se precisar ajustar
chmod 600 .env
chmod 755 storage
chmod 777 storage/logs storage/framework
```

### Ver Quem está conectado via SSH

```bash
who

# Processos ativos
w

# Histórico de login
lastlog

# Último login
last
```

---

## 📈 PERFORMANCE

### Cache Status

```bash
# Verificar cache
php artisan config:show CACHE_STORE

# Limpar cache
php artisan cache:clear

# Recriar cache
php artisan config:cache
php artisan route:cache
```

### Database Performance

```bash
# Número de conexões
psql -U forge -c "SELECT count(*) FROM pg_stat_activity;"

# Queries lentas (fazer em psql)
SELECT query, calls, mean_time FROM pg_stat_statements 
ORDER BY mean_time DESC LIMIT 10;
```

---

## 📝 ARQUIVO ÚTIL: Script de Status

Crie arquivo `status.sh`:

```bash
#!/bin/bash

echo "=== SERVIÇOS ==="
sudo systemctl status php8.2-fpm | grep Active
sudo systemctl status nginx | grep Active
sudo systemctl status postgresql | grep Active

echo ""
echo "=== RECURSOS ==="
df -h | grep /dev/
free -h

echo ""
echo "=== ÚLTIMOS ERROS ==="
tail -5 storage/logs/laravel.log

echo ""
echo "=== BANCO DE DADOS ==="
psql -U forge -d barbearia_prod -c "SELECT COUNT(*) as total_users FROM users;"
```

Usar:
```bash
bash status.sh
```

---

## 🆘 QUANDO NADA FUNCIONA

```bash
# 1. Verificar conexão
ping 8.8.8.8

# 2. Verificar DNS
nslookup google.com

# 3. Reboot último recurso (vai derrubar site momentaneamente!)
sudo reboot

# 4. Ver se veio do reboot
journalctl -b0
```

---

## 📚 MAIS INFORMAÇÕES

```bash
# Help do artisan
php artisan

# Help de um comando
php artisan migrate --help

# Manual do PostgreSQL
man psql

# Manual do nginx
man nginx
```

---

**Estes são os comandos mais úteis. Sempre use `--help` se tiver dúvida sobre um comando!**
