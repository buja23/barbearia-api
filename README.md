# Desempenho da aplicação publicada

A versão pública deste projeto utiliza uma infraestrutura econômica/gratuita, adequada para demonstrações de portfólio.

Quando a aplicação fica algum tempo sem receber acessos, alguns recursos da hospedagem podem precisar ser inicializados novamente no próximo acesso.

Esse processo pode envolver:

- inicialização do container da aplicação;
- inicialização do Apache e PHP;
- carregamento das configurações e caches do Laravel;
- conexão com o banco PostgreSQL externo;
- execução das verificações configuradas no startup.

Por esse motivo, o primeiro acesso após um período de inatividade pode ser perceptivelmente mais lento.

Após a aplicação estar ativa, as próximas requisições normalmente são processadas com muito menos latência.

## Cenário de implantação comercial

Para um ambiente comercial, este projeto foi pensado para utilizar uma infraestrutura permanentemente ativa e com recursos dedicados.

Uma das arquiteturas consideradas para produção seria:

- **Laravel Forge** para provisionamento, configuração e gerenciamento do servidor;
- **DigitalOcean** como infraestrutura de hospedagem;
- servidor dedicado à aplicação Laravel;
- banco de dados PostgreSQL gerenciado ou em instância separada;
- workers de fila executando continuamente;
- cache e sessões utilizando Redis;
- HTTPS e domínio próprio;
- backups automatizados;
- monitoramento de aplicação e servidor;
- escalabilidade vertical ou horizontal conforme o aumento de acessos.

Nesse cenário, a aplicação não dependeria do ciclo de suspensão utilizado na hospedagem demonstrativa e teria recursos dimensionados para suportar um volume maior de requisições.

A infraestrutura atual foi escolhida apenas para disponibilizar o projeto publicamente como portfólio, mantendo baixo custo de hospedagem.

# Barbearia SaaS — Backend Laravel + Filament

Backend de uma plataforma de gestão para barbearias, desenvolvido como projeto de portfólio.

O projeto reúne:

- painel administrativo em **Filament 3**
- API REST para aplicativo/mobile
- autenticação com **Laravel Sanctum**
- arquitetura **multi-tenant**
- gerenciamento de agendamentos
- barbeiros e serviços
- estoque e produtos
- vendas e caixa
- planos e assinaturas
- dashboard e calendário
- ambiente público de demonstração
- integrações de pagamento presentes no código, mas não necessárias para a demonstração pública

> Este projeto é apresentado como **portfólio técnico**. A versão pública foi preparada para navegação e demonstração das principais funcionalidades, e não deve ser tratada como um ambiente comercial de produção.

---

## Demo online

**Aplicação**

https://barbearia-api-xxvv.onrender.com

**Conta de demonstração**

```text
E-mail: demo@barbearia.app
Senha: Demo@12345
```

A conta demo possui dados previamente cadastrados para facilitar a avaliação do sistema.

### Importante sobre a demo

A conta pública é protegida contra ações que poderiam quebrar a experiência para os próximos visitantes.

Algumas operações podem exibir:

> Esta ação está desativada no ambiente demonstrativo.

Isso é intencional.

A demonstração prioriza:

- navegação pelo sistema
- visualização dos módulos
- filtros e buscas
- dashboard
- agenda
- calendário
- serviços
- barbeiros
- estoque
- vendas
- planos
- assinaturas

Ações destrutivas ou sensíveis podem ser bloqueadas.

---

# Principais tecnologias

## Backend

- PHP 8.4
- Laravel 11
- Laravel Sanctum
- PostgreSQL
- Eloquent ORM

## Painel administrativo

- Filament 3
- Livewire 3
- Filament FullCalendar
- Laravel Trend

## Infraestrutura da demonstração

- Docker
- Apache
- Render
- PostgreSQL hospedado externamente

## Outros pacotes presentes

- Mercado Pago PHP SDK
- Simple QR Code
- PHP Pix
- PHPUnit

---

# Funcionalidades

## Dashboard

