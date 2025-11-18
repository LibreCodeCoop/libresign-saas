# SaaS LibreSign

Plataforma SaaS para gerenciar usuários e instâncias Nextcloud com planos e provisionamento automático.

## Tecnologias

- Frontend: Next.js + React + TypeScript + Tailwind CSS
- Backend: Laravel (PHP 8.2)
- Banco de Dados: PostgreSQL 16
- Cache/Filas: Redis 7
- Docker: desenvolvimento e produção containerizados

## Funcionalidades

- Cadastro/login de usuários
- Planos com limites e quotas
- Criação automática de usuário e grupo no Nextcloud (job em background)
- Dashboard do usuário
- Painel administrativo com métricas, instâncias e usuários

## Estrutura do Projeto

```
.
├── frontend/          # Next.js application
├── backend/           # Laravel API
├── docker-compose.yml # Orquestração dos serviços
└── README.md
```

## Início rápido

Pré-requisitos: Docker e Docker Compose

1) Suba os serviços

```bash
docker compose up -d --build
```

2) Rode as migrations (e seeds)

```bash
docker exec saas-backend php artisan migrate --seed
```

Acesse:
- Frontend: http://localhost:3000
- API: http://localhost:8000

## Como testar o SaaS

1) Criar um usuário
- Acesse http://localhost:3000
- Clique em Entrar > Cadastrar
- Preencha nome, email, empresa, senha e crie a conta

Resultado esperado:
- Usuário criado com plano Trial automaticamente
- Um job é enfileirado para criar o usuário no Nextcloud e o grupo pessoal

2) Ver o dashboard do usuário
- Após login, acesse /dashboard
- Você verá informações do plano e métricas básicas

3) Promover a admin (opcional)

```bash
docker exec saas-backend php artisan tinker --execute="
App\Models\User::where('email', 'seu@email')->update(['is_admin' => true]);
"
```

Depois, acesse o painel em http://localhost:3000/admin
- Dashboard com métricas (usuários por plano/instância, storage, etc.)
- Lista de instâncias em /admin/instances

## Desenvolvimento

### Backend (Laravel)

```bash
# Migrations/Seeds
docker exec saas-backend php artisan migrate --seed

# Limpar cache
docker exec saas-backend php artisan cache:clear && \
  docker exec saas-backend php artisan config:clear

# Console interativo
docker exec saas-backend php artisan tinker
```

### Filas (jobs)

```bash
# Processar um job
docker exec saas-backend php artisan queue:work --once

# Worker contínuo (dev)
docker exec saas-backend php artisan queue:work --verbose
```

### Frontend (Next.js)

```bash
# Logs do frontend
docker logs -f saas-frontend

# Rebuild apenas do frontend
docker compose up -d --build frontend
```

## Notas

- A criação de usuário no Nextcloud via job requer uma instância configurada em Admin > Instâncias
- Métodos suportados para gerenciar Nextcloud: SSH, API ou Docker
- Em dev, você pode testar a fila rodando um job único (`queue:work --once`)

## Licença

MIT
