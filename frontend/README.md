# LibreSign SaaS - Frontend

Frontend da plataforma LibreSign SaaS construído com Next.js 14, React 18 e TypeScript.

## 🚀 Tecnologias

- **Next.js 14** - Framework React com App Router
- **React 18** - Biblioteca para interfaces
- **TypeScript** - Tipagem estática
- **Tailwind CSS** - Framework CSS utility-first
- **React Hot Toast** - Notificações toast
- **js-cookie** - Gerenciamento de cookies

## 📋 Funcionalidades Implementadas

### ✅ Autenticação
- Login com email e senha
- Registro de novos usuários
- Logout
- Proteção de rotas com middleware
- Gerenciamento de token JWT

### ✅ Dashboard
- Cards de estatísticas (documentos do mês, total, plano)
- Barra de progresso de uso
- Acesso à plataforma de assinatura
- Gerenciamento de plano
- Troca de senha com validação em tempo real
- Informações da conta

### ✅ Segurança
- Middleware para proteção de rotas
- Validação de senha forte:
  - Mínimo 8 caracteres
  - Letra maiúscula
  - Letra minúscula
  - Número
  - Caractere especial

### ✅ Design
- Cores do LibreSign (#3056D3, #13C296, #F7931E)
- Responsivo (mobile, tablet, desktop)
- Loading states
- Animações suaves
- Toast notifications

## 🛠️ Instalação

```bash
# Instalar dependências
npm install

# Rodar em desenvolvimento
npm run dev

# Build para produção
npm run build

# Rodar em produção
npm start
```

## 🐳 Docker

```bash
# Build da imagem
docker build -t libresign-frontend .

# Rodar container
docker run -p 3000:3000 libresign-frontend

# Ou usar docker compose
docker compose up frontend
```

## 🔧 Configuração

Crie um arquivo `.env.local` na raiz do projeto:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

## 📁 Estrutura de Pastas

```
frontend/
├── src/
│   ├── app/                  # App Router do Next.js
│   │   ├── dashboard/        # Página do dashboard
│   │   ├── login/            # Página de login
│   │   ├── register/         # Página de registro
│   │   ├── layout.tsx        # Layout raiz
│   │   └── page.tsx          # Página inicial
│   ├── components/           # Componentes React
│   │   ├── ActionCard.tsx
│   │   ├── ChangePasswordModal.tsx
│   │   ├── Header.tsx
│   │   └── StatCard.tsx
│   ├── services/             # Serviços e APIs
│   │   └── api.ts
│   ├── styles/               # Estilos globais
│   │   └── globals.css
│   └── middleware.ts         # Middleware de autenticação
├── public/                   # Arquivos estáticos
├── Dockerfile
├── next.config.js
├── tailwind.config.js
├── tsconfig.json
└── package.json
```

## 🔗 Endpoints da API

O frontend consome os seguintes endpoints do backend:

- `POST /api/login` - Login
- `POST /api/register` - Registro
- `POST /api/logout` - Logout
- `GET /api/user` - Dados do usuário
- `GET /api/user/stats` - Estatísticas (com fallback para dados mockados)
- `GET /api/user/plan` - Plano do usuário (com fallback para dados mockados)
- `POST /api/user/change-password` - Troca de senha

## 📱 Páginas

### `/` - Home
Redireciona para `/dashboard` se autenticado ou `/login` se não autenticado.

### `/login` - Login
Formulário de login com email e senha.

### `/register` - Registro
Formulário de registro com validação de campos.

### `/dashboard` - Dashboard
Página principal com estatísticas e ações do usuário.

## 🎨 Customização de Cores

As cores do LibreSign estão configuradas no `tailwind.config.js`:

```js
colors: {
  libresign: {
    blue: '#3056D3',
    green: '#13C296',
    orange: '#F7931E',
  },
}
```

## 📝 Dados Mockados

Enquanto as APIs do backend não estiverem prontas, o serviço de API retorna dados mockados para:
- Estatísticas do usuário
- Informações do plano

## 🔄 Fluxo de Autenticação

1. Usuário faz login → Token JWT é salvo em cookie
2. Middleware verifica token em todas as requisições
3. Token é enviado no header `Authorization: Bearer {token}`
4. Se token inválido → Redireciona para login

## 📄 Licença

Este projeto faz parte do LibreSign SaaS.
