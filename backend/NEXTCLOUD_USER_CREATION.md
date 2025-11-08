# Sistema de Criação de Usuários Nextcloud

## Visão Geral

Quando um usuário se registra no SaaS LibreSign, automaticamente é criada uma conta para ele em uma instância do Nextcloud. Este processo acontece de forma **assíncrona** para não bloquear o registro.

## Métodos de Gerenciamento

O sistema suporta dois métodos de gerenciamento de instâncias:

- **SSH + OCC**: Executa comandos occ diretamente via SSH (mais completo)
- **API HTTP**: Usa a Provisioning API do Nextcloud (mais simples)

O método é selecionado automaticamente baseado na configuração da instância (`management_method`).

## Fluxo de Criação

```
1. Usuário se registra no site
   ↓
2. Conta criada no banco de dados (status: pending)
   ↓
3. Job CreateNextcloudUser é adicionado à fila
   ↓
4. Worker processa o job em background
   ↓
5. Usuário criado no Nextcloud (status: active)
```

## Estados do Usuário

O campo `nextcloud_status` pode ter os seguintes valores:

- **pending**: Aguardando criação no Nextcloud
- **creating**: Job está sendo processado
- **active**: Usuário criado com sucesso
- **failed**: Falha na criação (após 3 tentativas)

## Seleção de Instância

O sistema seleciona automaticamente a instância com:
- Status `active`
- Capacidade disponível (`current_users < max_users`)
- Menor número de usuários (balanceamento de carga)

## Quotas por Plano

| Plano          | Quota     |
|----------------|-----------|
| Trial          | 5 GB      |
| Básico         | 10 GB     |
| Profissional   | 50 GB     |
| Empresarial    | 200 GB    |

## Retry e Tratamento de Erros

- **Tentativas**: 3 tentativas automáticas
- **Backoff**: 1 min, 5 min, 10 min entre tentativas
- **Erro permanente**: Status muda para `failed` e erro é registrado

## Executando o Worker

### Desenvolvimento (Foreground)

```bash
php artisan queue:work --tries=3 --backoff=60,300,600
```

### Produção (Supervisor)

Adicione ao `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/para/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
```

Depois execute:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Monitoramento

### Ver Jobs na Fila

```bash
php artisan queue:monitor
```

### Ver Logs

```bash
tail -f storage/logs/laravel.log | grep "Nextcloud"
```

### Verificar Status dos Usuários

```sql
SELECT 
    id, 
    email, 
    nextcloud_status, 
    nextcloud_created_at,
    nextcloud_error
FROM users
WHERE nextcloud_status != 'active';
```

## Re-processar Usuários com Falha

Caso queira reprocessar usuários que falharam:

```bash
php artisan tinker
```

```php
User::where('nextcloud_status', 'failed')->each(function($user) {
    $user->update(['nextcloud_status' => 'pending', 'nextcloud_error' => null]);
    \App\Jobs\CreateNextcloudUser::dispatch($user);
});
```

## Criação Manual

Se necessário criar manualmente:

```bash
php artisan tinker
```

```php
$user = User::find(1);
\App\Jobs\CreateNextcloudUser::dispatch($user);
```

## Testando

Para testar sem instância real:

```php
// No Job, adicione um check no início:
if (app()->environment('local') && !config('services.nextcloud.enabled')) {
    $this->user->update(['nextcloud_status' => 'active']);
    return;
}
```

## Troubleshooting

### Job não está sendo processado

1. Verifique se o worker está rodando: `ps aux | grep "queue:work"`
2. Verifique se há instâncias ativas: `SELECT * FROM nextcloud_instances WHERE status = 'active';`
3. Verifique os logs: `tail -f storage/logs/laravel.log`

### Falhas constantes

1. Teste a conexão SSH manualmente
2. Verifique permissões do usuário SSH
3. Execute health check na instância: `POST /api/admin/instances/{id}/health-check`

### Job travado

```bash
php artisan queue:restart
```
