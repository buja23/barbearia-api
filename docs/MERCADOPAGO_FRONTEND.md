# MercadoPago — Integração Frontend (Cartão e PIX)

> Documentação focada para o app frontend. Aqui está **tudo** que você precisa para fazer o pagamento de cartão e PIX funcionar do zero.

---

## Visão Geral do Fluxo

```
Frontend                         Backend (Laravel)              MercadoPago
   │                                    │                            │
   │── GET /api/{slug} ───────────────► │                            │
   │◄─ { mp_public_key: "APP_USR-..." } │                            │
   │                                    │                            │
   │  [Inicializa MP Bricks com         │                            │
   │   mp_public_key da barbearia]      │                            │
   │                                    │                            │
   │  [Usuário preenche cartão]         │                            │
   │  [Bricks gera card_token]          │                            │
   │                                    │                            │
   │── POST /api/subscribe ──────────► │── cria pagamento em ──────► │
   │   { plan_id, payment_method:       │   nome da barbearia         │
   │     "card", card_token,            │◄─ { status: "approved" } ──│
   │     installments }                 │                            │
   │◄─ { payment_status: "approved" } ──│                            │
   │                                    │                            │
   │  [Exibe sucesso ou análise]        │                            │
```

**Ponto crítico:** A `mp_public_key` usada no Bricks é **da barbearia**, não sua. Ela vem na resposta de `GET /api/{slug}`. Isso faz o dinheiro ir direto para a conta MP da barbearia.

---

## Passo 1 — Buscar a Public Key da Barbearia

```http
GET /api/{slug}
```

Não precisa de autenticação. Substitua `{slug}` pelo identificador da barbearia (ex: `minha-barbearia`).

**Resposta:**
```json
{
  "id": 1,
  "name": "Barbearia do João",
  "slug": "minha-barbearia",
  "mp_public_key": "APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  ...
}
```

> ⚠️ Se `mp_public_key` vier `null`, a barbearia ainda não configurou o MercadoPago.  
> Nesse caso: **oculte a opção de cartão e mostre só PIX.**

Guarde o valor de `mp_public_key` — você vai precisar dele para inicializar o Bricks.

---

## Passo 2 — Buscar os Planos

```http
GET /api/{slug}/plans
```

**Resposta:**
```json
[
  {
    "id": 1,
    "name": "Plano Bronze",
    "price": "49.90",
    "cuts_per_month": 2,
    "is_active": true
  }
]
```

Guarde o `id` e o `price` do plano escolhido pelo usuário.

---

## Passo 3 — Carregar o SDK do MercadoPago

Adicione este script **antes** de inicializar o Bricks. Carregue apenas uma vez na página/tela.

```html
<script src="https://sdk.mercadopago.com/js/v2"></script>
```

Se estiver em React Native, Expo ou similar (sem DOM), use o WebView para renderizar o Bricks, ou use o pacote oficial:
```bash
npm install @mercadopago/sdk-react
```

---

## Passo 4 — Inicializar o MP Bricks (Cartão)

Use a `mp_public_key` da barbearia (não crie a sua própria).

```js
// mp_public_key veio do GET /api/{slug}
// plan.price veio do GET /api/{slug}/plans

const mp = new MercadoPago(mp_public_key, { locale: 'pt-BR' });
const bricksBuilder = mp.bricks();

await bricksBuilder.create('cardPayment', 'container-do-bricks', {
  initialization: {
    amount: parseFloat(plan.price), // ex: 49.90  — DEVE ser número, não string
  },
  customization: {
    paymentMethods: {
      minInstallments: 1,
      maxInstallments: 12,
    },
  },
  callbacks: {
    onReady: () => {
      // Bricks carregou — esconda o loading aqui
    },
    onSubmit: async ({ formData }) => {
      // Este callback é chamado quando o usuário clica em "Pagar" dentro do Bricks
      //
      // formData contém:
      //   formData.token        → card_token (string) — enviado ao backend
      //   formData.installments → número de parcelas escolhido pelo usuário
      //   formData.payment_method_id → bandeira (ex: "visa", "master")
      //   formData.issuer_id    → banco emissor

      await enviarPagamentoAoBackend(formData.token, formData.installments);
    },
    onError: (error) => {
      // Erros do próprio Bricks (ex: cartão inválido, campo vazio)
      console.error('Erro no Bricks:', error);
      mostrarErroNaTela('Verifique os dados do cartão e tente novamente.');
    },
  },
});
```

> **O botão "Pagar" é do Bricks.** Você não precisa criar um botão próprio para submeter o formulário de cartão. O Bricks renderiza o formulário completo com botão incluído.

---

## Passo 5 — Enviar ao Backend

Depois que o Bricks chama `onSubmit`, você recebe o `card_token`. Envie ao backend:

```js
async function enviarPagamentoAoBackend(cardToken, installments) {
  const res = await fetch('https://SEU-DOMINIO/api/subscribe', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + userToken,  // token do usuário logado
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      plan_id: plan.id,              // integer — ID do plano escolhido
      payment_method: 'card',        // string — obrigatório para cartão
      card_token: cardToken,         // string — vindo do formData.token do Bricks
      installments: installments,    // integer — vindo do formData.installments
    }),
  });

  const data = await res.json();

  if (!res.ok) {
    tratarErro(res.status, data);
    return;
  }

  tratarSucesso(data);
}
```

---

## Passo 6 — Tratar a Resposta

### Sucesso — Cartão aprovado na hora

