# Guia de Início Rápido

## 🚀 Configuração Inicial

### 1. Backend (Laravel)

```bash
cd backend

# Copiar arquivo de ambiente
cp .env.example .env

# Configurar banco de dados no .env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=saas_libresign
DB_USERNAME=saas_user
DB_PASSWORD=saas_pass

# Gerar chave da aplicação
php artisan key:generate

# Executar migrations
php artisan migrate

# Iniciar servidor
php artisan serve
```

### 2. Frontend (Next.js)

```bash
cd frontend

# Instalar dependências (se necessário)
npm install

# Iniciar servidor de desenvolvimento
npm run dev
```

### 3. Docker (Opcional - Para PostgreSQL e Redis)

```bash
# Na raiz do projeto
docker-compose up -d postgres redis
```

## 🧪 Testando a Aplicação

### Acessar

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api

### Testar Registro

1. Acesse http://localhost:3000/login
2. Clique em "crie uma nova conta"
3. Preencha todos os campos:
   - Nome completo
   - Celular
   - Empresa
   - Cargo/Função
   - E-mail
   - Senha (deve atender aos requisitos)
   - Confirmar senha
   - Aceitar termos
4. Clique em "Criar conta"

### Validação de Senha

A senha deve conter:
- ✓ Mínimo 8 caracteres
- ✓ Letra maiúscula
- ✓ Letra minúscula
- ✓ Número
- ✓ Caractere especial

### Testar Login

1. Acesse http://localhost:3000/login
2. Digite e-mail e senha
3. Clique em "Entrar"

## 📡 Endpoints da API

### Público

- `POST /api/register` - Criar nova conta
- `POST /api/login` - Fazer login

### Autenticado (requer token)

- `GET /api/user` - Obter dados do usuário
- `POST /api/logout` - Fazer logout

## 🔑 Autenticação

O sistema usa **Laravel Sanctum** com tokens de API.

Após login/registro bem-sucedido:
1. Token é armazenado no `localStorage`
2. Token é enviado no header `Authorization: Bearer {token}`
3. Redirecionamento automático para `/dashboard` (a ser criado)

## 🎨 Cores do LibreSign

- **Primária (azul)**: `#3056D3`
- **Secundária (verde)**: `#13C296`
- **Destaque (laranja)**: `#F7931E`

## 📝 Mensagens

O sistema exibe mensagens de:
- ✅ **Sucesso**: Conta criada, login bem-sucedido
- ❌ **Erro**: Validação, credenciais incorretas, erro de servidor
- ℹ️ **Info**: Informações gerais
- ⚠️ **Warning**: Avisos

## 🐛 Troubleshooting

### Erro de CORS

Se encontrar erros de CORS, adicione no `.env` do Laravel:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

E no arquivo `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'supports_credentials' => true,
```

### Erro de conexão com PostgreSQL

Verifique se o PostgreSQL está rodando:

```bash
docker-compose ps
```

Se não estiver, inicie:

```bash
docker-compose up -d postgres
```

### Migrations não executam

Certifique-se de que o banco existe:

```bash
docker exec -it saas-postgres psql -U saas_user -d saas_libresign
```

## 📦 Próximas Funcionalidades

- [ ] Dashboard de usuário
- [ ] Dashboard de administrador
- [ ] Gerenciamento de planos
- [ ] Integração com Stripe
- [ ] Provisionamento automático de contas Nextcloud
- [ ] Gerenciamento de instâncias Nextcloud
