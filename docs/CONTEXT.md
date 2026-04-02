# Contexto do Projeto — Barbearia SaaS API

## O que é o projeto

SaaS multi-tenant para **gestão de barbearias**. Cada barbearia é um tenant identificado por um `slug` único (ex: `minha-barbearia`). O sistema tem dois públicos:

- **Donos de barbearia / administradores** → usam o painel web (Filament 3) para gerenciar barbeiros, serviços, planos de assinatura e agendamentos.
- **Clientes finais** → usam um app mobile/web que consome esta API REST para agendar horários e assinar planos mensais de cortes.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | **Laravel 11** |
| Autenticação | **Laravel Sanctum** (Bearer Token) |
| Painel admin | **Filament 3** (sem relevância para o app) |
| Pagamentos | **MercadoPago SDK v3** (PIX e cartão) |
| Banco de dados | MySQL |

---

## Base URL

```
https://barbearia-api.on-forge.com/api
```

---

## Autenticação

Todas as rotas marcadas com 🔒 exigem header:

```
Authorization: Bearer <token>
```

O token é obtido no login ou registro.

---

## Resumo dos Módulos e Endpoints

### 1. Autenticação

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `POST` | `/api/register` | ❌ | Cria conta de cliente |
| `POST` | `/api/login` | ❌ | Faz login, retorna token |
| `POST` | `/api/logout` | 🔒 | Revoga o token atual |
| `GET` | `/api/user` | 🔒 | Dados do usuário autenticado |
| `PUT` | `/api/user` | 🔒 | Atualiza nome e e-mail |

### 2. Senha

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `POST` | `/api/password/forgot` | ❌ | Envia e-mail com link de redefinição |
| `POST` | `/api/password/reset` | ❌ | Redefine a senha com token do e-mail |

### 3. Barbearia (dados públicos)

> Prefixo: `/{slug}` — todas são públicas, sem autenticação.

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/{slug}` | Info pública da barbearia (nome, logo, endereço, descrição, horários, tema) |
| `GET` | `/api/{slug}/services` | Lista serviços ativos (nome, preço, duração) |
| `GET` | `/api/{slug}/barbers` | Lista barbeiros ativos |
| `GET` | `/api/{slug}/plans` | Lista planos de assinatura ativos |
| `GET` | `/api/{slug}/slots` | Horários disponíveis (query: `date`, `barber_id`, `service_id`) |

### 4. Agendamentos

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/appointments` | 🔒 | Lista agendamentos do usuário |
| `POST` | `/api/appointments` | 🔒 | Cria agendamento |
| `DELETE` | `/api/appointments/{id}` | 🔒 | Cancela agendamento |

### 5. Assinaturas

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/user/subscription` | 🔒 | Assinatura ativa do usuário |
| `POST` | `/api/subscribe` | 🔒 | Cria nova assinatura (PIX ou cartão) |
| `POST` | `/api/subscribe/cancel` | 🔒 | Cancela assinatura ativa |

**Body de `POST /api/subscribe`**

| Campo | Tipo | Obrigatório | Observação |
|---|---|---|---|
| `plan_id` | integer | ✅ | |
| `payment_method` | string | ❌ | `"pix"` (padrão) ou `"card"` |
| `card_token` | string | ✅ se `card` | gerado pelo MP Bricks |
| `installments` | integer | ❌ | parcelas, padrão 1 |

---

## Fluxos Principais

### Fluxo de Agendamento

```
1. GET /api/{slug}/services          → app mostra catálogo de serviços
2. GET /api/{slug}/barbers           → app mostra lista de barbeiros
3. GET /api/{slug}/slots?date=...    → app mostra horários disponíveis
4. POST /api/appointments            → app cria o agendamento
   └─ se usuário tem assinatura com saldo → total_price = 0.00
   └─ se não tem saldo / sem assinatura  → total_price = preço do serviço
```

### Fluxo de Assinatura (PIX ou Cartão)

```
1. GET /api/{slug}                → pegar mp_public_key da barbearia
2. GET /api/{slug}/plans          → app mostra planos disponíveis
3. Usuário escolhe plano + forma de pagamento

--- PIX ---
4. POST /api/subscribe            → body: { plan_id, payment_method: "pix" }
   └─ retorna pix.copy_paste (string Pix Copia e Cola)
5. App exibe o código para o usuário pagar
6. MercadoPago chama /api/webhooks/mercadopago
7. API ativa a assinatura automaticamente (status: active)

