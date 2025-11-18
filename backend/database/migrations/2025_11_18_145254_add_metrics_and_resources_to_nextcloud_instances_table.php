<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            // Storage metrics
            $table->bigInteger('storage_used')->default(0)->comment('Storage usado em bytes');
            $table->bigInteger('storage_allocated')->default(0)->comment('Storage alocado em bytes');
            
            // Resource metrics
            $table->float('cpu_usage')->default(0)->comment('Uso de CPU em %');
            $table->float('memory_usage')->default(0)->comment('Uso de memória em %');
            $table->bigInteger('memory_allocated')->default(0)->comment('Memória alocada em bytes');
            $table->float('disk_io')->default(0)->comment('Disk I/O em MB/s');
            $table->float('network_throughput')->default(0)->comment('Network throughput em MB/s');
            
            // User metrics
            $table->integer('active_users')->default(0)->comment('Usuários ativos no momento');
            
            // Performance metrics
            $table->json('response_times')->nullable()->comment('Histórico de tempos de resposta');
            $table->json('storage_growth')->nullable()->comment('Histórico de crescimento de storage');
            $table->json('user_activity')->nullable()->comment('Histórico de atividade de usuários');
            
            // Status and management
            $table->string('domain')->nullable()->comment('Domínio da instância');
            $table->string('plan')->default('starter')->comment('Plano contratado');
            $table->timestamp('last_backup')->nullable()->comment('Data do último backup');
            
            // Alertas
            $table->json('alerts')->nullable()->comment('Alertas ativos');
            
            // Configuração de backup
            $table->json('backup_config')->nullable()->comment('Configuração de backup');
            
            // Auto-scaling config
            $table->json('autoscaling_config')->nullable()->comment('Configuração de auto-scaling');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->dropColumn([
                'storage_used',
                'storage_allocated',
                'cpu_usage',
                'memory_usage',
                'memory_allocated',
                'disk_io',
                'network_throughput',
                'active_users',
                'response_times',
                'storage_growth',
                'user_activity',
                'domain',
                'plan',
                'last_backup',
                'alerts',
                'backup_config',
                'autoscaling_config',
            ]);
        });
    }
};
