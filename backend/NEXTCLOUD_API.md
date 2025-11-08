# API Nextcloud - Documentação

Este documento descreve todas as APIs disponíveis para gerenciar instâncias do Nextcloud e interagir com elas via SSH/occ.

> 🔑 **Antes de começar**: Veja [SSH_SETUP.md](SSH_SETUP.md) para aprender como gerar e configurar as chaves SSH necessárias.

## Autenticação

Todas as rotas de administração requerem:
1. **Autenticação**: Token Bearer (Sanctum)
2. **Permissão**: Usuário com `is_admin = true`

Header exemplo:
```
Authorization: Bearer {token}
```

---

## Gerenciamento de Instâncias

### Listar todas as instâncias
```http
GET /api/admin/instances
```

**Resposta:**
```json
[
  {
    "id": 1,
    "name": "Nextcloud Produção",
    "url": "https://cloud.example.com",
    "ssh_host": "192.168.1.100",
    "ssh_port": 22,
    "ssh_user": "ubuntu",
    "docker_container_name": "nextcloud-docker-app-1",
    "status": "active",
    "max_users": 100,
    "current_users": 45,
    "notes": "Servidor principal"
  }
]
```

### Criar instância
```http
POST /api/admin/instances
Content-Type: application/json

{
  "name": "Nextcloud Produção",
  "url": "https://cloud.example.com",
  "ssh_host": "192.168.1.100",
  "ssh_port": 22,
  "ssh_user": "ubuntu",
  "ssh_private_key": "-----BEGIN PRIVATE KEY-----\n...",
  "docker_container_name": "nextcloud-docker-app-1",
  "status": "active",
  "max_users": 100,
  "notes": "Servidor principal"
}
```

### Obter instância específica
```http
GET /api/admin/instances/{id}
```

### Atualizar instância
```http
PUT /api/admin/instances/{id}
Content-Type: application/json

{
  "status": "maintenance",
  "max_users": 200
}
```

### Deletar instância
```http
DELETE /api/admin/instances/{id}
```

### Testar conexão SSH
```http
POST /api/admin/instances/{id}/test-connection
```

**Resposta:**
```json
{
  "success": true,
  "message": "Conexão testada com sucesso",
  "instance": "Nextcloud Produção"
}
```

---

## Gerenciamento de Usuários

Todas as rotas de usuários usam o padrão:
```
/api/admin/instances/{instanceId}/users
```

### Listar todos os usuários
```http
GET /api/admin/instances/1/users
```

**Resposta:**
```json
{
  "instance": "Nextcloud Produção",
  "users": {
    "joao": {
      "displayname": "João Silva",
      "email": "joao@example.com"
    },
    "maria": {
      "displayname": "Maria Santos",
      "email": "maria@example.com"
    }
  }
}
```

### Criar usuário
```http
POST /api/admin/instances/1/users
Content-Type: application/json

{
  "user_id": "joao@example.com",
  "display_name": "João Silva",
  "email": "joao@example.com",
  "password": "SenhaSegura123!",
  "groups": ["users", "employees"]
}
```

**Resposta:**
```json
{
  "message": "Usuário criado com sucesso",
  "user": {
    "user_id": "joao@example.com",
    "password": "SenhaSegura123!",
    "display_name": "João Silva",
    "email": "joao@example.com",
    "output": "The user \"joao@example.com\" was created successfully"
  }
}
```

**Nota:** Se `password` não for fornecido, uma senha aleatória será gerada.

### Obter informações do usuário
```http
GET /api/admin/instances/1/users/joao@example.com
```

**Resposta:**
```json
{
  "user_id": "joao@example.com",
  "info": "  - user_id: joao@example.com\n  - display name: João Silva\n  - email: joao@example.com\n  - groups:\n    - users\n    - employees",
  "groups": ["users", "employees"]
}
```

### Deletar usuário
```http
DELETE /api/admin/instances/1/users/joao@example.com
```

**Resposta:**
```json
{
  "message": "Usuário removido com sucesso",
  "output": "The user \"joao@example.com\" was deleted"
}
```

### Adicionar usuário a um grupo
```http
POST /api/admin/instances/1/users/joao@example.com/add-to-group
Content-Type: application/json

{
  "group_id": "managers"
}
```

### Remover usuário de um grupo
```http
POST /api/admin/instances/1/users/joao@example.com/remove-from-group
Content-Type: application/json

{
  "group_id": "managers"
}
```

### Promover usuário a sub-admin
```http
POST /api/admin/instances/1/users/joao@example.com/promote-subadmin
Content-Type: application/json

{
  "group_id": "employees"
}
```