Apresenta informações resumidas da operação da barbearia, como dados de agendamentos, receitas e desempenho.

## Agendamentos

Permite:

- visualizar agendamentos
- filtrar por situação e data
- pesquisar clientes
- selecionar barbeiro
- selecionar serviço
- consultar horários disponíveis
- visualizar status
- visualizar situação do pagamento
- integrar os dados ao calendário

A API também revalida barbeiro, serviço, barbearia e disponibilidade antes de criar um agendamento.

## Barbeiros

Gerenciamento dos profissionais vinculados à barbearia.

## Serviços

Cadastro e gerenciamento dos serviços oferecidos, incluindo preço e duração.

## Produtos e estoque

Controle de produtos da barbearia, estoque e movimentações relacionadas às vendas.

## Vendas e caixa

Registro de vendas e itens associados.

O projeto contém fluxos de pagamento e baixa de estoque, mas a conta pública de demonstração possui proteções adicionais.

## Planos e assinaturas

Gerenciamento de planos de clientes e assinaturas.

## Multi-tenant

Cada barbearia funciona como um tenant independente.

O sistema restringe os dados e relacionamentos de acordo com a barbearia ativa.

A conta demo também é limitada especificamente ao tenant demonstrativo.

---

# Modo de demonstração

O projeto possui uma camada específica de proteção para a conta pública.

Configuração:

```env
DEMO_MODE=true
DEMO_EMAIL=demo@barbearia.app
DEMO_TENANT_SLUG=barbearia-demo
```

Opcionalmente:

```env
DEMO_USER_ID=
DEMO_TENANT_ID=
```

Quando informados, os IDs têm prioridade sobre e-mail e slug.

A lógica central está em:

```text
app/Support/DemoAccess.php
```

As proteções também são aplicadas em Resources do Filament, observers, middleware da API e fluxos sensíveis.

---

# O que a conta demo NÃO pode fazer

Para evitar que um visitante prejudique a experiência dos próximos usuários, determinadas operações são restringidas.

Entre elas podem estar:

- exclusão de registros protegidos
- exclusões em massa
- alteração de dados estruturais da barbearia
- alteração das credenciais da conta demo
- criação de outro tenant
- mudanças críticas em assinaturas
- operações financeiras reais
- conexão/desconexão de contas de pagamento
- acesso a outros tenants

A API da conta demo também restringe operações de escrita protegidas.

---

# Pagamentos e Mercado Pago

O repositório contém código relacionado a:

- PIX
- Mercado Pago
- OAuth
- cartões
- webhooks
- pagamentos de assinatura

Porém, **essas integrações não são requisito da demonstração pública**.

Na conta demo:

- nenhum pagamento real deve ser necessário
- o PIX pode utilizar uma apresentação demonstrativa
- ações financeiras sensíveis são protegidas
- o usuário não precisa possuir credenciais do Mercado Pago

O código foi mantido para demonstrar a arquitetura e os fluxos desenvolvidos.

## Possíveis erros relacionados a pagamentos

Se o projeto for executado fora da conta demo e alguém tentar utilizar Mercado Pago sem configurar as credenciais necessárias, os fluxos de pagamento podem falhar.

Variáveis existentes:

```env
MERCADOPAGO_ACCESS_TOKEN=
MERCADOPAGO_PUBLIC_KEY=
MERCADO_PAGO_WEBHOOK_SECRET=

MP_APP_ID=
MP_APP_SECRET=
```

Para avaliar o portfólio, **não é necessário configurar essas variáveis**.

---

# API REST

Todas as rotas abaixo utilizam o prefixo:

```text
/api
```

## Autenticação

### Registrar usuário

```http
POST /api/register
```

Campos principais:

```json
{
  "name": "Nome",
  "email": "email@exemplo.com",
  "password": "senha",
  "password_confirmation": "senha"
}
```

### Login

```http
POST /api/login
```

```json
{
  "email": "email@exemplo.com",
  "password": "senha"
}
```

Retorna token Sanctum.

### Usuário autenticado

```http
GET /api/user
```

