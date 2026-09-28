# Barber Easy — Aplicativo Mobile

Aplicativo mobile do ecossistema **Barber Easy**, desenvolvido com **React Native + Expo** e integrado a uma API em **Laravel**.

O projeto foi criado como portfólio técnico para demonstrar integração entre aplicativo mobile e backend, autenticação, consumo de API REST, persistência de sessão, navegação, agendamentos, planos e experiência multi-tenant para barbearias.

> Este projeto é uma **demonstração de portfólio**. Alguns recursos sensíveis, principalmente pagamentos reais, ficam desativados na versão pública.

---

## Download do APK

A versão Android de demonstração é publicada através do **GitHub Releases**:

https://github.com/Diogordo08/barber-mobile/releases

Abra a release mais recente e baixe o arquivo `.apk`.

> No Android, pode ser necessário permitir a instalação de aplicativos provenientes do navegador ou gerenciador de arquivos utilizado para abrir o APK.

---

## Backend utilizado

A versão atual do aplicativo está integrada ao backend:

```text
https://barbearia-api-xxvv.onrender.com
```

API:

```text
https://barbearia-api-xxvv.onrender.com/api
```

Repositório do backend:

https://github.com/buja23/barbearia-api

### Primeiro acesso

O backend público utiliza uma infraestrutura econômica destinada à demonstração do projeto. Após um período sem acessos, o primeiro carregamento pode levar alguns segundos enquanto o ambiente da aplicação é inicializado novamente.

Depois da inicialização, as requisições seguintes normalmente respondem com menor latência.

---

# Tecnologias

- React Native
- Expo 54
- Expo Router
- TypeScript
- React 19
- Axios
- AsyncStorage
- NativeWind
- Expo Camera
- React Native WebView
- Lucide React Native

---

# Principais funcionalidades

## Seleção de barbearia

Ao abrir o aplicativo, o usuário seleciona uma barbearia através de:

- código/slug digitado manualmente;
- QR Code.

Para a demonstração pública, o tenant utilizado é:

```text
barbearia-demo
```

O aplicativo consulta:

```http
GET /api/{slug}
```

e mantém a barbearia selecionada no armazenamento local.

---

## Autenticação

O app utiliza autenticação via **Laravel Sanctum**.

Fluxos disponíveis:

```http
POST /api/register
POST /api/login
POST /api/logout
GET  /api/user
PUT  /api/user
```

Após o login, o backend retorna um `access_token`.

O aplicativo:

1. armazena o token com AsyncStorage;
2. configura o header `Authorization`;
3. recupera a sessão ao reabrir o app;
4. limpa a sessão automaticamente quando recebe `401`;
5. tenta revogar o token no logout sem impedir a saída local caso a rede esteja indisponível.

Exemplo:

```http
Authorization: Bearer TOKEN
Accept: application/json
```

---

# Agendamentos

O fluxo principal do aplicativo é:

```text
Selecionar serviço
        ↓
Selecionar profissional
        ↓
Selecionar data
        ↓
Consultar horários disponíveis
        ↓
Selecionar horário
        ↓
Confirmar agendamento
        ↓
Tela de confirmação
        ↓
Agenda / Histórico
```

## Serviços

```http
GET /api/{slug}/services
```

## Barbeiros

```http
GET /api/{slug}/barbers
```

## Horários disponíveis

```http
GET /api/{slug}/slots
```

Parâmetros:

```text
date
barber_id
service_id
```

## Criar agendamento

```http
POST /api/appointments
```

Payload principal:

```json
{
  "barber_id": 1,
  "service_id": 1,
  "scheduled_at": "2026-09-28 15:30:00"
}
```

O backend revalida barbearia, barbeiro, serviço, disponibilidade e conflitos de horário.

---

# Meus agendamentos

A tela de agenda possui duas visualizações:

- **Próximos**
- **Histórico**

Endpoint:

```http
GET /api/appointments
```

Status suportados:

```text
pending
confirmed
completed
canceled
no_show
```

Cancelamento:

```http
DELETE /api/appointments/{id}
```

---

# Perfil

A área de perfil permite visualizar e editar os dados do usuário.

Endpoint:

```http
PUT /api/user
```

O estado atualizado também é persistido localmente para manter a interface sincronizada após a edição.

---

# Planos e assinaturas

O aplicativo possui telas para listar planos, mostrar assinatura ativa, acompanhar utilização e visualizar validade.

Endpoints relacionados:

```http
GET  /api/{slug}/plans
GET  /api/user/subscription
POST /api/subscribe
POST /api/subscribe/cancel
```

---

# Pagamentos

O código do projeto contém implementação relacionada ao Mercado Pago, incluindo suporte a PIX, cartão, WebView, Mercado Pago Bricks, polling de pagamento e chave pública por barbearia.

Porém, **pagamentos reais estão desativados na versão pública de portfólio**.

Na versão demonstrativa:

- o botão de assinatura não inicia pagamento real;
- o checkout do Mercado Pago não é aberto;
- não há polling de pagamento;
- não é exigida chave pública do Mercado Pago;
- a interface informa que pagamentos estão desativados.

O código foi preservado no repositório para demonstrar a arquitetura desenvolvida.

---

# Conta demonstrativa e ações protegidas

O backend possui proteções específicas para o ambiente demonstrativo.

Dependendo da conta utilizada, ações de escrita podem retornar:

```text
403 Forbidden
```

