# Instruções de Uso - Frontend LibreSign SaaS

## 🚀 Como Rodar o Projeto

### Opção 1: Com Docker (Recomendado)

```bash
# Na raiz do projeto (libresign-saas/)
docker compose up

# Ou apenas o frontend
docker compose up frontend
```

O frontend estará disponível em: http://localhost:3000

### Opção 2: Local (sem Docker)

```bash
# Entrar na pasta do frontend
cd frontend

# Instalar dependências
npm install

# Rodar em modo desenvolvimento
npm run dev
```

## 📋 Funcionalidades Implementadas

### 1. Autenticação
- **Login** (`/login`)
  - Email e senha
  - Validação de campos
  - Redirecionamento automático para dashboard
  
- **Registro** (`/register`)
  - Nome completo
  - Email
  - Senha e confirmação
  - Nome da empresa
  - Telefone (opcional)
  - Cargo/Função (opcional)

### 2. Dashboard (`/dashboard`)

#### Cards de Estatísticas:
1. **Documentos do Mês**
   - Quantidade de documentos assinados no mês
   - Barra de progresso com limite do plano
   - Porcentagem de uso

2. **Total de Documentos**
   - Contador total de documentos
   - Todos os documentos assinados

3. **Plano Atual**
   - Nome do plano
   - Valor mensal
   - Design com gradiente azul

#### Seções de Ação:
1. **Plataforma de Assinatura**
   - Botão para acessar LibreSign
   - (TODO: Implementar SSO)

2. **Gerenciar Plano**
   - Informações do plano atual
   - Data de renovação
   - Alterar plano (em desenvolvimento)

3. **Segurança**
   - Botão "Trocar Senha"
   - Modal com validação completa

4. **Informações da Conta**
   - Email, telefone, cargo
   - Ver detalhes (em desenvolvimento)

### 3. Modal de Troca de Senha

- Campo de senha atual
- Campo de nova senha
- Campo de confirmação
- Validação em tempo real:
  - ✓ Mínimo 8 caracteres
  - ✓ Letra maiúscula
  - ✓ Letra minúscula
  - ✓ Número
  - ✓ Caractere especial
- Indicadores visuais de requisitos
- Botões de cancelar e salvar

## 🔐 Segurança

### Middleware de Proteção
- Rotas protegidas requerem autenticação
- Usuários não autenticados → redirecionados para `/login`
- Usuários autenticados em `/login` → redirecionados para `/dashboard`

### Token JWT
- Armazenado em cookie (httpOnly na produção)
- Enviado em todas as requisições autenticadas
- Header: `Authorization: Bearer {token}`

## 🎨 Design

### Cores do LibreSign
- **Azul**: #3056D3
- **Verde**: #13C296
- **Laranja**: #F7931E

### Responsividade
- **Desktop**: 3 colunas
- **Tablet**: 2 colunas
- **Mobile**: 1 coluna

## 📡 Integração com Backend

### Endpoints Utilizados:
- `POST /api/login` - Autenticação
- `POST /api/register` - Cadastro
- `GET /api/user` - Dados do usuário
- `GET /api/user/stats` - Estatísticas
- `GET /api/user/plan` - Informações do plano
- `POST /api/user/change-password` - Trocar senha
- `POST /api/logout` - Logout

### Fallback de Dados
Se as APIs não estiverem prontas, o sistema usa dados mockados:
```typescript
stats: {
  documents_signed_this_month: 127,
  total_documents: 543,
}
plan: {
  name: 'Profissional',
  price: 149,
  documents_limit: 500,
  renewal_date: '2025-12-08',
}
```

## 🧪 Como Testar

1. **Iniciar os serviços**:
   ```bash
   docker compose up
   ```

2. **Acessar o frontend**: http://localhost:3000

3. **Criar uma conta**:
   - Ir para `/register`
   - Preencher o formulário
   - Submeter

4. **Fazer login**:
   - Usar as credenciais criadas
   - Será redirecionado para `/dashboard`

5. **Explorar o dashboard**:
   - Ver estatísticas de uso
   - Clicar em "Trocar Senha"
   - Testar validação de senha
   - Clicar em "Acessar Plataforma"
   - Fazer logout

## 🔧 Variáveis de Ambiente

Crie `.env.local` na raiz do frontend:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000
```

## 📝 Notas

- O sistema está preparado para integrar com o backend Laravel
- As rotas de API já estão configuradas
- Dados mockados serão substituídos quando o backend implementar os endpoints
- SSO com plataforma LibreSign está marcado como TODO

## 🐛 Troubleshooting

### Frontend não inicia
```bash
# Limpar cache e reinstalar
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Erro de CORS
Verifique se o backend Laravel tem CORS configurado para aceitar requisições de `http://localhost:3000`

### Token inválido
Limpe os cookies do navegador ou use modo anônimo

## 📚 Estrutura de Código

```
src/
├── app/              # Páginas (App Router)
├── components/       # Componentes reutilizáveis
├── services/         # Serviços de API
├── styles/           # Estilos globais
└── middleware.ts     # Proteção de rotas
```

## ✅ Checklist de Funcionalidades

- [x] Página de login
- [x] Página de registro
- [x] Dashboard com estatísticas
- [x] Cards de informação
- [x] Modal de troca de senha
- [x] Validação de senha forte
- [x] Proteção de rotas
- [x] Logout
- [x] Design responsivo
- [x] Toast notifications
- [x] Loading states
- [x] Integração com API
- [ ] SSO com LibreSign
- [ ] Gerenciamento de planos
- [ ] Histórico de documentos
- [ ] Gráficos de uso
