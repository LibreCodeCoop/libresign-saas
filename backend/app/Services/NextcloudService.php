<?php

namespace App\Services;

use App\Models\NextcloudInstance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class NextcloudService
{
    protected NextcloudInstance $instance;
    protected ?SSH2 $ssh = null;

    public function __construct(NextcloudInstance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Verifica se a instância usa SSH ou API
     */
    protected function usesSSH(): bool
    {
        return $this->instance->management_method === 'ssh';
    }

    /**
     * Executa requisição para a API do Nextcloud
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->instance->url, '/') . '/ocs/v2.php/' . ltrim($endpoint, '/');
        
        $response = Http::withBasicAuth(
            $this->instance->api_username,
            $this->instance->api_password
        )
        ->withHeaders([
            'OCS-APIRequest' => 'true',
            'Accept' => 'application/json',
        ])
        ->$method($url, $data);

        if (!$response->successful()) {
            $error = $response->json('ocs.meta.message') ?? $response->body();
            throw new \Exception("API Error: {$error}");
        }

        return $response->json('ocs.data') ?? [];
    }

    /**
     * Conecta via SSH à instância do Nextcloud
     */
    protected function connect(): SSH2
    {
        if ($this->ssh && $this->ssh->isConnected()) {
            return $this->ssh;
        }

        $this->ssh = new SSH2($this->instance->ssh_host, $this->instance->ssh_port);

        // Autenticação
        if ($this->instance->ssh_private_key) {
            $key = PublicKeyLoader::load($this->instance->ssh_private_key);
            if (!$this->ssh->login($this->instance->ssh_user, $key)) {
                throw new \Exception('Falha na autenticação SSH com chave privada');
            }
        } else {
            throw new \Exception('Chave SSH não configurada para esta instância');
        }

        return $this->ssh;
    }

    /**
     * Executa um comando occ no Nextcloud
     */
    protected function executeOcc(string $command, array $args = []): string
    {
        $ssh = $this->connect();
        
        $argString = implode(' ', array_map('escapeshellarg', $args));
        $dockerCommand = sprintf(
            'docker exec -u 33 %s php occ %s %s',
            escapeshellarg($this->instance->docker_container_name),
            $command,
            $argString
        );

        $output = $ssh->exec($dockerCommand);
        
        if ($ssh->getExitStatus() !== 0) {
            Log::error('Erro ao executar comando occ', [
                'command' => $dockerCommand,
                'output' => $output,
                'instance' => $this->instance->name
            ]);
            throw new \Exception("Erro ao executar comando: {$output}");
        }

        return trim($output);
    }

    /**
     * Cria um usuário no Nextcloud
     */
    public function createUser(string $userId, string $displayName, string $email, ?string $password = null): array
    {
        if (!$password) {
            $password = bin2hex(random_bytes(10)); // Gera senha aleatória
        }

        if ($this->usesSSH()) {
            return $this->createUserViaSSH($userId, $displayName, $email, $password);
        } else {
            return $this->createUserViaAPI($userId, $displayName, $email, $password);
        }
    }

    /**
     * Cria usuário via SSH/OCC
     */
    protected function createUserViaSSH(string $userId, string $displayName, string $email, string $password): array
    {
        // Executa comando via variável de ambiente para senha
        $ssh = $this->connect();
        $dockerCommand = sprintf(
            'docker exec -e OC_PASS=%s -u 33 %s php occ user:add --password-from-env --display-name=%s --email=%s %s',
            escapeshellarg($password),
            escapeshellarg($this->instance->docker_container_name),
            escapeshellarg($displayName),
            escapeshellarg($email),
            escapeshellarg($userId)
        );

        $output = $ssh->exec($dockerCommand);

        if ($ssh->getExitStatus() !== 0) {
            throw new \Exception("Erro ao criar usuário: {$output}");
        }

        return [
            'user_id' => $userId,
            'password' => $password,
            'display_name' => $displayName,
            'email' => $email,
            'output' => trim($output)
        ];
    }

    /**
     * Cria usuário via API
     */
    protected function createUserViaAPI(string $userId, string $displayName, string $email, string $password): array
    {
        $data = $this->apiRequest('post', 'cloud/users', [
            'userid' => $userId,
            'password' => $password,
            'displayName' => $displayName,
            'email' => $email,
        ]);

        return [
            'user_id' => $userId,
            'password' => $password,
            'display_name' => $displayName,
            'email' => $email,
            'output' => 'Created via API'
        ];
    }

    /**
     * Deleta um usuário
     */
    public function deleteUser(string $userId): string
    {
        if ($this->usesSSH()) {
            return $this->executeOcc('user:delete', [$userId]);
        } else {
            $this->apiRequest('delete', "cloud/users/{$userId}");
            return "User {$userId} deleted";
        }
    }

    /**
     * Cria um grupo
     */
    public function createGroup(string $groupId): string
    {
        if ($this->usesSSH()) {
            return $this->executeOcc('group:add', [$groupId]);
        } else {
            $this->apiRequest('post', 'cloud/groups', ['groupid' => $groupId]);
            return "Group {$groupId} created";
        }
    }

    /**
     * Deleta um grupo
     */
    public function deleteGroup(string $groupId): string
    {
        if ($this->usesSSH()) {
            return $this->executeOcc('group:delete', [$groupId]);
        } else {
            $this->apiRequest('delete', "cloud/groups/{$groupId}");
            return "Group {$groupId} deleted";
        }
    }

    /**
     * Adiciona usuário a um grupo
     */
    public function addUserToGroup(string $userId, string $groupId): string
    {
        if ($this->usesSSH()) {
            return $this->executeOcc('group:adduser', [$groupId, $userId]);
        } else {
            $this->apiRequest('post', "cloud/users/{$userId}/groups", ['groupid' => $groupId]);
            return "User {$userId} added to group {$groupId}";
        }
    }

    /**
     * Remove usuário de um grupo
     */
    public function removeUserFromGroup(string $userId, string $groupId): string
    {
        if ($this->usesSSH()) {
            return $this->executeOcc('group:removeuser', [$groupId, $userId]);
        } else {
            $this->apiRequest('delete', "cloud/users/{$userId}/groups", ['groupid' => $groupId]);
            return "User {$userId} removed from group {$groupId}";
        }
    }

    /**
     * Lista grupos do usuário
     */
    public function listUserGroups(string $userId): array
    {
        $output = $this->executeOcc('user:info', [$userId]);
        
        // Parse do output para extrair grupos
        preg_match('/- groups:\s*\n((?:\s+-\s+.+\n?)*)/m', $output, $matches);
        
        if (isset($matches[1])) {
            $groups = [];
            preg_match_all('/- (.+)/', $matches[1], $groupMatches);
            return $groupMatches[1] ?? [];
        }
        
        return [];
    }

    /**
     * Define quota para um usuário
     */
    public function setUserQuota(string $userId, string $quota): string
    {
        // Exemplo: "5GB", "1TB", "unlimited"
        if ($this->usesSSH()) {
            return $this->setUserSetting($userId, 'files', 'quota', $quota);
        } else {
            $this->apiRequest('put', "cloud/users/{$userId}", ['key' => 'quota', 'value' => $quota]);
            return "Quota set to {$quota} for user {$userId}";
        }
    }

    /**
     * Define quota para um grupo
     */
    public function setGroupQuota(string $groupId, string $quota): string
    {
        // Exemplo: "5GB", "1TB", "unlimited"
        return $this->executeOcc('group:set-quota', [$groupId, $quota]);
    }

    /**
     * Define configuração do usuário
     */
    public function setUserSetting(string $userId, string $app, string $key, string $value): string
    {
        return $this->executeOcc('user:setting', [$userId, $app, $key, $value]);
    }

    /**
     * Obtém configuração do usuário
     */
    public function getUserSetting(string $userId, string $app, string $key): string
    {
        return $this->executeOcc('user:setting', [$userId, $app, $key]);
    }

    /**
     * Promove usuário a sub-admin de um grupo
     */
    public function addSubAdmin(string $userId, string $groupId): string
    {
        return $this->executeOcc('group:add-subadmin', [$userId, $groupId]);
    }

    /**
     * Remove sub-admin
     */
    public function removeSubAdmin(string $userId, string $groupId): string
    {
        return $this->executeOcc('group:remove-subadmin', [$userId, $groupId]);
    }

    /**
     * Lista todos os usuários
     */
    public function listUsers(): array
    {
        if ($this->usesSSH()) {
            $output = $this->executeOcc('user:list', ['--output=json']);
            return json_decode($output, true) ?? [];
        } else {
            $data = $this->apiRequest('get', 'cloud/users');
            return $data['users'] ?? [];
        }
    }

    /**
     * Lista todos os grupos
     */
    public function listGroups(): array
    {
        if ($this->usesSSH()) {
            $output = $this->executeOcc('group:list', ['--output=json']);
            return json_decode($output, true) ?? [];
        } else {
            $data = $this->apiRequest('get', 'cloud/groups');
            return $data['groups'] ?? [];
        }
    }

    /**
     * Obtém informações do usuário
     */
    public function getUserInfo(string $userId): string
    {
        return $this->executeOcc('user:info', [$userId]);
    }

    /**
     * Obtém último acesso do usuário
     */
    public function getUserLastSeen(string $userId): string
    {
        return $this->executeOcc('user:lastseen', [$userId]);
    }

    /**
     * Reenvia email de boas-vindas
     */
    public function resendWelcomeEmail(string $userId): string
    {
        return $this->executeOcc('user:resetpassword', [$userId, '--send-email']);
    }

    /**
     * Habilita aplicativo
     */
    public function enableApp(string $appId): string
    {
        return $this->executeOcc('app:enable', [$appId]);
    }

    /**
     * Desabilita aplicativo
     */
    public function disableApp(string $appId): string
    {
        return $this->executeOcc('app:disable', [$appId]);
    }

    /**
     * Lista aplicativos instalados
     */
    public function listApps(): array
    {
        $output = $this->executeOcc('app:list', ['--output=json']);
        return json_decode($output, true) ?? [];
    }

    /**
     * Executa manutenção do Nextcloud
     */
    public function runMaintenance(): string
    {
        return $this->executeOcc('maintenance:repair');
    }

    /**
     * Define modo de manutenção
     */
    public function setMaintenanceMode(bool $enabled): string
    {
        return $this->executeOcc('maintenance:mode', ['--' . ($enabled ? 'on' : 'off')]);
    }

    /**
     * Testa conexão SSH ou API
     */
    public function testConnection(): bool
    {
        try {
            if ($this->usesSSH()) {
                $ssh = $this->connect();
                $result = $ssh->exec('echo "test"');
                return trim($result) === 'test';
            } else {
                // Testa API fazendo requisição simples
                $this->apiRequest('get', 'cloud/users?limit=1');
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Falha no teste de conexão', [
                'instance' => $this->instance->name,
                'method' => $this->instance->management_method,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Desconecta SSH
     */
    public function disconnect(): void
    {
        if ($this->ssh && $this->ssh->isConnected()) {
            $this->ssh->disconnect();
        }
    }

    /**
     * Destrutor - garante que a conexão seja fechada
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