Nesses casos, o aplicativo apresenta:

> Esta ação não está disponível nesta conta demonstrativa.

Isso pode ocorrer em operações como alteração de perfil, criação ou cancelamento de agendamentos, assinaturas, suporte e outras ações protegidas pelo backend.

---

# Tratamento de erros

A aplicação trata respostas comuns da API sem expor mensagens técnicas do Axios ao usuário.

## 401 — sessão expirada

A sessão local é limpa e o usuário retorna ao fluxo de autenticação.

## 403 — ambiente demonstrativo

```text
Esta ação não está disponível nesta conta demonstrativa.
```

## 404 — recurso não encontrado

```text
Barbearia não encontrada.
```

## 422 — validação

Quando apropriado, o aplicativo exibe a mensagem retornada pelo backend.

Exemplo:

```text
Este horário não está mais disponível. Escolha outro horário.
```

## 429 — limite de requisições

```text
Muitas tentativas em pouco tempo. Aguarde alguns instantes.
```

## Falha de conexão

```text
Não foi possível conectar ao servidor. Tente novamente.
```

---

# QR Code

O aplicativo utiliza `expo-camera` para leitura do QR Code da barbearia.

O QR Code pode conter o slug, por exemplo:

```text
barbearia-demo
```

ou uma URL cujo último segmento corresponda ao slug da barbearia.

---

# Persistência local

O aplicativo utiliza AsyncStorage para armazenar informações necessárias à experiência do usuário.

Principais chaves:

```text
@BarberSaaS:user
@BarberSaaS:token
@BarberSaaS:shop
@BarberSaaS:theme
```

---

# Estrutura principal

```text
app/
├── (tabs)/
│   ├── index.tsx
│   ├── agenda.tsx
│   ├── plans.tsx
│   └── perfil.tsx
├── checkout/
├── _layout.tsx
├── welcome.tsx
├── login.tsx
├── register.tsx
├── new-appointment.tsx
├── appointment-success.tsx
└── my-plans.tsx

src/
├── contexts/
│   ├── AuthContext.tsx
│   └── ThemeContext.tsx
├── services/
│   ├── api.ts
│   └── mocks.ts
└── types/

assets/
```

---

# Executando localmente

## Requisitos

- Node.js
- npm
- Expo
- Android Studio, emulador ou dispositivo com Expo Go para testes móveis

Clone:

```bash
git clone https://github.com/Diogordo08/barber-mobile.git
cd barber-mobile
```

Instale as dependências:

```bash
npm install
```

Inicie o Expo:

```bash
npx expo start
```

ou:

```bash
npm start
```

Outros comandos:

```bash
npm run android
npm run ios
npm run web
```

---

# Verificações

TypeScript:

```bash
npx tsc --noEmit
```

Expo:

```bash
npx expo-doctor
```

Build web:

```bash
npm run web
```

---

# Gerando o APK

O projeto utiliza **EAS Build**.

Instale:

```bash
npm install -g eas-cli
```

Faça login:

```bash
eas login
```

Para gerar uma versão Android instalável:

```bash
eas build -p android --profile preview
```

O perfil `preview` deve utilizar:

```json
{
  "android": {
    "buildType": "apk"
  }
}
```

Após o build, publique o `.apk` em **GitHub Releases**.

Não é recomendado versionar o APK diretamente no Git, pois builds Android podem ultrapassar o limite de tamanho de arquivos aceito em commits comuns pelo GitHub.

---

# Limitações conhecidas

## Pagamentos

Pagamentos reais estão desativados na versão pública.

## Cold start

A API hospedada pode levar alguns segundos no primeiro acesso após um período de inatividade.

## Conta demo

Algumas ações podem ser bloqueadas propositalmente pelo backend.

## Câmera

A leitura de QR Code depende de permissão do dispositivo e deve ser validada em Android/iOS.

## Recursos externos

Funcionalidades que dependem de serviços de terceiros podem exigir configuração adicional fora do ambiente demonstrativo.

---

# Validações realizadas

Durante a preparação da versão de portfólio foram verificados:

- TypeScript;
- imports e variáveis;
- configuração do Expo;
- exportação web;
- integração com a API publicada;
- login;
- consulta do usuário;
- barbearia;
- serviços;
- barbeiros;
- horários;
- agenda;
- assinatura;
- tratamento de `403`;
- logout;
- rotas utilizadas pelo aplicativo.

Antes de uma nova release, recomenda-se testar manualmente câmera, QR Code, persistência da sessão, logout offline, navegação, aparência e criação/cancelamento de agendamento com uma conta não demonstrativa.

---

# Objetivo do projeto

Este aplicativo foi desenvolvido para demonstrar conhecimentos em:

- React Native;
- Expo;
- TypeScript;
- consumo de API REST;
- autenticação Bearer Token;
- persistência de sessão;
- Axios e interceptors;
- Expo Router;
- integração mobile/backend;
- fluxo de agendamentos;
- arquitetura multi-tenant;
- QR Code;
- tratamento de estados e erros;
- build Android;
- integração com serviços externos.

Arquitetura resumida:

```text
React Native / Expo
        ↓
Laravel REST API
        ↓
PostgreSQL
        ↓
Filament Admin
```

---

# Repositórios

## Aplicativo

https://github.com/Diogordo08/barber-mobile

## Backend

https://github.com/buja23/barbearia-api

---

# Autor

**Victor Azambuja**

GitHub:

https://github.com/buja23
