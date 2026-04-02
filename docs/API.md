# Barbearia API — Documentação

> Stack: **Laravel 11** · **Sanctum** · **Filament 3** · **MercadoPago SDK v3**

## Visão Geral

API REST para um SaaS de gestão de barbearias. O sistema permite que **donos de barbearia** gerenciem serviços, barbeiros, planos e agendamentos via painel administrativo (Filament), enquanto **clientes** utilizam a API móvel/web para agendar horários e assinar planos mensais.

---

## Base URL

```
https://<seu-dominio>/api
```

---

## Autenticação

A API usa **Laravel Sanctum** (Bearer Token). Após login ou registro, inclua o token em todas as requisições protegidas:

```
Authorization: Bearer <token>
```

---

## Rate Limiting

| Grupo de Rotas | Limite |
|---|---|
| Login / Registro / Senha | 5 req/min |
| Webhook MercadoPago | 60 req/min |
| Rotas protegidas (geral) | 60 req/min |
| Assinaturas | 10 req/min |
| Agendamentos | 20 req/min |
| Rotas públicas da barbearia | 30 req/min |

---

## Módulos

1. [Autenticação](#1-autenticação)
2. [Senha](#2-senha)
3. [Barbearia Pública](#3-barbearia-pública)
4. [Serviços](#4-serviços)
5. [Barbeiros](#5-barbeiros)
6. [Planos](#6-planos)
7. [Agendamentos](#7-agendamentos)
8. [Assinaturas](#8-assinaturas)
9. [Webhook](#9-webhook)

---

## 1. Autenticação

### `POST /api/register`

Registra um novo usuário. O `role` é sempre forçado para `client`.

**Body (JSON)**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `name` | string | ✅ | max 255 |
| `email` | string | ✅ | email único |
| `password` | string | ✅ | mín 10 chars, maiúsculas, minúsculas e números |
| `password_confirmation` | string | ✅ | igual a `password` |

**Resposta `200 OK`**

> ⚠️ O controller retorna `200` (não `201`) mesmo no registro.

```json
{
  "access_token": "1|abc...",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "João", "email": "joao@email.com", "role": "client" }
}
```

---

### `POST /api/login`

Autentica um usuário existente.

**Body (JSON)**

| Campo | Tipo | Obrigatório |
|---|---|---|
| `email` | string | ✅ |
| `password` | string | ✅ |

**Resposta `200 OK`**

```json
{
  "access_token": "2|xyz...",
  "token_type": "Bearer",
  "user": { "id": 1, "name": "João", "email": "joao@email.com" }
}
```

**Erro `422`**: Credenciais incorretas — o erro vem no campo `email`, não no campo `message`:

```json
{ "errors": { "email": ["As credenciais fornecidas estão incorretas."] } }
```

---

### `POST /api/logout` 🔒

Revoga o token atual do usuário autenticado.

**Resposta `200 OK`**

```json
{ "message": "Logout realizado com sucesso" }
```

---

### `GET /api/user` 🔒

Retorna os dados do usuário autenticado.

**Resposta `200 OK`** — Objeto `User` completo (sem `password`).

---

### `PUT /api/user` 🔒

Atualiza nome e e-mail do perfil. Se o e-mail for alterado, `email_verified_at` é zerado.

**Body (JSON)**

| Campo | Tipo | Obrigatório |
|---|---|---|
| `name` | string | ✅ |
| `email` | string | ✅ |

**Resposta `200 OK`**

```json
{
  "message": "Perfil atualizado com sucesso!",
  "user": { ... }
}
```

---

## 2. Senha

### `POST /api/password/forgot`

Solicita o envio de e-mail com link para redefinição de senha. A resposta é sempre a mesma independente de o e-mail existir (evita enumeração de usuários).

**Body (JSON)**

| Campo | Tipo | Obrigatório |
|---|---|---|
| `email` | string | ✅ |

**Resposta `200 OK`**

```json
{ "message": "Se o email estiver cadastrado, você receberá um link de redefinição em breve." }
```

---

### `POST /api/password/reset`

Redefine a senha usando o token recebido por e-mail.

**Body (JSON)**

| Campo | Tipo | Obrigatório | Regras |
|---|---|---|---|
| `token` | string | ✅ | token enviado por e-mail |
| `email` | string | ✅ | |
| `password` | string | ✅ | mín 10 chars, maiúsculas, minúsculas e números |
| `password_confirmation` | string | ✅ | |

**Resposta `200 OK`**

```json
{ "message": "Senha redefinida com sucesso." }
```

**Erro `422`**: Token inválido ou expirado.

---

## 3. Barbearia Pública

> Todas as rotas públicas são prefixadas com `/{slug}`, onde `slug` é o identificador único da barbearia (ex: `minha-barbearia`).

### `GET /api/{slug}`

Retorna informações públicas da barbearia. **Dados sensíveis são filtrados** (telefone mascarado, sem IDs internos extras).

**Resposta `200 OK`**

```json
{
  "id": 1,
  "name": "Barbearia Top",
  "slug": "barbearia-top",
  "description": "Especialistas em cortes clássicos e modernos desde 2010.",
  "logo": "https://dominio.com/storage/logos/logo.png",
  "phone": "(11) 99999-9999",
  "address": "Rua das Pedras, 123",
  "whatsapp": "https://wa.me/5511999999999",
  "mp_public_key": "APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "opening_hours": [
    { "day_of_week": 1, "opening_time": "09:00", "closing_time": "20:00", "is_closed": false },
    { "day_of_week": 2, "opening_time": "09:00", "closing_time": "20:00", "is_closed": false },
    { "day_of_week": 3, "opening_time": "09:00", "closing_time": "20:00", "is_closed": false },
    { "day_of_week": 4, "opening_time": "09:00", "closing_time": "20:00", "is_closed": false },
    { "day_of_week": 5, "opening_time": "09:00", "closing_time": "20:00", "is_closed": false },
    { "day_of_week": 6, "opening_time": "09:00", "closing_time": "14:00", "is_closed": false },
    { "day_of_week": 0, "opening_time": null, "closing_time": null, "is_closed": true }
  ],
  "theme": {
    "primary": "#0f172a",
    "secondary": "#fbbf24"
  }
}
```

**Campos:**

| Campo | Tipo | Descrição |
|---|---|---|
| `description` | string\|null | Texto "Sobre a barbearia" para exibir na página pública. `null` se não preenchido |
| `opening_hours` | array | Horários configurados. Array vazio `[]` se nenhum dia foi cadastrado |
| `opening_hours[].day_of_week` | int | `0`=Dom · `1`=Seg · `2`=Ter · `3`=Qua · `4`=Qui · `5`=Sex · `6`=Sáb |
| `opening_hours[].opening_time` | string\|null | Hora de abertura `"HH:MM"`. `null` quando `is_closed: true` |
| `opening_hours[].closing_time` | string\|null | Hora de fechamento `"HH:MM"`. `null` quando `is_closed: true` |
| `opening_hours[].is_closed` | boolean | `true` = fechado nesse dia |
| `mp_public_key` | string\|null | Chave pública MP da barbearia para o Bricks. `null` = MP não configurado |

> ⚠️ `mp_public_key` — se vier `null`, desabilite a opção de cartão no app.

> ⚠️ `opening_hours` — só aparecem os dias **cadastrados** pelo dono no Filament. Se o array vier vazio `[]`, a barbearia não configurou horários — não exiba a seção ou exiba uma mensagem de contato.

> ⚠️ `description` — pode vir `null` se o dono não preencheu. Mostre um texto padrão ou oculte a seção.

**Erros**: `400` slug inválido · `404` barbearia não encontrada.

---

## 4. Serviços

### `GET /api/{slug}/services`

Lista os serviços ativos de uma barbearia, ordenados por nome.

**Resposta `200 OK`**

```json
[
  {
    "id": 1,
    "name": "Corte Degradê",
    "price": "35.00",
    "duration_minutes": 45,
    "description": "Corte moderno com máquina e tesoura"
  }
]
```

---

## 5. Barbeiros

### `GET /api/{slug}/barbers`

Lista os barbeiros **ativos** de uma barbearia. Retorna apenas campos públicos.

**Resposta `200 OK`**

```json
[
  {
    "id": 1,
    "name": "Carlos",
    "avatar": "avatars/carlos.jpg",
    "email": "carlos@barbearia.com"
  }
]
```

> ⚠️ O campo `avatar` retorna o caminho relativo (ex: `avatars/carlos.jpg`), não a URL completa. Construa a URL no app: `https://<dominio>/storage/avatars/carlos.jpg`.

---

## 6. Planos

### `GET /api/{slug}/plans`

Lista os planos de assinatura **ativos** de uma barbearia.

> ⚠️ Retorna **todos os campos** do model `Plan` (sem filtro de colunas no controller). Os campos principais são:

**Resposta `200 OK`**

```json
[
  {
    "id": 1,
    "barbershop_id": 1,
    "name": "Plano Bronze",
    "description": "2 cortes por mês",
    "price": "49.90",
    "cuts_per_month": 2,
    "is_active": true,
    "created_at": "2026-03-01T00:00:00.000000Z",
    "updated_at": "2026-03-01T00:00:00.000000Z"
  }
]
```

---

## 7. Agendamentos

### `GET /api/{slug}/slots`

Retorna os horários disponíveis para agendamento de um barbeiro em uma data específica. Respeita horário de funcionamento, intervalo de almoço do barbeiro e agendamentos já confirmados.

**Query Parameters**

| Parâmetro | Tipo | Obrigatório | Exemplo |
|---|---|---|---|
| `date` | string (Y-m-d) | ✅ | `2026-04-15` |
| `barber_id` | integer | ✅ | `1` |
| `service_id` | integer | ✅ | `2` |

**Resposta `200 OK`**

```json
["09:00", "09:45", "10:30", "14:00", "15:30"]
```

> Array de strings `"HH:mm"`. Intervalos de 15 minutos a partir do horário de abertura até `(fechamento - duração do serviço)`.

---

### `GET /api/appointments` 🔒

Lista todos os agendamentos do usuário autenticado, ordenados do mais recente para o mais antigo. Inclui dados do barbeiro, barbearia e serviço.

**Resposta `200 OK`**

```json
[
  {
    "id": 10,
    "scheduled_at": "2026-04-15T10:00:00.000000Z",
    "end_at": "2026-04-15T10:45:00.000000Z",
    "status": "confirmed",
    "total_price": "0.00",
    "notes": "Pago pelo plano Bronze",
    "barber": {
      "id": 1,
      "name": "Carlos",
      "barbershop": { "id": 1, "name": "Barbearia Top" }
    },
    "service": { "id": 2, "name": "Corte Degradê", "duration_minutes": 45 }
  }
]
```

---

### `POST /api/appointments` 🔒

Cria um novo agendamento. A lógica verifica automaticamente se o usuário possui assinatura ativa com saldo:

- **Com assinatura e saldo disponível** → `total_price = 0.00`, incrementa `uses_this_month`.
- **Com assinatura, mas sem saldo** → preço cheio do serviço, agendamento avulso.
- **Sem assinatura** → preço cheio do serviço.

**Body (JSON)**

| Campo | Tipo | Obrigatório | Observação |
|---|---|---|---|
| `barber_id` | integer | ✅ | deve pertencer à barbearia |
| `service_id` | integer | ✅ | deve pertencer à barbearia |
| `scheduled_at` | datetime (ISO 8601) | ✅ | ex: `2026-04-15T10:00:00` |
| `client_phone` | string | ❌ | se omitido usa o telefone cadastrado do usuário |

**Resposta `201 Created`**

```json
{
  "message": "Agendado via assinatura!",
  "appointment": {
    "id": 11,
    "scheduled_at": "2026-04-15T10:00:00.000000Z",
    "status": "confirmed",
    "total_price": "0.00"
  }
}
```

---

### `DELETE /api/appointments/{id}` 🔒

Cancela um agendamento do usuário. Só é permitido cancelar agendamentos do próprio usuário. Se o agendamento era via assinatura (`total_price = 0` + nota de plano), decrementa `uses_this_month`.

**Resposta `200 OK`**

```json
{ "message": "Agendamento cancelado." }
```

**Erros**: `403` não autorizado · `404` não encontrado · `422` já cancelado.

---

## 8. Assinaturas

### `GET /api/user/subscription` 🔒

Retorna a assinatura ativa do usuário com detalhes do plano.

**Resposta `200 OK`**

```json
{
  "id": 3,
  "user_id": 1,
  "plan_id": 1,
  "barbershop_id": 1,
  "status": "active",
  "starts_at": "2026-03-01T00:00:00.000000Z",
  "expires_at": "2026-04-01T00:00:00.000000Z",
  "uses_this_month": 1,
  "remaining_cuts": 2,
  "plan": {
    "id": 1,
    "name": "Plano Bronze",
    "cuts_per_month": 2,
    "price": "49.90"
  }
}
```

**Erro `404`**: `{ "message": "Nenhuma assinatura ativa." }`

---

### `POST /api/subscribe` 🔒

Cria uma nova assinatura. Impede múltiplas assinaturas ativas simultaneamente.

O pagamento vai **diretamente para a conta MercadoPago da barbearia** (não passa pelo SaaS).

**Regras por cenário:**
- **Plano gratuito (`price = 0`)** → ativado imediatamente, sem pagamento.
- **Plano pago + `payment_method = "pix"`** → gera PIX na conta MP da barbearia. Assinatura fica `pending` até o webhook confirmar.
- **Plano pago + `payment_method = "card"`** → cobra no cartão via token MP Bricks. Se aprovado imediatamente, já ativa (`active`). Se em análise, fica `pending` até webhook.

**Body (JSON)**

| Campo | Tipo | Obrigatório | Observação |
|---|---|---|---|
| `plan_id` | integer | ✅ | deve ser plano ativo da barbearia |
| `payment_method` | string | ❌ | `"pix"` (padrão) ou `"card"` |
| `card_token` | string | ✅ se `card` | token gerado pelo MP Bricks no frontend |
| `installments` | integer | ❌ | parcelas, padrão `1` (máx. 12) |

**Resposta `201` — Plano gratuito**

```json
{
  "message": "Assinatura ativada com sucesso!",
  "subscription": { "id": 3, "status": "active", "plan": { ... } }
}
```

**Resposta `201` — PIX**

```json
{
  "message": "Assinatura criada. Efetue o pagamento via PIX para ativar.",
  "subscription": { "id": 4, "status": "pending", "plan": { ... } },
  "pix": {
    "copy_paste": "00020126330014br.gov.bcb.pix...",
    "payment_id": "12345678"
  }
}
```

> `copy_paste` é o código Pix Copia e Cola. Para exibir QR Code visual, gere a imagem no app a partir desta string (ex: biblioteca `qrcode`).

**Resposta `201` — Cartão aprovado imediatamente**

```json
{
  "message": "Pagamento processado! Sua assinatura está ativa.",
  "subscription": { "id": 4, "status": "active", "plan": { ... } },
  "payment_status": "approved"
}
```

**Resposta `201` — Cartão em análise**

```json
{
  "message": "Pagamento processado! Sua assinatura está em análise.",
  "subscription": { "id": 4, "status": "pending", "plan": { ... } },
  "payment_status": "in_process"
}
```

**Erros**

| Código | Motivo |
|---|---|
| `422` | Usuário já possui assinatura ativa |
| `422` | `card_token` ausente quando `payment_method = "card"` |
| `500` | Barbearia sem MP configurado ou recusa do banco — trate com mensagem genérica |

---

### `POST /api/subscribe/cancel` 🔒

Cancela a assinatura ativa do usuário (altera status para `canceled`).

**Resposta `200 OK`**

```json
{ "message": "Assinatura cancelada com sucesso." }
```

**Erro `404`**: `{ "message": "Nenhuma assinatura ativa para cancelar." }`

---

## 9. Webhook

### `POST /api/webhooks/mercadopago`

Endpoint para receber notificações de pagamento do MercadoPago. **Validação obrigatória de assinatura HMAC-SHA256** via header `x-signature`. Requisições sem assinatura válida são rejeitadas com `403`.

Trata dois cenários:

| `type` / `topic` | Cenário | Ação |
|---|---|---|
| `subscription_preapproval` | Renovação de assinatura (cartão) | Reseta `uses_this_month`, estende `expires_at` em +30 dias, status `active` |
| `payment` (subscription) | PIX de assinatura de plano pago | Busca `Subscription` por `external_id`, consulta MP com token da barbearia, seta `status: active` + `expires_at` +30 dias |
| `payment` (appointment) | PIX de agendamento avulso | Atualiza `payment_status` do agendamento e confirma se `approved` |

> **Importante:** O backend identifica automaticamente se o `payment` é de assinatura ou agendamento pelo `external_id` salvo no banco. **Nenhuma lógica extra é necessária no frontend.** A assinatura ativa sozinha via webhook após o cliente efetuar o PIX.

**Headers necessários (enviados pelo MercadoPago)**

```
x-signature: ts=<timestamp>,v1=<hmac-sha256>
x-request-id: <uuid>
```

---

## Modelos de Dados

### User

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | int | ID único |
| `name` | string | Nome completo |
| `email` | string | E-mail único |
| `role` | enum | `admin`, `barber`, `client` |
| `cpf` | string (encrypted) | CPF criptografado |
| `phone` | string (encrypted) | Telefone criptografado |
| `barbershop_id` | int\|null | Barbearia vinculada (cliente) |

### Barbershop (Barbearia) — campos públicos

| Campo | Tipo | Descrição |
|---|---|---|
| `id` | int | ID único |
| `name` | string | Nome da barbearia |
| `slug` | string | Identificador URL |
| `description` | string\|null | Texto "Sobre a barbearia". `null` se não preenchido |
| `logo` | string\|null | URL absoluta do logo |
| `phone` | string | Telefone mascarado |
| `address` | string | Endereço |
| `whatsapp` | string | Link `https://wa.me/55...` |
| `mp_public_key` | string\|null | Chave pública MP da barbearia para Bricks |
| `opening_hours` | array | Dias e horários de funcionamento cadastrados |

> Campos `mp_access_token`, `pix_key` e outros dados sensíveis **nunca aparecem** na API.

### Appointment (Agendamento)

| Campo | Tipo | Descrição |
|---|---|---|
| `status` | enum | `confirmed`, `pending`, `completed`, `canceled` |
| `total_price` | decimal | Valor cobrado (0 = via assinatura) |
| `payment_method` | string | `pix`, null |
| `notes` | string\|null | Ex: "Pago pelo plano Bronze" |

### Subscription (Assinatura)

| Campo | Tipo | Descrição |
|---|---|---|
| `status` | enum | `active`, `pending`, `canceled`, `expired` |
| `uses_this_month` | int | Cortes usados no mês corrente |
| `remaining_cuts` | int | Cortes restantes (valor armazenado no banco) |
| `expires_at` | datetime | Data de expiração da assinatura |

---

## Fluxo de Pagamento com Cartão (MP Bricks)

O app usa o **MP Bricks** (SDK MercadoPago) para capturar os dados do cartão com segurança.  
A chave pública usada é a **da barbearia** (`mp_public_key`), então o pagamento vai direto para a conta dela.

### Passo a passo completo — Cartão

**1. Buscar a barbearia**
```
GET /api/{slug}
→ guardar mp_public_key da resposta
```

> Se `mp_public_key` vier `null`, a barbearia ainda não configurou o MP. **Oculte a opção de cartão**, ofereça só PIX.

**2. Buscar os planos**
```
GET /api/{slug}/plans
→ selecionar o plano, guardar plan.price e plan.id
```

**3. Carregar o SDK do MercadoPago no HTML**
```html
<script src="https://sdk.mercadopago.com/js/v2"></script>
```

**4. Inicializar o MP Bricks**
```js
const mp = new MercadoPago(mp_public_key, { locale: 'pt-BR' });
const bricksBuilder = mp.bricks();

await bricksBuilder.create('cardPayment', 'brick-container', {
  initialization: {
    amount: parseFloat(plan.price), // ex: 49.90
  },
  customization: {
    paymentMethods: {
      minInstallments: 1,
      maxInstallments: 12,
    },
  },
  callbacks: {
    onReady: () => {
      // Bricks carregado — esconder loading
    },
    onSubmit: async ({ formData }) => {
      // formData.token        → card_token
      // formData.installments → número de parcelas
      // formData.payment_method_id → bandeira (ex: "visa")
      await assinarComCartao(formData.token, formData.installments);
    },
    onError: (error) => {
      console.error('Bricks error:', error);
    },
  },
});
```

**5. Enviar ao backend**
```js
async function assinarComCartao(cardToken, installments) {
  const res = await fetch('/api/subscribe', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + userToken,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      plan_id: plan.id,
      payment_method: 'card',
      card_token: cardToken,
      installments: installments,
    }),
  });

  const data = await res.json();

  if (!res.ok) {
    // 422 → já tem assinatura / token inválido
    // 500 → recusa do banco ou MP sem credenciais configuradas
    mostrarErro(data.message ?? 'Erro ao processar pagamento.');
    return;
  }

  if (data.payment_status === 'approved') {
    mostrarSucesso('Assinatura ativa! Bem-vindo.');
  } else {
    mostrarAviso('Pagamento em análise. Você será notificado em breve.');
  }
}
```

**6. Tratar respostas**

| Código HTTP | `payment_status` | O que mostrar |
|---|---|---|
| `201` | `approved` | "Assinatura ativa! Bem-vindo." |
| `201` | `in_process` | "Pagamento em análise. Você será notificado." |
| `422` | — | Mensagem do campo `message` (ex: "Você já possui assinatura ativa") |
| `500` | — | "Falha ao processar pagamento. Tente novamente ou use PIX." |

---

## Fluxo de Pagamento com PIX

### Passo a passo completo — PIX

**1. Enviar ao backend**
```js
const res = await fetch('/api/subscribe', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + userToken,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    plan_id: plan.id,
    payment_method: 'pix', // ou omitir — pix é o padrão
  }),
});

const data = await res.json();
```

**2. Exibir o código PIX**
```js
// data.pix.copy_paste → string longa para "Copiar código"
// Gere o QR Code visualmente no app a partir dessa string

const codigoPix = data.pix.copy_paste;
const paymentId = data.pix.payment_id; // guarde para consultas futuras
```

**Gerar QR Code no app (exemplo com biblioteca `qrcode`):**
```js
import QRCode from 'qrcode';

const qrDataUrl = await QRCode.toDataURL(codigoPix);
// <img src={qrDataUrl} />
```

**3. Aguardar ativação**

A assinatura fica com `status: "pending"` até o webhook do MercadoPago confirmar o pagamento. O app deve:

```js
// Polling simples — consulta a cada 5 segundos por até 5 minutos
let tentativas = 0;
const intervalo = setInterval(async () => {
  tentativas++;
  const sub = await fetch('/api/user/subscription', {
    headers: { 'Authorization': 'Bearer ' + userToken }
  }).then(r => r.json());

  if (sub.status === 'active') {
    clearInterval(intervalo);
    mostrarSucesso('PIX confirmado! Assinatura ativa.');
  }

  if (tentativas >= 60) {
    clearInterval(intervalo);
    mostrarAviso('Pagamento ainda não confirmado. Ele será ativado automaticamente assim que o banco processar.');
  }
}, 5000);
```

---

## Segurança

- Senhas com requisitos mínimos: 10 chars, maiúsculas, minúsculas e números.
- CPF e telefone do usuário são **criptografados** em repouso (`encrypted` cast).
- Telefones são **mascarados** nas respostas públicas.
- Retorno genérico na recuperação de senha (anti-enumeração de usuários).
- Webhooks validados com HMAC-SHA256 (assinatura oficial do MercadoPago).
- Autorização explícita por `user_id` antes de cancelar agendamentos.
- Rate limiting aplicado em todos os grupos de rotas.
- Slugs validados com regex antes de qualquer consulta ao banco.

---

## Guia de Teste — Integração Pagamentos

### Pré-requisitos

| Item | Status |
|---|---|
| Barbearia com `mp_public_key` preenchida | Verificar no Filament |
| Barbearia com `mp_access_token` preenchido | Verificar no Filament |
| Webhook configurado no painel MP da barbearia | URL: `https://<dominio>/api/webhooks/mercadopago`, Evento: `payment` |
| Credenciais de **Produção** (não Teste) | Token começa com `APP_USR-` |

### Roteiro de Teste PIX

```
1. Criar conta de cliente via POST /api/register
   → guardar token

2. Chamar GET /api/{slug}/plans
   → escolher o plano de menor valor (R$ 1,00 nos testes)

3. Chamar POST /api/subscribe
   Body: { "plan_id": <id>, "payment_method": "pix" }
   → receber pix.copy_paste e pix.payment_id

4. Pagar o PIX (copiar código no app de banco, aguardar ~5-10s)

5. Iniciar polling GET /api/user/subscription a cada 5s
   → aguardar status mudar de "pending" para "active"
   → máximo 5 minutos; se não mudar, verificar logs do webhook
```

### Roteiro de Teste Cartão (MP Bricks)

```
1. Mesmos passos 1-2 do PIX

2. Carregar SDK: <script src="https://sdk.mercadopago.com/js/v2"></script>

3. Inicializar Bricks com mp_public_key da barbearia (GET /api/{slug})

4. Preencher dados do cartão no Bricks
   → onSubmit retorna formData.token (card_token) e formData.installments

5. Chamar POST /api/subscribe
   Body: { "plan_id": <id>, "payment_method": "card", "card_token": "<token>", "installments": 1 }
   → se payment_status === "approved": assinatura já ativa
   → se payment_status === "in_process": aguardar webhook (polling)
```

### Como verificar o webhook nos logs

No servidor Forge:
```bash
tail -f storage/logs/laravel.log | grep -i "webhook\|subscription\|pix"
```

Ou via Tinker para ver o estado da assinatura:
```bash
php artisan tinker
>>> App\Models\Subscription::latest()->first()
```

### Erros comuns e solução

| Erro | Causa | Solução |
|---|---|---|
| `500` no `POST /api/subscribe` | `mp_access_token` não salvo | Preencher token no Filament e salvar novamente |
| Assinatura fica `pending` para sempre | Webhook não configurado no MP | Adicionar URL do webhook no painel MP da barbearia |
| `403` no webhook (logs) | `MERCADOPAGO_WEBHOOK_SECRET` errado no `.env` | Copiar o segredo do painel MP → aba Webhook → "Chave secreta" |
| `mp_public_key` retorna `null` | Campo não preenchido no Filament | Preencher Public Key da barbearia |
