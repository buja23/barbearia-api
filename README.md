# 💈 Barbearia API (MVP)
Backend e Painel Administrativo do SaaS de Barbearias "Barba Branca".
Este sistema gerencia o Clube de Assinaturas, Agendamentos e Configurações das barbearias parceiras.

## 🚀 Status do Projeto
- **Fase:** 1 (Backend & Backoffice)
- **Progresso:**
  - [x] Ambiente Docker Configurado (Laravel 11 + Postgres + Redis)
  - [x] Painel Administrativo instalado (FilamentPHP v3)
  - [x] Modelagem de Banco: Barbearias e Serviços
  - [ ] Integração Mercado Pago (Próximo Passo)
  - [ ] API Endpoints para Mobile

## 🛠️ Tecnologias
- **Linguagem:** PHP 8.4
- **Framework:** Laravel 11
- **Admin:** FilamentPHP
- **Banco:** PostgreSQL
- **Cache/Fila:** Redis
- **Infra:** Docker (Laravel Sail)

---

## 💻 Como Rodar o Projeto (Para Desenvolvedores)

### Pré-requisitos
1. **WSL2** (Se estiver no Windows) com Ubuntu instalado.
2. **Docker Desktop** rodando e integrado ao WSL.

### Passo a Passo
Clone o repositório (dentro do seu Linux/WSL, não no Windows):```bash
git clone <URL_DO_REPOSITORIO>
cd barbearia-api

Suba os containers (Servidor):

    ./vendor/bin/sail up -d

    (Na primeira vez pode demorar uns minutos)

Instale as dependências (se necessário):

    ./vendor/bin/sail composer install

Crie o Banco de Dados:

    ./vendor/bin/sail artisan migrate

Crie seu usuário Admin:

    ./vendor/bin/sail artisan make:filament-user

Acesse o Painel:

    URL: http://localhost/admin

    Use o email/senha que você criou.


### 📂 Estrutura Importante
app/Filament/Resources: Aqui ficam as telas do Painel Admin (Telas de Barbearia, Serviços, etc).

database/migrations: Aqui fica a estrutura do banco de dados.

docker-compose.yml: Configuração dos serviços (Banco, Redis, Mailpit).

### 🤝 Padrões de Código
Idioma: Código em Inglês (Barbershop, Service), Comentários em PT-BR.

Branchs: main para produção. Crie branchs para features (ex: feat/mercado-pago).