Requer:

```http
Authorization: Bearer TOKEN
```

### Atualizar perfil

```http
PUT /api/user
```

Permite atualizar dados do usuário e, quando enviados corretamente, senha e e-mail.

### Logout

```http
POST /api/logout
```

Revoga o token atual.

---

# API pública da barbearia

## Dados da barbearia

```http
GET /api/{slug}
```

Exemplo:

```text
/api/barbearia-demo
```

## Planos

```http
GET /api/{slug}/plans
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

Parâmetros principais:

```text
date
barber_id
service_id
```

O backend valida se barbeiro e serviço pertencem à barbearia do slug informado.

---

# API de agendamentos

Requer autenticação Sanctum.

## Listar agendamentos

```http
GET /api/appointments
```

## Criar agendamento

```http
POST /api/appointments
```

Campos principais:

```json
{
  "barber_id": 1,
  "service_id": 1,
  "scheduled_at": "2026-09-28 15:00:00",
  "client_phone": "18999999999"
}
```

Antes de salvar, o backend verifica:

- tenant
- barbeiro
- serviço
- disponibilidade
- conflitos de horário

Combinações inválidas ou horários indisponíveis podem retornar:

```text
422 Unprocessable Entity
```

Slug inexistente pode retornar:

```text
404 Not Found
```

## Cancelar agendamento

```http
DELETE /api/appointments/{id}
```

---

# API de assinaturas

## Assinatura atual

```http
GET /api/user/subscription
```

## Criar assinatura

```http
POST /api/subscribe
```

## Cancelar assinatura

```http
POST /api/subscribe/cancel
```

Os endpoints existem no projeto, mas fluxos financeiros externos não são necessários para a avaliação da versão de portfólio.

---

# Suporte

```http
POST /api/support/report
```

Tipos aceitos:

```text
bug
suggestion
other
```

---

# Rate limiting

A API possui limites de requisição em diferentes grupos.

Entre as respostas que um cliente deve estar preparado para tratar:

```text
401 Unauthorized
403 Forbidden
404 Not Found
422 Unprocessable Entity
429 Too Many Requests
```

Na conta demo, algumas operações protegidas retornam `403`.

---

# Instalação local

## Requisitos

- PHP 8.4+
- Composer
- PostgreSQL ou banco compatível configurado
- extensões PHP necessárias ao Laravel e ao PostgreSQL
- Node.js/NPM apenas caso queira recompilar assets

Clone:

```bash
git clone https://github.com/buja23/barbearia-api.git
cd barbearia-api
```

Instale as dependências:

```bash
composer install
```

Crie o `.env`:

```bash
cp .env.example .env
```

Gere a chave:

```bash
php artisan key:generate
```

Configure o banco no `.env`.

Depois:

```bash
php artisan migrate
```

Para popular o ambiente demonstrativo:

```bash
php artisan db:seed --class=DemoSeeder
```

Crie o link de storage:

```bash
php artisan storage:link
```

Execute:

```bash
php artisan serve
```

---

# Ambiente local e `.env.example`

O `.env.example` contém valores genéricos e deve ser adaptado ao ambiente utilizado.

A demonstração hospedada utiliza configuração diferente da configuração local padrão.

Não copie credenciais reais para o repositório.

Nunca versione:

```text
.env
tokens
senhas
segredos de webhook
chaves privadas
credenciais de banco
```

---

# Docker

O projeto possui `Dockerfile` baseado em:

```text
php:8.4-apache
```

A imagem instala extensões utilizadas pelo projeto, configura o Apache para servir `/public` e executa Composer durante o build.

No ambiente demonstrativo atual, o startup executa:

```bash
php artisan optimize:clear
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=DemoSeeder --force
php artisan optimize
```

e então inicia o Apache.

---

# Reset automático da demo

Na configuração atual do deploy, o `DemoSeeder` é executado durante a inicialização do container.

Isso significa que reinicializações podem restaurar os dados demonstrativos.

Para este portfólio, esse comportamento é intencional porque ajuda a manter a conta pública utilizável depois que diferentes visitantes acessam o sistema.

Consequências:

- alterações feitas por visitantes podem não ser permanentes
- dados da demo podem voltar ao estado inicial
- IDs de registros demonstrativos podem mudar
- não utilize esse comportamento como estratégia para um ambiente comercial real

---

# Deploy no Render

A demonstração utiliza container Docker.

Durante o primeiro acesso após um período de inatividade, o serviço pode demorar mais para responder.

Se a primeira abertura estiver lenta:

1. aguarde alguns segundos
2. atualize a página
3. tente novamente

Isso não significa necessariamente que a aplicação esteja com erro.

---

# Erros e limitações conhecidas

## 1. Mercado Pago

Fluxos reais de Mercado Pago não fazem parte da homologação da demo.

Sem credenciais, determinadas funcionalidades financeiras destinadas a contas reais podem não funcionar.

A conta demo foi preparada para não depender desses fluxos.

## 2. Dados da demo são restaurados

O seed demonstrativo pode ser executado novamente durante reinicializações.

Não espere persistência permanente das alterações realizadas na conta pública.

## 3. Hospedagem da demonstração

Por ser uma demonstração hospedada em infraestrutura econômica/gratuita, o primeiro acesso pode ser mais lento.

## 4. Escrita pela API usando a conta demo

A conta demo possui restrições adicionais.

É esperado receber:

```text
403 Forbidden
```

ao tentar determinadas operações de escrita.

## 5. Validações de agendamento

Um agendamento pode retornar `422` quando:

- barbeiro não pertence à barbearia
- serviço não pertence à barbearia
- barbeiro e serviço pertencem a tenants diferentes
- horário não está mais disponível
- existe conflito de agenda
- dados de entrada são inválidos

Isso é comportamento esperado.

## 6. Recuperação de senha e infraestrutura externa

Recursos que dependem de e-mail, serviços externos ou infraestrutura adicional podem necessitar configuração própria no ambiente local.

Eles não são necessários para explorar a conta pública de portfólio.

---

# Segurança da demonstração

A versão pública possui proteções adicionais para evitar:

- exclusões destrutivas
- troca das credenciais da demo
- acesso entre tenants
- modificação de configurações financeiras
- alterações críticas no cenário demonstrativo

Essas restrições fazem parte do ambiente de apresentação e não representam necessariamente as permissões de uma conta real em uma implantação comercial.

---

# Estrutura principal

```text
app/
├── Filament/
│   ├── Pages/
│   ├── Resources/
│   └── Widgets/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Observers/
├── Services/
└── Support/

