<?php

namespace App\Services;

use App\Models\NextcloudInstance;
use Illuminate\Support\Facades\Log;

class NextcloudMonitoringService
{
    protected NextcloudInstance $instance;
    protected NextcloudService $nc;

    public function __construct(NextcloudInstance $instance)
    {
        $this->instance = $instance;
        $this->nc = new NextcloudService($instance);
    }

    /**
     * Coleta todas as métricas da instância
     */
    public function collectMetrics(): array
    {
        $metrics = [
            'storage' => $this->getStorageMetrics(),
            'users' => $this->getUserMetrics(),
            'apps' => $this->getAppMetrics(),
            'system' => $this->getSystemMetrics(),
        ];

        // Atualiza a instância com as métricas coletadas
        $this->updateInstanceMetrics($metrics);

        return $metrics;
    }

    /**
     * Obtém métricas de storage
     */
    protected function getStorageMetrics(): array
    {
        try {
            if ($this->instance->management_method === 'ssh') {
                $ssh = $this->nc->connect();
                
                // Obtém uso de disco do container
                $dockerCommand = sprintf(
                    'docker exec %s df -h /var/www/html/data | tail -1',
                    escapeshellarg($this->instance->docker_container_name)
                );
                
                $output = trim($ssh->exec($dockerCommand));
                $parts = preg_split('/\s+/', $output);
                
                // Formato: Filesystem Size Used Avail Use% Mounted
                return [
                    'total' => $this->parseSize($parts[1] ?? '0'),
                    'used' => $this->parseSize($parts[2] ?? '0'),
                    'available' => $this->parseSize($parts[3] ?? '0'),
                    'usage_percentage' => intval($parts[4] ?? 0),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erro ao coletar métricas de storage', [
                'instance' => $this->instance->name,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'total' => $this->instance->storage_allocated ?? 0,
            'used' => $this->instance->storage_used ?? 0,
            'available' => 0,
            'usage_percentage' => 0,
        ];
    }

    /**
     * Obtém métricas de usuários
     */
    protected function getUserMetrics(): array
    {
        try {
            $users = $this->nc->listUsers();
            
            return [
                'total' => count($users),
                'active' => $this->instance->active_users ?? 0,
                'max' => $this->instance->max_users ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao coletar métricas de usuários', [
                'instance' => $this->instance->name,
                'error' => $e->getMessage()
            ]);
            
            return [
                'total' => $this->instance->current_users ?? 0,
                'active' => $this->instance->active_users ?? 0,
                'max' => $this->instance->max_users ?? 0,
            ];
        }
    }

    /**
     * Obtém métricas de aplicativos
     */
    protected function getAppMetrics(): array
    {
        try {
            $apps = $this->nc->listApps();
            
            return [
                'enabled' => count($apps['enabled'] ?? []),
                'disabled' => count($apps['disabled'] ?? []),
                'total' => count($apps['enabled'] ?? []) + count($apps['disabled'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao coletar métricas de apps', [
                'instance' => $this->instance->name,
                'error' => $e->getMessage()
            ]);
            
            return [
                'enabled' => 0,
                'disabled' => 0,
                'total' => 0,
            ];
        }
    }

    /**
     * Obtém métricas do sistema (CPU, memória, etc)
     */
    protected function getSystemMetrics(): array
    {
        try {
            if ($this->instance->management_method === 'ssh') {
                $ssh = $this->nc->connect();
                
                // CPU usage
                $cpuCommand = sprintf(
                    'docker stats %s --no-stream --format "{{.CPUPerc}}"',
                    escapeshellarg($this->instance->docker_container_name)
                );
                $cpuOutput = trim($ssh->exec($cpuCommand));
                $cpuUsage = floatval(str_replace('%', '', $cpuOutput));
                
                // Memory usage
                $memCommand = sprintf(
                    'docker stats %s --no-stream --format "{{.MemPerc}}"',
                    escapeshellarg($this->instance->docker_container_name)
                );
                $memOutput = trim($ssh->exec($memCommand));
                $memUsage = floatval(str_replace('%', '', $memOutput));
                
                return [
                    'cpu_usage' => $cpuUsage,
                    'memory_usage' => $memUsage,
                    'disk_io' => 0, // Requires additional monitoring
                    'network_throughput' => 0, // Requires additional monitoring
                ];
            }
        } catch (\Exception $e) {
            Log::error('Erro ao coletar métricas do sistema', [
                'instance' => $this->instance->name,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'cpu_usage' => $this->instance->cpu_usage ?? 0,
            'memory_usage' => $this->instance->memory_usage ?? 0,
            'disk_io' => $this->instance->disk_io ?? 0,
            'network_throughput' => $this->instance->network_throughput ?? 0,
        ];
    }

    /**
     * Atualiza métricas na instância
     */
    protected function updateInstanceMetrics(array $metrics): void
    {
        $storage = $metrics['storage'];
        $users = $metrics['users'];
        $system = $metrics['system'];

        $this->instance->update([
            'storage_used' => $storage['used'],
            'storage_allocated' => $storage['total'],
            'current_users' => $users['total'],
            'active_users' => $users['active'],
            'cpu_usage' => $system['cpu_usage'],
            'memory_usage' => $system['memory_usage'],
            'disk_io' => $system['disk_io'],
            'network_throughput' => $system['network_throughput'],
        ]);

        // Atualiza históricos
        $this->updateHistoricalMetrics($metrics);
        
        // Verifica alertas
        $this->checkAlerts($metrics);
    }

    /**
     * Atualiza métricas históricas
     */
    protected function updateHistoricalMetrics(array $metrics): void
    {
        $now = now()->toIso8601String();
        
        // Storage growth
        $storageGrowth = $this->instance->storage_growth ?? [];
        $storageGrowth[] = [
            'timestamp' => $now,
            'value' => $metrics['storage']['used'],
        ];
        // Mantém últimos 100 pontos
        $storageGrowth = array_slice($storageGrowth, -100);
        
        // User activity
        $userActivity = $this->instance->user_activity ?? [];
        $userActivity[] = [
            'timestamp' => $now,
            'total' => $metrics['users']['total'],
            'active' => $metrics['users']['active'],
        ];
        $userActivity = array_slice($userActivity, -100);
        
        $this->instance->update([
            'storage_growth' => $storageGrowth,
            'user_activity' => $userActivity,
        ]);
    }

    /**
     * Verifica e gera alertas
     */
    protected function checkAlerts(array $metrics): void
    {
        $alerts = [];
        
        // Alerta de storage
        $storagePercentage = $metrics['storage']['usage_percentage'];
        if ($storagePercentage >= 90) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'storage',
                'message' => "Storage crítico: {$storagePercentage}% usado",
            ];
        } elseif ($storagePercentage >= 80) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'storage',
                'message' => "Storage alto: {$storagePercentage}% usado",
            ];
        }
        
        // Alerta de CPU
        if ($metrics['system']['cpu_usage'] >= 90) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'cpu',
                'message' => "CPU crítica: {$metrics['system']['cpu_usage']}%",
            ];
        }
        
        // Alerta de memória
        if ($metrics['system']['memory_usage'] >= 90) {
            $alerts[] = [
                'level' => 'critical',
                'type' => 'memory',
                'message' => "Memória crítica: {$metrics['system']['memory_usage']}%",
            ];
        }
        
        // Alerta de usuários
        $userPercentage = ($metrics['users']['total'] / $metrics['users']['max']) * 100;
        if ($userPercentage >= 95) {
            $alerts[] = [
                'level' => 'warning',
                'type' => 'users',
                'message' => "Capacidade de usuários quase atingida: {$userPercentage}%",
            ];
        }
        
        $this->instance->update(['alerts' => $alerts]);
    }

    /**
     * Converte string de tamanho (ex: "5.2G", "100M") para bytes
     */
    protected function parseSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = floatval(substr($size, 0, -1));
        
        $units = [
            'K' => 1024,
            'M' => 1024 ** 2,
            'G' => 1024 ** 3,
            'T' => 1024 ** 4,
        ];
        
        return isset($units[$unit]) ? intval($value * $units[$unit]) : intval($value);
    }
}
