# 🔒 Recomendações de Segurança Adicionais

## 1. **Validação de Entrada (Input Validation)**

Todos os controllers devem validar entradas rigorosamente:

```php
// ✅ BOM - Validação completa
$request->validate([
    'email' => 'required|email|max:255',
    'phone' => 'required|regex:/^\d{10,11}$/', // Apenas números
    'price' => 'required|numeric|min:0.01|max:9999.99',
]);

// ❌ RUIM - Sem validação
$price = $request->price; // Pode receber qualquer coisa
```

**Arquivos a revisar:**
- `app/Http/Controllers/Api/AppointmentController.php`
- `app/Http/Controllers/Api/BarberController.php`
- `app/Http/Controllers/Api/ServiceController.php`

---

## 2. **Mass Assignment Protection**

Garanta que os modelos únicamente declarem fillable os campos corretos:

```php
// ✅ BOM - Apenas campos permitidos
protected $fillable = ['name', 'email', 'phone'];

// ❌ RUIM - Permite modificar qualquer coisa
protected $guarded = ['id']; // Muito permissivo
```

**Verificar todos os models em `app/Models/`**

---

## 3. **Authorization (Autorização) vs Authentication**

Diferença crítica:
- **Authentication**: "Você é realmente Pedro?" (Verificar credenciais)
- **Authorization**: "Pedro tem permissão para editar essa nomeação?" (Verificar propriedade/permissões)

```php
// ❌ RUIM - Sem verificação de propriedade
public function destroy($id)
{
    Appointment::find($id)->delete(); // Qualquer um pode deletar!
}

// ✅ BOM - Com verificação
public function destroy($id)
{
    $appointment = Appointment::find($id);
    
    // Garante que apenas o dono pode deletar
    if ($appointment->user_id !== auth()->id()) {
        abort(403, 'Unauthorized');
    }
    
    $appointment->delete();
}
```

**Adicionar verificações em todos os endpoints protegidos!**

---

## 4. **SQL Injection Prevention**

Usar sempre Eloquent/Query Builder, NUNCA raw SQL:

```php
// ❌ PERIGOSO - SQL Injection
$appointments = Appointment::whereRaw("user_id = $userId")->get();

// ✅ SEGURO - Parameterizado
$appointments = Appointment::where('user_id', $userId)->get();
```

---

## 5. **CSRF Protection para Web Routes**

A API usa tokens Bearer, mas web routes precisam de CSRF:

```php
// Em routes/web.php
Route::middleware('web')->group(function () {
    Route::post('/form', function () {
        // Protegido automaticamente por CSRF
    });
});
```

---

## 6. **Dados Sensíveis em Logs**

Nunca logar senhas, tokens ou CPF:

```php
// ❌ RUIM
Log::info('User data', $user->toArray()); // Expõe password

// ✅ BOM
Log::info('User login', ['user_id' => $user->id, 'email' => $user->email]);
```

Configurar em `config/logging.php`:
```php
'except' => [
    'password',
    'password_confirmation',
    'cpf',
    'api_key',
],
```

---

## 7. **Timezone & Date Handling**

Sempre usar UTC no banco, converter na apresentação:

```php
// .env
APP_TIMEZONE=America/Sao_Paulo

// Modelo
protected $casts = [
    'scheduled_at' => 'datetime', // Laravel converte automaticamente
];
```

---

## 8. **Encryption Sensitive Data**

Campos como CPF, tokens devem ser criptografados:

```php
// Model
protected $encrypted = ['cpf', 'phone'];

// Migration
Schema::create('users', function (Blueprint $table) {
    $table->string('cpf')->nullable()->encrypted();
});
```

---

## 9. **API Throttling por User**

Além de throttling global, limite por usuário:

```php
// routes/api.php
Route::middleware('auth:sanctum', 'throttle:60,1')->group(function () {
    // Max 60 requests por minuto, por usuário
    Route::get('/appointments', [AppointmentController::class, 'index']);
});
```

---

##10. **Audit Logs**

Registrar ações críticas:

```php
// Quando um pagamento é processado
Log::channel('audit')->info('Payment processed', [
    'payment_id' => $payment->id,
    'user_id' => $user->id,
    'amount' => $amount,
    'timestamp' => now(),
]);
```

---

## 11. **Dependencies Security**

Manter dependências atualizadas:

```bash
# Checar vulnerabilidades
composer audit

# Atualizar se seguro
composer update

# Fazer regularly (ex: semanalmente)
```

---

## 12. **Public Routes - Rate Limiting**

Rotas públicas devem ter throttle agressivo:

```php
Route::middleware('throttle:3,1')->group(function () {
    // Apenas 3 requests por minuto para descoberta
    Route::get('/{slug}', [BarbershopController::class, 'show']);
});
```

---

## 13. **Secrets Rotation**

Alterar regularmente:
- `APP_KEY`
- `MERCADO_PAGO_WEBHOOK_SECRET`
- Senhas de banco de dados
- **Frequência**: A cada 6-12 meses, ou se comprometido

---

## 14. **Error Messages**

Nunca expor detalhes internos:

```php
// ❌ RUIM
return response()->json([
    'error' => 'SQLSTATE[42S02]: Table not found',
]);

// ✅ BOM
return response()->json([
    'error' => 'Ocorreu um erro ao processar sua solicitação.',
]);
```

Configure em `config/app.php`:
```php
'debug' => false, // Ocultar erros
```

---

## 15. **Dependency Injection**

Use sempre DI, evite singletons globais:

```php
// ❌ RUIM
$service = new PaymentService();

// ✅ BOM - Laravel injeta
public function __construct(PaymentService $service)
{
    $this->service = $service;
}
```

---

## 📚 Links Úteis

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security](https://laravel.com/docs/11.x/security)
- [Sanctum Docs](https://laravel.com/docs/11.x/sanctum)
- [Password Validation](https://laravel.com/docs/11.x/validation#-password)

---

**Atualizado em**: 2026-03-11
