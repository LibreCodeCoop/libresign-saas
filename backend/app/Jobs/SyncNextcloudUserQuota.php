<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NextcloudService;
use App\Helpers\PlanHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncNextcloudUserQuota implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $newPlanType
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Sincronizando quota Nextcloud para: {$this->user->email}", [
            'old_plan' => $this->user->plan_type,
            'new_plan' => $this->newPlanType,
        ]);

        try {
            // Verifica se usuário tem conta Nextcloud
            if (!$this->user->nextcloud_user_id || !$this->user->nextcloud_instance_id) {
                Log::warning("Usuário não tem conta Nextcloud criada", [
                    'user_id' => $this->user->id,
                ]);
                return;
            }

            // Obtém nova quota baseada no plano
            $newQuota = PlanHelper::getStorage($this->newPlanType);

            // Conecta ao Nextcloud e atualiza quota
            $instance = $this->user->nextcloudInstance;
            if (!$instance) {
                throw new \Exception('Instância Nextcloud não encontrada');
            }

            $nc = new NextcloudService($instance);
            $nc->setUserQuota($this->user->nextcloud_user_id, $newQuota);

            Log::info("Quota atualizada com sucesso", [
                'user_id' => $this->user->id,
                'nextcloud_user_id' => $this->user->nextcloud_user_id,
                'new_quota' => $newQuota,
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao sincronizar quota Nextcloud: {$e->getMessage()}", [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lança para retry
            throw $e;
        }
    }

    /**
     * O que fazer quando o job falhar após todas as tentativas
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de sincronização de quota falhou: {$exception->getMessage()}", [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'new_plan' => $this->newPlanType,
        ]);

        // Poderia notificar admin ou criar tarefa manual
    }
}
