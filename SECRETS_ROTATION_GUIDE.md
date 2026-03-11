# 🔐 Guia de Rotação de Secrets

## O que são Secrets?

Secrets são dados sensíveis usados pela aplicação:
- `APP_KEY` - Chave de criptografia da aplicação
- `MERCADOPAGO_ACCESS_TOKEN` - Token de acesso ao Mercado Pago
- `MERCADO_PAGO_WEBHOOK_SECRET` - Chave para validar webhooks
- Senhas de banco de dados
- Credenciais de APIs externas

## Por que Rotacionar?

- **Segurança**: Se um secret vazar, você limita o tempo de exposição
- **Conformidade**: Requisitos regulatórios (PCI-DSS, etc)
- **Prevenção**: Casos de comprometimento de servidor
- **Auditoria**: Mantém registro de histórico

---

## 📅 Cronograma Recomendado

| Secret | Frequência | Criticidade |
|--------|-----------|------------|
| `APP_KEY` | 6-12 meses | ALTA |
| `MERCADOPAGO_*` | 6-12 meses | ALTA |
| `DB_PASSWORD` | 3-6 meses | CRÍTICA |
| Senhas Admin | 3 meses | CRÍTICA |

---

## 🔄 Processo de Rotação

### 1️⃣ **APP_KEY**

```bash
# 1. Gerar nova chave
php artisan key:generate

# 2. Verificar se foi gerada em .env
grep APP_KEY .env

# 3. Deploy para produção
git add .env
git commit -m "Rotate APP_KEY"
git push

# 4. Executar em produção
# Nenhum comando extra necessário (Laravel gerencia automaticamente)
```

**⚠️ AVISO**: Isso **INVALIDARÁ** qualquer encrypted data antiga. Use com cuidado em produção.

---

### 2️⃣ **MERCADO PAGO**

```bash
# 1. Ir ao painel: https://www.mercadopago.com.br/developers/panel

# 2. Gerar novo Access Token:
#    - Settings > API Keys > Regenerate

# 3. Atualizar .env:
MERCADOPAGO_ACCESS_TOKEN=seu-novo-token
MERCADOPAGO_PUBLIC_KEY=sua-nova-public-key
MERCADO_PAGO_WEBHOOK_SECRET=seu-novo-webhook-secret

# 4. Atualizar Webhook URL no MP para ponto correto

# 5. Testar webhook:
curl -X POST https://seu-dominio.com/api/webhooks/mercadopago \
  -H "x-signature: test" \
  -H "x-request-id: test" \
  -d '{"type":"payment"}'

# 6. Deploy
git add .env
git commit -m "Rotate Mercado Pago credentials"
git push
```

---

### 3️⃣ **BANCO DE DADOS**

⚠️ **MAIS COMPLEXO** - Requer downtime mínimo

```bash
# 1. Em DEV: Gerar senha nova forte
PASSWORD_OLD="current_password"
PASSWORD_NEW="NewSecure@12345$(date +%s)"

# 2. Conectar ao banco de produção:
# (Usar ferramenta de admin do DB - pgAdmin, MySQL Workbench, etc)

# 3. Alterar senha do usuário:
# PostgreSQL:
ALTER USER sail WITH PASSWORD 'NewSecure@12345';

# MySQL:
ALTER USER 'sail'@'localhost' IDENTIFIED BY 'NewSecure@12345';
FLUSH PRIVILEGES;

# 4. Atualizar .env em produção:
DB_PASSWORD=NewSecure@12345

# 5. Testar conexão:
php artisan tinker
>>> DB::connection()->getPdo();
# Deve retornar a conexão sem erro

# 6. Se tudo ok, restart da aplicação:
php artisan cache:clear
php artisan config:clear
systemctl restart php-fpm  # ou seu service
```

---

### 4️⃣ **ADMIN PASSWORD**

```bash
# 1. Login como Admin em Filament

# 2. Ir para Settings > Update Profile > Change Password

# 3. OU via CLI:
php artisan tinker
>>> $user = User::where('role', 'admin')->first();
>>> $user->update(['password' => Hash::make('NewAdmin@12345')]);
>>> $user->currentAccessToken()->delete();  // Logout todos os tokens antigos
```

---

## 🔍 Auditoria de Secrets

### Script para Verificar Exposição

```bash
#!/bin/bash
# scan_exposed_secrets.sh

echo "🔍 Procurando por Secrets expostos no repositório..."

# Procurar por padrões comuns
PATTERNS=(
    "APP_KEY="
    "MERCADOPAGO_ACCESS_TOKEN="
    "DB_PASSWORD="
    "ADMIN_PASSWORD="
    "api_key"
    "secret_key"
    "authorization: Bearer"
)

for pattern in "${PATTERNS[@]}"; do
    echo "Procurando: $pattern"
    git log -p --all -S "$pattern" | grep -v "^--" | head -20
done

echo "✅ Scan completo"
```

### Checklist de Segurança

- [ ] Nenhum secret em `.env.example` (apenas placeholders)
- [ ] Nenhum secret no `.git` history
- [ ] Nenhun secret em comentários do código
- [ ] `.env` está em `.gitignore`
- [ ] Senhas armazenadas com `Hash::make()` apenas
- [ ] Dados sensíveis com `encrypted` no Eloquent

---

## 📊 Audit Trail

Manter registro de rotações:

```txt
📄 secrets_rotation_log.txt

Data: 2026-03-11
Tipo: APP_KEY
Responsável: DevOps Team
Motivo: Rotação programada
Status: ✅ Sucesso

Data: 2026-03-05
Tipo: DB_PASSWORD
Responsável: Security Team
Motivo: Suspeita de comprometimento
Status: ✅ Sucesso com mitigação
```

---

## 🚨 Caso de Compromiso

Se um secret vazar:

1. **IMEDIATAMENTE**:
   - [ ] Desativar a chave/token no painel correspondente
   - [ ] Regenerar nova chave
   - [ ] Atualizar `.env` em PROD

2. **DENTRO DE 1 HORA**:
   - [ ] Revisar logs de auditoria para acessos suspeitos
   - [ ] Notificar time de segurança

3. **DENTRO DE 24 HORAS**:
   - [ ] Relatório de incidente
   - [ ] Plano de resposta

---

**Última atualização**: 2026-03-11
