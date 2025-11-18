<?php

namespace App\Console\Commands;

use App\Models\NextcloudInstance;
use App\Services\NextcloudMonitoringService;
use Illuminate\Console\Command;

class MonitorInstances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instances:monitor {--instance= : ID da instância específica para monitorar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Coleta métricas de todas as instâncias Nextcloud ativas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $instanceId = $this->option('instance');

        if ($instanceId) {
            // Monitorar instância específica
            $instance = NextcloudInstance::find($instanceId);
            if (!$instance) {
                $this->error("Instância #{$instanceId} não encontrada");
                return 1;
            }
            
            $this->info("Coletando métricas da instância: {$instance->name}");
            $this->monitorInstance($instance);
        } else {
            // Monitorar todas as instâncias ativas
            $instances = NextcloudInstance::where('status', 'active')->get();
            
            $this->info("Coletando métricas de {$instances->count()} instâncias ativas...");
            
            $bar = $this->output->createProgressBar($instances->count());
            $bar->start();
            
            foreach ($instances as $instance) {
                $this->monitorInstance($instance);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
        }

        $this->info('✓ Monitoramento concluído com sucesso!');
        return 0;
    }

    /**
     * Monitora uma instância específica
     */
    protected function monitorInstance(NextcloudInstance $instance): void
    {
        try {
            $monitoring = new NextcloudMonitoringService($instance);
            $metrics = $monitoring->collectMetrics();
            
            if ($this->option('verbose')) {
                $this->line("  {$instance->name}:");
                $this->line("    Storage: " . $this->formatBytes($metrics['storage']['used']) . " / " . $this->formatBytes($metrics['storage']['total']));
                $this->line("    Users: {$metrics['users']['total']} / {$metrics['users']['max']}");
                $this->line("    CPU: {$metrics['system']['cpu_usage']}%");
                $this->line("    Memory: {$metrics['system']['memory_usage']}%");
            }
        } catch (\Exception $e) {
            $this->error("  Erro ao monitorar {$instance->name}: {$e->getMessage()}");
        }
    }

    /**
     * Formata bytes para formato legível
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
