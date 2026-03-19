# ✅ CHECKLIST RÁPIDO - PRODUÇÃO (PRINT E USE)

```
╔═══════════════════════════════════════════════════════════╗
║     BARBEARIA API - VERIFICAÇÃO PRÉ-DEPLOY             ║
║     Imprima ou screeshot este documento                  ║
╚═══════════════════════════════════════════════════════════╝

[  ] 1. APP_ENV = production (NÃO local)
[  ] 2. APP_DEBUG = false
[  ] 3. APP_URL = https://seu-dominio (COM https://)
[  ] 4. SSL/HTTPS com certificado válido
[  ] 5. ADMIN_PASSWORD alterada (NÃO padrão)
[  ] 6. DB_PASSWORD alterada (NÃO padrão)
[  ] 7. DB_HOST = servidor de produção
[  ] 8. MAIL_MAILER = smtp (NÃO log)
[  ] 9. SMTP credentials configuradas
[  ] 10. CORS sem localhost (HTTPS apenas)

[  ] 11. chmod 600 .env (arquivo seguro)
[  ] 12. .env em .gitignore (NÃO em git)
[  ] 13. Banco de dados criado e vazio
[  ] 14. php artisan migrate --force (executado)
[  ] 15. php artisan config:cache (executado)
[  ] 16. php artisan route:cache (executado)
[  ] 17. Backup automático configurado
[  ] 18. Permissões: storage/ + bootstrap/cache/
[  ] 19. Firewall: portas 80, 443 abertas
[  ] 20. Testar curl -I https://seu-dominio (200 OK)

STATUS: _____ of 20 (Marque quantos completou)
```

---

## 🔥 TOP 5 ERROS A EVITAR

1. ❌ Deixar APP_ENV=local → ✅ APP_ENV=production
2. ❌ Deixar ADMIN_PASSWORD=ChangeMe@12345 → ✅ Alterar para senha forte
3. ❌ Esquecer php artisan config:cache → ✅ Executar!
4. ❌ Não usar HTTPS (deixar http://) → ✅ Usar https://
5. ❌ Deixar CORS com localhost → ✅ Remover localhost

---

## 📊 TEMPOS ESTIMADOS

| Tarefa | Tempo |
|--------|-------|
| Preparar .env | 10 min |
| Configurar SSL | 5 min |
| Deploy código | 5 min |
| Rodar migrações | 2 min |
| Cache | 1 min |
| Testes | 10 min |
| **TOTAL** | **~30 min** |

---

## 🆘 SE ALGO DER ERRADO

```bash
# 1. Ver logs em tempo real
tail -f storage/logs/laravel.log

# 2. Ver último erro
tail -20 storage/logs/laravel.log

# 3. Limpar cache se houver erro estranho
php artisan cache:clear
php artisan config:clear

# 4. Verificar banco conecta
php artisan db:show

# 5. Testar endpoint
curl https://seu-dominio.com/api/health
```

---

## 🎯 SUCESSO = VER ISSO

```bash
$ curl -I https://seu-dominio.com/api/health

HTTP/2 200
date: Mon, 11 Mar 2026 10:30:00 GMT
content-type: application/json
strict-transport-security: max-age=63072000;
x-content-type-options: nosniff
x-frame-options: SAMEORIGIN
```

---

## 📱 TESTES ESSENCIAIS PÓS-DEPLOY

```bash
# Teste 1: HTTPS OK?
curl -I https://seu-dominio.com
# ✅ HTTP/2 200

# Teste 2: API responde?
curl https://seu-dominio.com/api/health
# ✅ {"status":"ok"} (ou similar)

# Teste 3: Rate limiting funciona?
for i in {1..6}; do
  curl -X POST https://seu-dominio.com/api/login \
    -H "Content-Type: application/json" \
    -d '{\"email\":\"x\",\"password\":\"x\"}'
done
# ✅ 6ª requisição = 429 Too Many Requests

# Teste 4: Logs estão limpos?
grep -i "fatal\\|error\\|warning" storage/logs/laravel.log
# ✅ Nenhuma linha ou apenas info logs
```

---

## 💬 SUPORTE DO LARAVEL

- Docs: https://laravel.com/docs/11
- Community: https://discord.gg/laravel
- Stack Overflow: tag `laravel`

---

**Imprima este checklist e marque conforme completa!**
```