--- CARTÃO ---
4. App inicializa MP Bricks com mp_public_key (captura dados do cartão)
5. Bricks gera card_token no callback onSubmit
6. POST /api/subscribe            → body: { plan_id, payment_method: "card", card_token, installments }
   └─ approved   → assinatura já ativa
   └─ in_process → assinatura pending, aguardar webhook
```

> O dinheiro vai **direto para a conta MercadoPago da barbearia**.

---

## Campos Importantes por Modelo

### User (retornado no login/registro)

```json
{
  "id": 1,
  "name": "João Silva",
  "email": "joao@email.com",
  "role": "client"
}
```

> CPF e telefone são **criptografados** em repouso e não aparecem nas respostas padrão.

### Appointment (agendamento)

| Campo | Tipo | Descrição |
|---|---|---|
| `status` | enum | `confirmed`, `pending`, `completed`, `canceled` |
| `total_price` | decimal | `0.00` = coberto pela assinatura; valor > 0 = avulso |
| `payment_method` | string\|null | `pix` ou `null` |
| `notes` | string\|null | Ex: `"Pago pelo plano Bronze"` |

> `status` é sempre `"confirmed"` imediatamente após a criação. Nunca fica pendente.

### Subscription (assinatura)

| Campo | Tipo | Descrição |
|---|---|---|
| `status` | enum | `active`, `pending`, `canceled`, `expired` |
| `uses_this_month` | int | Cortes usados no mês corrente |
| `remaining_cuts` | int | Cortes restantes (valor armazenado, não calculado) |
| `expires_at` | datetime | Data de expiração |

---

## Armadilhas / Comportamentos Não Óbvios

1. **`POST /api/register` retorna `200`**, não `201` — não confunda com erro.

2. **Erro de login** não vem em `message`, vem em `errors.email`:
   ```json
   { "errors": { "email": ["As credenciais fornecidas estão incorretas."] } }
   ```

3. **Campo `avatar` dos barbeiros** retorna caminho relativo, não URL completa:
   - API retorna: `"avatars/carlos.jpg"`
   - URL real: `https://<dominio>/storage/avatars/carlos.jpg`

4. **`GET /api/{slug}/plans`** retorna **todos** os campos do model (incluindo `barbershop_id`, `created_at`, `updated_at`) — não apenas os campos exibidos no doc.

5. **`GET /api/{slug}/slots`** retorna **array de strings** `"HH:mm"`, não objetos:
   ```json
   ["09:00", "09:45", "10:30"]
   ```

6. **Assinatura PIX** é criada com `status: "pending"` — só ativa após o webhook do MercadoPago. — Assinatura paga por **cartão aprovado** já fica `status: "active"` imediatamente.

7. **`mp_public_key` nulo** em `GET /api/{slug}` significa que a barbearia ainda não configurou o MercadoPago no painel. Desabilite a opção de cartão no app quando isso ocorrer.

8. **Cartão**: o app inicializa o MP Bricks com `mp_public_key` da barbearia. O `card_token` é gerado no callback `onSubmit` do Bricks e enviado ao backend.

9. **O dinheiro vai direto para a conta MP da barbearia** — o SaaS não intermedia.

10. **Cancelamento de agendamento via assinatura** (`total_price = 0`) devolve 1 corte ao saldo (`uses_this_month--`) automaticamente.

11. **`description`** em `GET /api/{slug}` pode vir `null` se o dono da barbearia não preencheu o campo no Filament. O app deve tratar esse caso (texto padrão ou ocultar a seção "Sobre nós").

12. **`opening_hours`** em `GET /api/{slug}` retorna **apenas os dias cadastrados** pelo dono no Filament. Se o array vier vazio `[]`, não exiba a seção de horários ou mostre "Consulte pelo WhatsApp".
    - `day_of_week`: `0`=Dom, `1`=Seg, `2`=Ter, `3`=Qua, `4`=Qui, `5`=Sex, `6`=Sáb
    - Quando `is_closed: true`, `opening_time` e `closing_time` vêm `null`
    - Para exibir "Aberto hoje": filtre pelo dia da semana atual (`new Date().getDay()`), verifique `is_closed === false`

---

## Rate Limits

| Rota | Limite |
|---|---|
| Login / Registro | 5 req/min |
| Agendamentos | 20 req/min |
| Assinaturas | 10 req/min |
| Rotas públicas da barbearia | 30 req/min |
| Demais rotas protegidas | 60 req/min |

---

## Documentação Completa

Ver `docs/API.md` para todos os exemplos de request/response com os campos exatos.
