<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\NextcloudInstance;
use App\Models\Plan;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateNextcloudUser implements ShouldQueue
{
    use Queueable;

    public $tries = 3; // Tentar 3 vezes
    public $backoff = [60, 300, 600]; // Esperar 1min, 5min, 10min entre tentativas

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Iniciando criação de usuário Nextcloud para: {$this->user->email}");

        try {
            // Atualiza status para 'creating'
            $this->user->update(['nextcloud_status' => 'creating']);

            // Busca instância disponível (com menos usuários ou primeira ativa)
            $instance = $this->getAvailableInstance();

            if (!$instance) {
                throw new \Exception('Nenhuma instância Nextcloud disponível');
            }

            // Gera senha aleatória segura
            $password = Str::random(16) . Str::upper(Str::random(2)) . rand(10, 99) . '!@';

            // Cria usuário no Nextcloud
            $nc = new NextcloudService($instance);
            
            // Cria o usuário (user_id baseado no email)
            $nextcloudUserId = $this->generateNextcloudUserId($this->user->email);
            $nc->createUser($nextcloudUserId, $this->user->name, $this->user->email, $password);

            // Define quota baseado no plano do usuário
            $quota = $this->getQuotaForUser($this->user);
            if ($quota) {
                $nc->setUserQuota($nextcloudUserId, $quota);
            }

            // Cria grupo pessoal do usuário (baseado no email)
            $personalGroupId = $this->generatePersonalGroupId($this->user->email);
            try {
                $nc->createGroup($personalGroupId);
                Log::info("Grupo pessoal criado: {$personalGroupId}");
            } catch (\Exception $e) {
                // Grupo pode já existir, não é erro crítico
                Log::warning("Erro ao criar grupo pessoal: {$e->getMessage()}");
            }

            // Adiciona usuário ao seu grupo pessoal
            try {
                $nc->addUserToGroup($nextcloudUserId, $personalGroupId);
                Log::info("Usuário adicionado ao grupo pessoal: {$personalGroupId}");
            } catch (\Exception $e) {
                Log::warning("Não foi possível adicionar ao grupo pessoal: {$e->getMessage()}");
            }

            // Envia email de boas-vindas
            try {
                $nc->resendWelcomeEmail($nextcloudUserId);
            } catch (\Exception $e) {
                Log::warning("Não foi possível enviar email de boas-vindas: {$e->getMessage()}");
            }

            // Atualiza usuário com sucesso
            $this->user->update([
                'nextcloud_instance_id' => $instance->id,
                'nextcloud_user_id' => $nextcloudUserId,
                'nextcloud_status' => 'active',
                'nextcloud_error' => null,
                'nextcloud_created_at' => now(),
                'platform_url' => $instance->url, // URL da instância onde foi criado
            ]);

            // Atualiza contador de usuários da instância
            $instance->increment('current_users');

            Log::info("Usuário Nextcloud criado com sucesso: {$nextcloudUserId}");

        } catch (\Exception $e) {
            Log::error("Erro ao criar usuário Nextcloud: {$e->getMessage()}", [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'trace' => $e->getTraceAsString()
            ]);

            // Atualiza status para 'failed'
            $this->user->update([
                'nextcloud_status' => 'failed',
                'nextcloud_error' => $e->getMessage(),
            ]);

            // Re-lança exceção para retry
            throw $e;
        }
    }

    /**
     * Busca instância disponível
     */
    private function getAvailableInstance(): ?NextcloudInstance
    {
        return NextcloudInstance::where('status', 'active')
            ->whereColumn('current_users', '<', 'max_users')
            ->orderBy('current_users', 'asc')
            ->first();
    }

    /**
     * Gera ID de usuário Nextcloud baseado no email
     */
    private function generateNextcloudUserId(string $email): string
    {
        // Remove domínio e caracteres especiais
        $userId = Str::before($email, '@');
        $userId = Str::slug($userId, '_');
        
        // Adiciona timestamp se necessário para garantir unicidade
        $userId = $userId . '_' . substr(md5($email), 0, 6);
        
        return $userId;
    }

    /**
     * Gera ID de grupo pessoal baseado no email do usuário
     */
    private function generatePersonalGroupId(string $email): string
    {
        // Usa o email completo como nome do grupo
        return $email;
    }

    /**
     * Retorna quota baseado no plano do usuário
     */
    private function getQuotaForUser(User $user): ?string
    {
        // Se o usuário tem um plano associado, usa o storage_limit do plano
        if ($user->plan_id && $user->plan) {
            $storageGB = $user->plan->storage_limit;
            return $storageGB ? "{$storageGB}GB" : null;
        }
        
        // Fallback para quota padrão
        return '5GB';
    }

    /**
     * O que fazer quando o job falhar após todas as tentativas
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job falhou após todas as tentativas: {$exception->getMessage()}", [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
        ]);

        // Poderia enviar notificação para admin aqui
    }
}
