<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NextcloudInstance;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Dashboard - Métricas globais
     */
    public function dashboard()
    {
        $instances = NextcloudInstance::all();
        $users = User::with(['plan', 'nextcloudInstance'])->get();
        
        // Métricas de instâncias
        $metrics = [
            'total_instances' => $instances->count(),
            'running_instances' => $instances->where('status', 'active')->count(),
            'stopped_instances' => $instances->where('status', 'inactive')->count(),
            'error_instances' => $instances->where('status', 'error')->count(),
            'total_storage_used' => $instances->sum('storage_used'),
            'total_storage_allocated' => $instances->sum('storage_allocated'),
            'avg_cpu_usage' => round($instances->avg('cpu_usage'), 2),
            'avg_memory_usage' => round($instances->avg('memory_usage'), 2),
            'total_active_users' => $instances->sum('active_users'),
        ];
        
        // Métricas de usuários SaaS
        $saasMetrics = [
            'total_users' => $users->count(),
            'active_users' => $users->where('nextcloud_status', 'active')->count(),
            'pending_users' => $users->where('nextcloud_status', 'pending')->count(),
            'failed_users' => $users->where('nextcloud_status', 'failed')->count(),
            'total_storage_used' => $users->sum('storage_used_bytes'),
            'total_storage_quota' => $users->sum('storage_quota_bytes'),
        ];
        
        // Usuários por plano
        $usersByPlan = [];
        foreach (Plan::all() as $plan) {
            $planUsers = $users->where('plan_id', $plan->id);
            $usersByPlan[] = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'plan_slug' => $plan->slug,
                'users_count' => $planUsers->count(),
                'storage_used' => $planUsers->sum('storage_used_bytes'),
                'storage_quota' => $plan->storage_limit * 1024 * 1024 * 1024, // GB to bytes
            ];
        }
        
        // Usuários por instância Nextcloud
        $usersByInstance = [];
        foreach ($instances as $instance) {
            $instanceUsers = $users->where('nextcloud_instance_id', $instance->id);
            $usersByInstance[] = [
                'instance_id' => $instance->id,
                'instance_name' => $instance->name,
                'instance_url' => $instance->url,
                'users_count' => $instanceUsers->count(),
                'storage_used' => $instanceUsers->sum('storage_used_bytes'),
            ];
        }
        
        $metrics = array_merge($metrics, $saasMetrics);

        // Alertas críticos
        $alerts = [];
        foreach ($instances as $instance) {
            if ($instance->alerts && count($instance->alerts) > 0) {
                foreach ($instance->alerts as $alert) {
                    $alerts[] = [
                        'instance_id' => $instance->id,
                        'instance_name' => $instance->name,
                        'alert' => $alert,
                    ];
                }
            }
        }

        // Top 5 instâncias por uso de recursos
        $topCpu = $instances->sortByDesc('cpu_usage')->take(5)->values();
        $topMemory = $instances->sortByDesc('memory_usage')->take(5)->values();
        $topStorage = $instances->sortByDesc(function($instance) {
            return $instance->storage_allocated > 0 
                ? ($instance->storage_used / $instance->storage_allocated) * 100 
                : 0;
        })->take(5)->values();

        return response()->json([
            'metrics' => $metrics,
            'alerts' => $alerts,
            'top_resources' => [
                'cpu' => $topCpu,
                'memory' => $topMemory,
                'storage' => $topStorage,
            ],
            'recent_instances' => $instances->sortByDesc('created_at')->take(5)->values(),
            'users_by_plan' => $usersByPlan,
            'users_by_instance' => $usersByInstance,
            'recent_users' => $users->sortByDesc('created_at')->take(10)->values()->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'plan_name' => $user->plan->name ?? 'N/A',
                    'instance_name' => $user->nextcloudInstance->name ?? 'N/A',
                    'storage_used' => $user->storage_used_bytes,
                    'nextcloud_status' => $user->nextcloud_status,
                    'created_at' => $user->created_at,
                ];
            }),
        ]);
    }

    /**
     * Listar todas as instâncias
     */
    public function index(Request $request)
    {
        $query = NextcloudInstance::query();

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('plan')) {
            $query->where('plan', $request->plan);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('domain', 'like', "%{$request->search}%")
                  ->orWhere('url', 'like', "%{$request->search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $instances = $query->paginate($perPage);

        return response()->json($instances);
    }

    /**
     * Detalhes de uma instância
     */
    public function show($id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        return response()->json($instance);
    }

    /**
     * Criar nova instância
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'domain' => 'nullable|string|max:255',
            'management_method' => 'required|in:ssh,api',
            'max_users' => 'required|integer|min:1',
            'plan' => 'required|in:starter,business,enterprise',
            'storage_allocated' => 'required|integer|min:0',
            'memory_allocated' => 'required|integer|min:0',
            // SSH fields
            'ssh_host' => 'required_if:management_method,ssh',
            'ssh_port' => 'required_if:management_method,ssh|integer',
            'ssh_user' => 'required_if:management_method,ssh',
            'ssh_private_key' => 'required_if:management_method,ssh',
            'docker_container_name' => 'nullable|string',
            // API fields
            'api_username' => 'required_if:management_method,api',
            'api_password' => 'required_if:management_method,api',
        ]);

        $validated['status'] = 'inactive';
        $validated['version'] = '';
        
        $instance = NextcloudInstance::create($validated);

        // Fetch version if possible
        $instance->fetchVersion();

        return response()->json([
            'message' => 'Instância criada com sucesso',
            'instance' => $instance,
        ], 201);
    }

    /**
     * Atualizar instância
     */
    public function update(Request $request, $id)
    {
        $instance = NextcloudInstance::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'domain' => 'nullable|string|max:255',
            'max_users' => 'sometimes|integer|min:1',
            'plan' => 'sometimes|in:starter,business,enterprise',
            'storage_allocated' => 'sometimes|integer|min:0',
            'memory_allocated' => 'sometimes|integer|min:0',
            'notes' => 'nullable|string',
            'backup_config' => 'nullable|array',
            'autoscaling_config' => 'nullable|array',
        ]);

        $instance->update($validated);

        return response()->json([
            'message' => 'Instância atualizada com sucesso',
            'instance' => $instance,
        ]);
    }

    /**
     * Deletar instância
     */
    public function destroy($id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        // Check if any users are using this instance
        $usersCount = User::where('nextcloud_instance_id', $instance->id)->count();
        
        if ($usersCount > 0) {
            return response()->json([
                'message' => "Não é possível deletar esta instância. Existem {$usersCount} usuários associados.",
            ], 422);
        }

        $instance->delete();

        return response()->json([
            'message' => 'Instância deletada com sucesso',
        ]);
    }

    /**
     * Métricas em tempo real de uma instância
     */
    public function metrics($id)
    {
        $instance = NextcloudInstance::findOrFail($id);

        return response()->json([
            'real_time' => [
                'cpu_usage' => $instance->cpu_usage,
                'memory_usage' => $instance->memory_usage,
                'disk_io' => $instance->disk_io,
                'network_throughput' => $instance->network_throughput,
                'active_users' => $instance->active_users,
                'storage_used' => $instance->storage_used,
                'storage_allocated' => $instance->storage_allocated,
                'storage_percentage' => $instance->storage_allocated > 0 
                    ? round(($instance->storage_used / $instance->storage_allocated) * 100, 2)
                    : 0,
            ],
            'historical' => [
                'response_times' => $instance->response_times ?? [],
                'storage_growth' => $instance->storage_growth ?? [],
                'user_activity' => $instance->user_activity ?? [],
            ],
            'alerts' => $instance->alerts ?? [],
        ]);
    }

    /**
     * Logs de uma instância
     */
    public function logs(Request $request, $id)
    {
        $instance = NextcloudInstance::findOrFail($id);

        // TODO: Implementar leitura de logs reais
        // Por enquanto, retorna estrutura mockada
        
        return response()->json([
            'logs' => [
                ['timestamp' => now()->subMinutes(5), 'level' => 'info', 'message' => 'Sistema iniciado'],
                ['timestamp' => now()->subMinutes(3), 'level' => 'warning', 'message' => 'Uso de memória alto: 85%'],
                ['timestamp' => now()->subMinutes(1), 'level' => 'info', 'message' => 'Backup concluído'],
            ],
        ]);
    }

    /**
     * Executar ação em uma instância
     */
    public function action(Request $request, $id)
    {
        $instance = NextcloudInstance::findOrFail($id);

        $validated = $request->validate([
            'action' => 'required|in:start,stop,restart,backup,update',
        ]);

        $action = $validated['action'];

        switch ($action) {
            case 'start':
                $instance->update(['status' => 'active']);
                $message = 'Instância iniciada com sucesso';
                break;
            
            case 'stop':
                $instance->update(['status' => 'inactive']);
                $message = 'Instância parada com sucesso';
                break;
            
            case 'restart':
                $instance->update(['status' => 'active']);
                $message = 'Instância reiniciada com sucesso';
                break;
            
            case 'backup':
                $instance->update(['last_backup' => now()]);
                $message = 'Backup iniciado com sucesso';
                break;
            
            case 'update':
                $instance->fetchVersion();
                $message = 'Atualização iniciada com sucesso';
                break;
            
            default:
                return response()->json(['message' => 'Ação inválida'], 400);
        }

        return response()->json([
            'message' => $message,
            'instance' => $instance->fresh(),
        ]);
    }

    /**
     * Executar ação em lote
     */
    public function batchAction(Request $request)
    {
        $validated = $request->validate([
            'instance_ids' => 'required|array',
            'instance_ids.*' => 'exists:nextcloud_instances,id',
            'action' => 'required|in:start,stop,restart,backup,update',
        ]);

        $instances = NextcloudInstance::whereIn('id', $validated['instance_ids'])->get();
        $action = $validated['action'];
        $results = [];

        foreach ($instances as $instance) {
            try {
                switch ($action) {
                    case 'start':
                        $instance->update(['status' => 'active']);
                        break;
                    case 'stop':
                        $instance->update(['status' => 'inactive']);
                        break;
                    case 'restart':
                        $instance->update(['status' => 'active']);
                        break;
                    case 'backup':
                        $instance->update(['last_backup' => now()]);
                        break;
                    case 'update':
                        $instance->fetchVersion();
                        break;
                }

                $results[] = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'success' => true,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $instance->id,
                    'name' => $instance->name,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => 'Ação em lote executada',
            'results' => $results,
        ]);
    }

    /**
     * Health check de uma instância
     */
    public function healthCheck($id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        $results = $instance->runHealthCheck();

        return response()->json([
            'message' => 'Health check executado',
            'results' => $results,
            'instance' => $instance->fresh(),
        ]);
    }

    /**
     * Estatísticas gerais
     */
    public function stats()
    {
        $instances = NextcloudInstance::all();
        $users = User::all();

        $stats = [
            'instances' => [
                'total' => $instances->count(),
                'by_status' => [
                    'active' => $instances->where('status', 'active')->count(),
                    'inactive' => $instances->where('status', 'inactive')->count(),
                    'error' => $instances->where('status', 'error')->count(),
                    'maintenance' => $instances->where('status', 'maintenance')->count(),
                ],
                'by_plan' => [
                    'starter' => $instances->where('plan', 'starter')->count(),
                    'business' => $instances->where('plan', 'business')->count(),
                    'enterprise' => $instances->where('plan', 'enterprise')->count(),
                ],
            ],
            'users' => [
                'total' => $users->count(),
                'admins' => $users->where('is_admin', true)->count(),
                'by_plan' => [
                    'trial' => $users->where('plan_type', 'trial')->count(),
                    'basic' => $users->where('plan_type', 'basic')->count(),
                    'pro' => $users->where('plan_type', 'pro')->count(),
                ],
            ],
            'resources' => [
                'total_storage_used' => $instances->sum('storage_used'),
                'total_storage_allocated' => $instances->sum('storage_allocated'),
                'avg_cpu' => round($instances->avg('cpu_usage'), 2),
                'avg_memory' => round($instances->avg('memory_usage'), 2),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Coleta métricas de uma instância
     */
    public function collectMetrics($id)
    {
        $instance = NextcloudInstance::findOrFail($id);
        
        try {
            $monitoring = new \App\Services\NextcloudMonitoringService($instance);
            $metrics = $monitoring->collectMetrics();

            return response()->json([
                'message' => 'Métricas coletadas com sucesso',
                'metrics' => $metrics,
                'instance' => $instance->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao coletar métricas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
