# Configuração de Chaves SSH para Nextcloud

Este guia explica como gerar e configurar as chaves SSH necessárias para que o SaaS possa gerenciar remotamente as instâncias do Nextcloud.

## 1. Gerar Par de Chaves SSH

No servidor onde o Laravel está rodando, gere um par de chaves SSH:

```bash
# Gerar chave SSH (sem senha para automação)
ssh-keygen -t rsa -b 4096 -f ~/.ssh/nextcloud_saas -N ""
```

Isso criará dois arquivos:
- `~/.ssh/nextcloud_saas` - Chave privada (será usada no SaaS)
- `~/.ssh/nextcloud_saas.pub` - Chave pública (será adicionada aos servidores Nextcloud)

### Alternativa: Chave ED25519 (mais moderna e segura)

```bash
ssh-keygen -t ed25519 -f ~/.ssh/nextcloud_saas -N ""
```

## 2. Copiar a Chave Pública para o Servidor Nextcloud

### Opção A: Usando ssh-copy-id (mais fácil)

```bash
# Substitua 'ubuntu' pelo usuário SSH e '192.168.1.100' pelo IP/hostname do servidor
ssh-copy-id -i ~/.ssh/nextcloud_saas.pub ubuntu@192.168.1.100
```

### Opção B: Manualmente

1. **Exibir a chave pública:**
```bash
cat ~/.ssh/nextcloud_saas.pub
```

2. **Copiar o conteúdo** (começa com `ssh-rsa` ou `ssh-ed25519`)

3. **No servidor Nextcloud**, adicionar ao arquivo `authorized_keys`:
```bash
# SSH no servidor Nextcloud
ssh ubuntu@192.168.1.100

# Adicionar a chave
echo "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQ..." >> ~/.ssh/authorized_keys

# Ajustar permissões
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

## 3. Testar a Conexão SSH

```bash
# Testar se consegue conectar sem senha
ssh -i ~/.ssh/nextcloud_saas ubuntu@192.168.1.100

# Se funcionar, teste um comando
ssh -i ~/.ssh/nextcloud_saas ubuntu@192.168.1.100 "echo 'Conexão OK'"
```

## 4. Obter a Chave Privada para o SaaS

```bash
# Exibir a chave privada
cat ~/.ssh/nextcloud_saas
```

**Copie TODO o conteúdo**, incluindo as linhas:
```
-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----
```

## 5. Cadastrar Instância no Painel Admin

1. Acesse: http://localhost:3000/admin/instances
2. Clique em **"+ Nova Instância"**
3. Preencha os campos:
   - **Nome**: Nome descritivo (ex: "Nextcloud Produção")
   - **URL**: https://cloud.example.com
   - **SSH Host**: IP ou hostname (ex: 192.168.1.100)
   - **SSH Port**: 22 (padrão)
   - **SSH User**: ubuntu (ou outro usuário configurado)
   - **Chave Privada SSH**: Cole a chave privada completa
   - **Nome do Container Docker**: nextcloud-docker-app-1 (padrão)
   - **Status**: Ativo
   - **Máximo de Usuários**: 100 (ou conforme capacidade)

4. Clique em **"Salvar"**

## 6. Testar a Conexão

Após cadastrar a instância, você pode testar via API:

```bash
# Substitua {TOKEN} pelo seu token de admin e {INSTANCE_ID} pelo ID da instância
curl -X POST http://localhost:8000/api/admin/instances/{INSTANCE_ID}/test-connection \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json"
```

Resposta esperada:
```json
{
  "success": true,
  "message": "Conexão testada com sucesso",
  "instance": "Nextcloud Produção"
}
```

## 7. Configurações Adicionais no Servidor Nextcloud

### Verificar se o Docker está acessível

```bash
# No servidor Nextcloud, verificar se o usuário tem permissão para Docker
groups ubuntu

# Se não estiver no grupo docker, adicionar:
sudo usermod -aG docker ubuntu

# Fazer logout e login novamente para aplicar
```

### Testar comando occ

```bash
# Testar se consegue executar comandos occ
docker exec -u 33 nextcloud-docker-app-1 php occ --version

# Exemplo de saída:
# Nextcloud 28.0.0
```

### Verificar nome do container Docker

```bash
# Listar containers em execução
docker ps

