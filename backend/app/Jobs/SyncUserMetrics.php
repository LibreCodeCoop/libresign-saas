<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NextcloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncUserMetrics implements ShouldQueue
{
    use Queueable;

    public $tries = 2;
    public $backoff = [300];

    public function __construct(
        public User $user
    ) {}

    public function handle(): void
    {
        if (!$this->user->nextcloud_user_id || !$this->user->nextcloud_instance_id) {
            return;
        }

        try {
            $instance = $this->user->nextcloudInstance;
            if (!$instance) {
                return;
            }

            $nc = new NextcloudService($instance);
            $userInfo = $this->parseUserInfo($nc->getUserInfo($this->user->nextcloud_user_id));
            
            try {
                $lastSeen = $nc->getUserLastSeen($this->user->nextcloud_user_id);
                $lastLogin = $this->parseLastSeen($lastSeen);
            } catch (\Exception $e) {
                $lastLogin = null;
            }

            $this->user->update([
                'storage_used_bytes' => $userInfo['storage_used'] ?? 0,
                'storage_quota_bytes' => $userInfo['storage_quota'] ?? null,
                'last_login_at' => $lastLogin,
                'last_activity_at' => $lastLogin,
                'metrics_synced_at' => now(),
            ]);

            Log::info("Métricas sincronizadas", [
                'user_id' => $this->user->id,
                'storage_used_mb' => round(($userInfo['storage_used'] ?? 0) / 1024 / 1024, 2),
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao sincronizar métricas: {$e->getMessage()}", [
                'user_id' => $this->user->id,
            ]);
            throw $e;
        }
    }

    private function parseUserInfo(string $output): array
    {
        $data = [];
        if (preg_match('/quota:\s*(.+)/i', $output, $matches)) {
            $quotaStr = trim($matches[1]);
            if ($quotaStr !== 'none' && $quotaStr !== 'unlimited') {
                $data['storage_quota'] = $this->parseSize($quotaStr);
            }
        }
        return $data;
    }

    private function parseLastSeen(string $output): ?string
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $output, $matches)) {
            try {
                return \Carbon\Carbon::parse($matches[1])->toDateTimeString();
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    private function parseSize(string $size): int
    {
        $size = strtoupper(trim($size));
        preg_match('/^([0-9.]+)\s*([KMGT]?B?)$/i', $size, $matches);
        
        if (!$matches) {
            return 0;
        }
        
        $value = (float) $matches[1];
        $unit = $matches[2] ?? '';
        
        $multipliers = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
        ];
        
        return (int) ($value * ($multipliers[$unit] ?? 1));
    }
}