### Obter último acesso do usuário
```http
GET /api/admin/instances/1/users/joao@example.com/last-seen
```

**Resposta:**
```json
{
  "user_id": "joao@example.com",
  "last_seen": "2025-11-08 10:30:45"
}
```

### Reenviar email de boas-vindas
```http
POST /api/admin/instances/1/users/joao@example.com/resend-welcome
```

---

## Gerenciamento de Grupos

Todas as rotas de grupos usam o padrão:
```
/api/admin/instances/{instanceId}/groups
```

### Listar todos os grupos
```http
GET /api/admin/instances/1/groups
```

**Resposta:**
```json
{
  "instance": "Nextcloud Produção",
  "groups": {
    "admin": {
      "users": ["admin"]
    },
    "users": {
      "users": ["joao", "maria", "pedro"]
    },
    "employees": {
      "users": ["joao", "maria"]
    }
  }
}
```

### Criar grupo
```http
POST /api/admin/instances/1/groups
Content-Type: application/json

{
  "group_id": "managers",
  "quota": "10GB"
}
```

**Resposta:**
```json
{
  "message": "Grupo criado com sucesso",
  "group_id": "managers",
  "output": "Created group \"managers\""
}
```

### Deletar grupo
```http
DELETE /api/admin/instances/1/groups/managers
```

**Resposta:**
```json
{
  "message": "Grupo removido com sucesso",
  "output": "Group \"managers\" was removed"
}
```

### Definir quota do grupo
```http
POST /api/admin/instances/1/groups/employees/set-quota
Content-Type: application/json

{
  "quota": "5GB"
}
```

**Valores aceitos para quota:**
- `5GB`, `10GB`, `100GB` - Tamanho específico
- `1TB`, `2TB` - Terabytes
- `unlimited` - Sem limite

**Resposta:**
```json
{
  "message": "Quota definida com sucesso",
  "group_id": "employees",
  "quota": "5GB",
  "output": "Set quota for group \"employees\""
}
```

---

## Fluxo Completo: Criar Usuário no Nextcloud

Quando um novo cliente se cadastra no SaaS, você pode criar automaticamente o usuário no Nextcloud:

```javascript
// 1. Selecionar instância disponível
const instance = instances.find(i => i.status === 'active' && i.current_users < i.max_users);

// 2. Criar usuário no Nextcloud
const response = await api.post(`/admin/instances/${instance.id}/users`, {
  user_id: user.email,
  display_name: user.name,
  email: user.email,
  groups: ['customers']
});

// 3. Guardar a senha gerada para enviar ao usuário
const { password } = response.data.user;

// 4. Enviar email com credenciais para o usuário
```

---

## Service NextcloudService

Além das APIs REST, você pode usar diretamente o service no Laravel:

```php
use App\Models\NextcloudInstance;
use App\Services\NextcloudService;

$instance = NextcloudInstance::find(1);
$nc = new NextcloudService($instance);

// Criar usuário
$result = $nc->createUser('joao@example.com', 'João Silva', 'joao@example.com');

// Adicionar ao grupo
$nc->addUserToGroup('joao@example.com', 'customers');

// Definir quota
$nc->setGroupQuota('customers', '5GB');

// Listar usuários
$users = $nc->listUsers();

// Testar conexão
$connected = $nc->testConnection();
```

---

## Códigos de Status HTTP

- `200 OK` - Operação bem-sucedida
- `201 Created` - Recurso criado com sucesso
- `422 Unprocessable Entity` - Erro de validação
- `403 Forbidden` - Acesso negado (não é admin)
- `404 Not Found` - Recurso não encontrado
- `500 Internal Server Error` - Erro na execução do comando SSH/occ

---

## Tratamento de Erros

Todas as respostas de erro seguem o padrão:

```json
{
  "message": "Descrição do erro",
  "error": "Detalhes técnicos do erro (quando disponível)",
  "errors": {
    "field": ["Mensagem de validação"]
  }
}
```

**Exemplo:**
```json
{
  "message": "Erro de validação",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

## Segurança

1. **Chaves SSH**: As chaves privadas SSH são armazenadas criptografadas no banco de dados
2. **Tokens**: Tokens de autenticação são gerenciados pelo Laravel Sanctum
3. **Middleware Admin**: Apenas usuários com `is_admin = true` podem acessar estas rotas
4. **Logs**: Todos os comandos executados são registrados no log do Laravel

---

## Próximos Passos

- Implementar sistema de pagamentos
- Criar lógica de alocação automática de instâncias
- Adicionar SSO entre SaaS e Nextcloud
- Implementar dashboard de monitoramento
- Adicionar webhooks para notificações
