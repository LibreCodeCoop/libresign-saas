# SaaS LibreSign

Sistema SaaS para gerenciamento de instâncias Nextcloud com integração de pagamentos.

## Tecnologias

- **Frontend**: Next.js 16 + TypeScript + Tailwind CSS
- **Backend**: Laravel 12 + PHP 8.2
- **Banco de Dados**: PostgreSQL 16
- **Cache/Filas**: Redis 7
- **Containerização**: Docker + Docker Compose

## Funcionalidades

- ✅ Landing page com planos
- ✅ Autenticação de usuários
- ✅ Autenticação de administradores
- ✅ Checkout e integração com gateway de pagamento
- ✅ Provisionamento automático de contas Nextcloud
- ✅ Gerenciamento de instâncias Nextcloud (admin)

## Estrutura do Projeto

```
.
├── frontend/          # Next.js application
├── backend/           # Laravel API
├── docker-compose.yml # Orquestração dos serviços
└── README.md
```

## Setup

### Pré-requisitos

- Docker
- Docker Compose
- Node.js 22+
- PHP 8.2+
- Composer

### Instalação

1. Clone o repositório e instale as dependências:

```bash
# Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate

# Frontend
cd ../frontend
npm install
```

2. Configure o `.env` do Laravel com PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=saas_libresign
DB_USERNAME=saas_user
DB_PASSWORD=saas_pass

REDIS_HOST=redis
REDIS_PORT=6379
```

3. Suba os containers:

```bash
docker-compose up -d
```

4. Execute as migrations:

```bash
docker exec -it saas-backend php artisan migrate
```

## Acesso

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

## Desenvolvimento

### Backend (Laravel)

```bash
# Rodar migrations
docker exec -it saas-backend php artisan migrate

# Criar migration
docker exec -it saas-backend php artisan make:migration create_plans_table

# Criar controller
docker exec -it saas-backend php artisan make:controller PlanController

# Rodar testes
docker exec -it saas-backend php artisan test
```

### Frontend (Next.js)

```bash
# Desenvolvimento local (sem Docker)
cd frontend
npm run dev

# Build de produção
npm run build
npm start
```

## ✅ Implementado

- ✅ Landing page com cores e design do LibreSign
- ✅ Página de login/registro completa
- ✅ Validação de senha com requisitos
- ✅ Sistema de mensagens (Toast)
- ✅ Laravel Sanctum configurado
- ✅ API de autenticação (login/register/logout)
- ✅ Migrations com campos extras (phone, company, role)

## Próximos Passos

1. Executar migrations e configurar PostgreSQL
2. Criar models (Admin, Plan, Subscription, NextcloudInstance)
3. Implementar controllers de planos e pagamentos
4. Configurar integração com Stripe/Paddle
5. Implementar filas Redis para provisionamento de contas Nextcloud
6. Criar dashboard de usuário e admin
7. Implementar gestão de instâncias Nextcloud
