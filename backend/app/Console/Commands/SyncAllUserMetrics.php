<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\SyncUserMetrics;

class SyncAllUserMetrics extends Command
{
    protected $signature = 'users:sync-metrics {--user= : Sync specific user ID}';
    protected $description = 'Sincroniza métricas de uso do Nextcloud para todos os usuários';

    public function handle()
    {
        $userId = $this->option('user');
        
        if ($userId) {
            $users = User::where('id', $userId)
                ->whereNotNull('nextcloud_user_id')
                ->get();
        } else {
            $users = User::whereNotNull('nextcloud_user_id')
                ->where('nextcloud_status', 'active')
                ->get();
        }

        if ($users->isEmpty()) {
            $this->warn('Nenhum usuário com conta Nextcloud ativa encontrado.');
            return 0;
        }

        $this->info("Sincronizando métricas de {$users->count()} usuário(s)...");
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            SyncUserMetrics::dispatch($user);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Jobs de sincronização enfileirados com sucesso!');
        
        return 0;
    }
}