config/
database/
resources/
routes/
tests/
```

Arquivos importantes:

```text
routes/api.php
routes/web.php
app/Support/DemoAccess.php
app/Observers/DemoProtectionObserver.php
app/Services/PaymentService.php
database/seeders/DemoSeeder.php
Dockerfile
```

---

# Testes

O projeto possui testes de regressão para fluxos importantes, incluindo proteções da demonstração.

Quando todas as dependências estiverem instaladas:

```bash
php artisan test
```

ou:

```bash
composer test
```

Também é útil validar:

```bash
composer dump-autoload --optimize
php artisan route:list
php artisan config:cache
php artisan route:cache
```

---

# Objetivo deste projeto

Este projeto foi desenvolvido para demonstrar conhecimentos em:

- desenvolvimento backend com Laravel
- APIs REST
- autenticação
- arquitetura multi-tenant
- modelagem relacional
- regras de negócio
- dashboards administrativos
- Filament e Livewire
- controle de estoque
- agenda e disponibilidade
- integração entre backend e aplicativo
- Docker e deploy
- proteção de ambiente demonstrativo
- integração com serviços externos

O foco atual é **portfólio técnico**.

A demonstração pública foi preparada para permitir que recrutadores e avaliadores explorem o sistema sem depender de configurações externas sensíveis.

---

# Autor

**Victor Azambuja**

GitHub:

https://github.com/buja23

Repositório:

https://github.com/buja23/barbearia-api
