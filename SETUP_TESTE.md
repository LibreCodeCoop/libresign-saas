# Setup Rápido - Usuário de Teste

## 🚀 Inicialização Rápida

### 1. Configurar Backend

```bash
cd backend

# Copiar .env
cp .env.example .env

# Gerar chave
php artisan key:generate

# Executar migrations
php artisan migrate

# Criar usuário de teste
php artisan db:seed --class=UserSeeder
```

### 2. Iniciar Servidores

```bash
# Terminal 1 - Backend
cd backend
php artisan serve

# Terminal 2 - Frontend
cd frontend
npm run dev
```

## 🔐 Credenciais de Teste

Use estas credenciais para fazer login:

```
Email: teste@libresign.coop
Senha: Teste@123
```

## 📋 Dados do Usuário de Teste

- **Nome**: Teste LibreSign
- **Email**: teste@libresign.coop
- **Telefone**: (11) 98765-4321
- **Empresa**: LibreCode Cooperativa
- **Cargo**: Desenvolvedor

## 🧪 Testando

1. Acesse: http://localhost:3000
2. Clique em "Acessar Plataforma" no header
3. Faça login com as credenciais acima
4. Você será redirecionado para `/dashboard`

## 🔄 Resetar Banco de Dados

Se precisar resetar tudo:

```bash
cd backend
php artisan migrate:fresh --seed
```

Isso irá:
- Apagar todas as tabelas
- Recriar as migrations
- Criar o usuário de teste novamente

## 📝 Nota sobre a Senha

A senha `Teste@123` atende a todos os requisitos:
- ✓ 8+ caracteres
- ✓ Letra maiúscula (T)
- ✓ Letra minúscula (este)
- ✓ Número (123)
- ✓ Caractere especial (@)
