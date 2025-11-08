<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NextcloudInstance extends Model
{
    protected $fillable = [
        'name',
        'url',
        'version',
        'health_check_results',
        'last_health_check',
        'ssh_host',
        'ssh_port',
        'ssh_user',
        'ssh_private_key',
        'docker_container_name',
        'management_method',
        'api_username',
        'api_password',
        'status',
        'max_users',
        'current_users',
        'notes',
    ];

    protected $casts = [
        'ssh_port' => 'integer',
        'max_users' => 'integer',
        'current_users' => 'integer',
        'health_check_results' => 'array',
        'last_health_check' => 'datetime',
    ];

    protected $hidden = [
        'ssh_private_key',
        'api_password',
    ];

    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->current_users < $this->max_users;
    }

    public function hasCapacity(): bool
    {
        return $this->current_users < $this->max_users;
    }

    /**
     * Busca e atualiza a versão do Nextcloud
     */
    public function fetchVersion(): ?string
    {
        try {
            $statusUrl = rtrim($this->url, '/') . '/status.php';
            
            $ch = curl_init($statusUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desenvolvimento
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if (isset($data['version'])) {
                    $this->version = $data['version'];
                    $this->save();
                    return $data['version'];
                }
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('Erro ao buscar versão do Nextcloud', [
                'instance' => $this->name,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Executa testes de saúde e permissões da instância
     */
    public function runHealthCheck(): array
    {
        $results = [
            'timestamp' => now()->toIso8601String(),
            'tests' => []
        ];

        // Teste 1: Conexão
        $connectionTest = $this->testConnection();
        $results['tests'][] = $connectionTest;

        // Se conexão falhar, não continuar
        if (!$connectionTest['success']) {
            $this->health_check_results = $results;
            $this->last_health_check = now();
            $this->save();
            return $results;
        }

        // Teste 2: Criar usuário de teste
        $testUserId = 'healthcheck_' . time();
        $createUserTest = $this->testCreateUser($testUserId);
        $results['tests'][] = $createUserTest;

        // Teste 3: Listar usuários
        $listUsersTest = $this->testListUsers();
        $results['tests'][] = $listUsersTest;

        // Teste 4: Criar grupo
        $testGroupId = 'healthcheck_group_' . time();
        $createGroupTest = $this->testCreateGroup($testGroupId);
        $results['tests'][] = $createGroupTest;

        // Teste 5: Adicionar usuário ao grupo (se ambos foram criados)
        if ($createUserTest['success'] && $createGroupTest['success']) {
            $addToGroupTest = $this->testAddUserToGroup($testUserId, $testGroupId);
            $results['tests'][] = $addToGroupTest;
        }

        // Limpeza: Deletar usuário de teste
        if ($createUserTest['success']) {
            $deleteUserTest = $this->testDeleteUser($testUserId);
            $results['tests'][] = $deleteUserTest;
        }

        // Limpeza: Deletar grupo de teste
        if ($createGroupTest['success']) {
            $deleteGroupTest = $this->testDeleteGroup($testGroupId);
            $results['tests'][] = $deleteGroupTest;
        }

        // Salvar resultados
        $this->health_check_results = $results;
        $this->last_health_check = now();
        $this->save();

        return $results;
    }

    private function testConnection(): array
    {
        try {
            if ($this->management_method === 'ssh') {
                $nc = new \App\Services\NextcloudService($this);
                $connected = $nc->testConnection();
                
                return [
                    'name' => 'Conexão SSH',
                    'success' => $connected,
                    'message' => $connected ? 'Conexão SSH estabelecida com sucesso' : 'Falha na conexão SSH'
                ];
            } else {
                // Teste de conexão API
                $statusUrl = rtrim($this->url, '/') . '/status.php';
                $ch = curl_init($statusUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $success = $httpCode === 200;
                return [
                    'name' => 'Conexão API',
                    'success' => $success,
                    'message' => $success ? 'API acessível' : 'API não responde'
                ];
            }
        } catch (\Exception $e) {
            return [
                'name' => 'Conexão',
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ];
        }
    }

    private function testCreateUser(string $userId): array
    {
        try {
            $nc = new \App\Services\NextcloudService($this);
            $nc->createUser($userId, 'Health Check User', 'healthcheck@test.com');
            
            return [
                'name' => 'Criar Usuário',
                'success' => true,
                'message' => 'Permissão para criar usuários confirmada'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Criar Usuário',
                'success' => false,
                'message' => 'Sem permissão: ' . $e->getMessage()
            ];
        }
    }

    private function testListUsers(): array
    {
        try {
            $nc = new \App\Services\NextcloudService($this);
            $nc->listUsers();
            
            return [
                'name' => 'Listar Usuários',
                'success' => true,
                'message' => 'Permissão para listar usuários confirmada'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Listar Usuários',
                'success' => false,
                'message' => 'Sem permissão: ' . $e->getMessage()
            ];
        }
    }

    private function testCreateGroup(string $groupId): array
    {
        try {
            $nc = new \App\Services\NextcloudService($this);
            $nc->createGroup($groupId);
            
            return [
                'name' => 'Criar Grupo',
                'success' => true,
                'message' => 'Permissão para criar grupos confirmada'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Criar Grupo',
                'success' => false,
                'message' => 'Sem permissão: ' . $e->getMessage()
            ];
        }
    }

    private function testAddUserToGroup(string $userId, string $groupId): array
    {
        try {
            $nc = new \App\Services\NextcloudService($this);
            $nc->addUserToGroup($userId, $groupId);
            
            return [
                'name' => 'Adicionar Usuário ao Grupo',
                'success' => true,
                'message' => 'Permissão confirmada'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Adicionar Usuário ao Grupo',
                'success' => false,
                'message' => 'Sem permissão: ' . $e->getMessage()
            ];
        }
    }

    private function testDeleteUser(string $userId): array
    {
        try {
            $nc = new \App\Services\NextcloudService($this);
            $nc->deleteUser($userId);
            
            return [
                'name' => 'Deletar Usuário',
                'success' => true,
                'message' => 'Permissão para deletar usuários confirmada'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Deletar Usuário',
                'success' => false,
                'message' => 'Sem permissão: ' . $e->getMessage()
            ];
        }
    }

    private function testDeleteGroup(string $groupId): array
    {
        try {
            $nc = new \App\Services\NextcloudService($this);
            $nc->deleteGroup($groupId);
            
            return [
                'name' => 'Deletar Grupo',
                'success' => true,
                'message' => 'Permissão para deletar grupos confirmada'
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Deletar Grupo',
                'success' => false,
                'message' => 'Sem permissão: ' . $e->getMessage()
            ];
        }
    }
}
