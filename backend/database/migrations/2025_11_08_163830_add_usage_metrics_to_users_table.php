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
        Schema::table('users', function (Blueprint $table) {
            // Métricas de uso do Nextcloud
            $table->bigInteger('storage_used_bytes')->default(0)->comment('Armazenamento usado em bytes');
            $table->bigInteger('storage_quota_bytes')->nullable()->comment('Quota de armazenamento em bytes');
            $table->integer('total_files')->default(0)->comment('Total de arquivos');
            $table->timestamp('last_login_at')->nullable()->comment('Último login no Nextcloud');
            $table->timestamp('last_activity_at')->nullable()->comment('Última atividade no Nextcloud');
            
            // Métricas do SaaS
            $table->integer('total_documents_signed')->default(0)->comment('Total histórico de documentos assinados');
            $table->timestamp('last_document_signed_at')->nullable()->comment('Última assinatura de documento');
            
            // Controle de sincronização
            $table->timestamp('metrics_synced_at')->nullable()->comment('Última sincronização de métricas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'storage_used_bytes',
                'storage_quota_bytes',
                'total_files',
                'last_login_at',
                'last_activity_at',
                'total_documents_signed',
                'last_document_signed_at',
                'metrics_synced_at',
            ]);
        });
    }
};