# Procure pelo container do Nextcloud, exemplo:
# CONTAINER ID   IMAGE              COMMAND         NAME
# abc123def456   nextcloud:latest   ...             nextcloud-docker-app-1
```

Se o nome do container for diferente, atualize no painel admin.

## 8. Segurança e Boas Práticas

### Restrições de SSH

No servidor Nextcloud, edite `/etc/ssh/sshd_config` para adicionar restrições:

```bash
# Editar configuração SSH
sudo nano /etc/ssh/sshd_config

# Adicionar estas configurações (se não existirem):
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
```

Reiniciar SSH:
```bash
sudo systemctl restart sshd
```

### Criar usuário dedicado (opcional, mais seguro)

```bash
# Criar usuário específico para o SaaS
sudo useradd -m -s /bin/bash nextcloud-saas

# Adicionar ao grupo docker
sudo usermod -aG docker nextcloud-saas

# Configurar chave SSH
sudo mkdir -p /home/nextcloud-saas/.ssh
sudo cp ~/.ssh/authorized_keys /home/nextcloud-saas/.ssh/
sudo chown -R nextcloud-saas:nextcloud-saas /home/nextcloud-saas/.ssh
sudo chmod 700 /home/nextcloud-saas/.ssh
sudo chmod 600 /home/nextcloud-saas/.ssh/authorized_keys
```

Depois, use `nextcloud-saas` como **SSH User** no painel admin.

### Restringir comandos (opcional, avançado)

No servidor Nextcloud, você pode restringir quais comandos podem ser executados com a chave SSH:

Edite `~/.ssh/authorized_keys` e adicione restrições:

```bash
command="docker exec -u 33 nextcloud-docker-app-1 php occ $SSH_ORIGINAL_COMMAND",no-port-forwarding,no-X11-forwarding,no-agent-forwarding ssh-rsa AAAAB3NzaC1...
```

**Atenção**: Isso limita o acesso apenas a comandos `occ`, aumentando a segurança.

## 9. Backup da Chave Privada

⚠️ **IMPORTANTE**: A chave privada é armazenada no banco de dados Laravel. Faça backup regular do banco!

```bash
# Exemplo de backup do PostgreSQL
pg_dump -U postgres -h localhost laravel > backup_$(date +%Y%m%d).sql

# Criptografar o backup (recomendado)
gpg --symmetric --cipher-algo AES256 backup_$(date +%Y%m%d).sql
```

## 10. Rotação de Chaves (recomendado periodicamente)

Para maior segurança, troque as chaves SSH periodicamente (ex: a cada 6 meses):

1. Gerar novo par de chaves
2. Adicionar nova chave pública aos servidores
3. Atualizar instâncias no painel admin com nova chave privada
4. Testar conexões
5. Remover chave antiga dos servidores

## Troubleshooting

### Erro: "Permission denied (publickey)"

```bash
# Verificar permissões no servidor
ls -la ~/.ssh/
# authorized_keys deve ter permissão 600

# Verificar logs do SSH
sudo tail -f /var/log/auth.log
```

### Erro: "Could not find driver"

O phpseclib pode não estar instalado. Execute:

```bash
cd /home/mohr/git/saas-libresign/backend
composer require phpseclib/phpseclib:~3.0
```

### Erro: "docker: command not found"

O usuário SSH não tem Docker instalado ou não está no grupo:

```bash
# Adicionar ao grupo docker
sudo usermod -aG docker ubuntu
```

### Erro: "docker exec: container not found"

Nome do container está errado. Verifique:

```bash
docker ps
```

E atualize no painel admin.

## Exemplo Completo: Setup de Nova Instância

```bash
# 1. No servidor SaaS, gerar chave
ssh-keygen -t ed25519 -f ~/.ssh/nextcloud_prod -N ""

# 2. Copiar para servidor Nextcloud
ssh-copy-id -i ~/.ssh/nextcloud_prod.pub ubuntu@cloud.example.com

# 3. Testar conexão
ssh -i ~/.ssh/nextcloud_prod ubuntu@cloud.example.com "docker exec -u 33 nextcloud-docker-app-1 php occ --version"

# 4. Obter chave privada
cat ~/.ssh/nextcloud_prod

# 5. Cadastrar no painel admin com a chave copiada
```

---

## Recursos Adicionais

- [SSH Key Management Best Practices](https://www.ssh.com/academy/ssh/keygen)
- [Docker Security](https://docs.docker.com/engine/security/)
- [Nextcloud OCC Commands](https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/occ_command.html)

---

**Última atualização**: 2025-11-08