```json
HTTP 201
{
  "message": "Pagamento processado! Sua assinatura está ativa.",
  "subscription": {
    "id": 4,
    "status": "active",
    "expires_at": "2026-05-01T00:00:00.000000Z",
    "plan": { "id": 1, "name": "Plano Bronze", "cuts_per_month": 2 }
  },
  "payment_status": "approved"
}
```

→ `payment_status === "approved"` + `subscription.status === "active"` → exibir tela de sucesso.

---

### Sucesso — Cartão em análise (raro)

```json
HTTP 201
{
  "message": "Pagamento processado! Sua assinatura está em análise.",
  "subscription": {
    "id": 4,
    "status": "pending"
  },
  "payment_status": "in_process"
}
```

→ `payment_status === "in_process"` → exibir mensagem: *"Pagamento em análise. Você será notificado em breve."*  
→ Iniciar polling (ver abaixo).

---

### Erro — Cartão recusado

```json
HTTP 500
{
  "message": "Pagamento recusado pelo banco. Verifique os dados do cartão."
}
```

→ Exibir a mensagem para o usuário e permitir tentar novamente.

---

### Erro — Usuário já tem assinatura

```json
HTTP 422
{
  "message": "Você já possui uma assinatura ativa."
}
```

→ Redirecionar para a tela de assinatura ativa.

---

### Tabela resumo de tratamento de erros

| HTTP | `payment_status` | O que exibir |
|---|---|---|
| `201` | `approved` | ✅ "Assinatura ativa! Seja bem-vindo." |
| `201` | `in_process` | ⏳ "Pagamento em análise. Aguarde." |
| `422` | — | ⚠️ Mensagem do campo `message` |
| `500` | — | ❌ "Falha ao processar. Tente novamente ou use PIX." |

```js
function tratarErro(status, data) {
  if (status === 422) {
    mostrarAviso(data.message);
  } else {
    mostrarErro(data.message ?? 'Falha ao processar pagamento. Tente novamente.');
  }
}

function tratarSucesso(data) {
  if (data.payment_status === 'approved') {
    navegarParaTelaDeSucesso();
  } else if (data.payment_status === 'in_process') {
    mostrarAviso('Pagamento em análise. Você será notificado.');
    iniciarPolling();
  }
}
```

---

## Polling — Verificar ativação (PIX e cartão em análise)

Quando a assinatura fica `pending`, use polling para detectar quando o webhook do MercadoPago ativar:

```js
function iniciarPolling(userToken) {
  let tentativas = 0;
  const MAX = 60;           // 60 tentativas × 5s = 5 minutos
  const INTERVALO = 5000;   // 5 segundos

  const timer = setInterval(async () => {
    tentativas++;

    try {
      const res = await fetch('https://SEU-DOMINIO/api/user/subscription', {
        headers: { 'Authorization': 'Bearer ' + userToken }
      });

      if (res.ok) {
        const sub = await res.json();

        if (sub.status === 'active') {
          clearInterval(timer);
          navegarParaTelaDeSucesso();
          return;
        }
      }
    } catch (e) {
      // Silencia erros de rede — continua tentando
    }

    if (tentativas >= MAX) {
      clearInterval(timer);
      mostrarAviso(
        'Pagamento ainda não confirmado. Ele será ativado automaticamente assim que o banco processar.'
      );
    }
  }, INTERVALO);

  return timer; // guarde para cancelar se o usuário sair da tela
}
```

---

## Fluxo PIX (complementar)

Se quiser implementar PIX também:

```js
const res = await fetch('https://SEU-DOMINIO/api/subscribe', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + userToken,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({
    plan_id: plan.id,
    payment_method: 'pix',   // ou omitir — pix é o padrão
  }),
});

const data = await res.json();
// data.pix.copy_paste → código Pix Copia e Cola (exibir para o usuário copiar)
// data.pix.payment_id → ID do pagamento (referência interna)

// Gerar QR Code visual a partir do copy_paste:
// Use qualquer biblioteca qrcode (ex: qrcode, react-native-qrcode-svg)
```

Após exibir o código PIX, inicie o polling igual ao cartão em análise.

---

## Resumo do Contrato da API

### `GET /api/{slug}` — Sem autenticação
```
Retorna: { mp_public_key: string|null, ... }
```

### `POST /api/subscribe` — Requer Bearer Token
```
Headers:
  Authorization: Bearer <token_do_usuario>
  Content-Type: application/json
  Accept: application/json

Body (cartão):
{
  "plan_id": 1,               ← integer, obrigatório
  "payment_method": "card",   ← string, obrigatório para cartão
  "card_token": "...",        ← string, obrigatório para cartão (vem do Bricks)
  "installments": 1           ← integer, opcional (padrão: 1, máx: 12)
}

Body (PIX):
{
  "plan_id": 1,
  "payment_method": "pix"    ← ou omitir, pix é o padrão
}
```

### `GET /api/user/subscription` — Requer Bearer Token
```
Retorna: { status: "active"|"pending"|"canceled"|"expired", ... }
Erro 404: { "message": "Nenhuma assinatura ativa." }
```

---

## O que NÃO fazer

| ❌ Errado | ✅ Certo |
|---|---|
| Usar sua própria Public Key do MP | Usar a `mp_public_key` que vem do `GET /api/{slug}` |
| Enviar dados do cartão direto ao backend | Usar o Bricks para gerar o `card_token` e enviar só ele |
| Bloquear a UI se `mp_public_key` for null | Esconder opção de cartão e oferecer PIX |
| Mostrar erro genérico para qualquer falha | Ler o campo `message` da resposta e exibir |
| Não implementar polling | Implementar polling após `status: "pending"` |
| Inicializar Bricks com `amount: 0` ou string | Passar `parseFloat(plan.price)` — número, não string |
