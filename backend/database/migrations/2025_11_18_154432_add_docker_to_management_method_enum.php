<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Salva valores atuais
        DB::statement("ALTER TABLE nextcloud_instances ADD COLUMN management_method_temp VARCHAR(10)");
        DB::statement("UPDATE nextcloud_instances SET management_method_temp = management_method::text");
        
        // Remove coluna antiga
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->dropColumn('management_method');
        });
        
        // Recria com novos valores
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->enum('management_method', ['ssh', 'api', 'docker'])->default('ssh')->after('docker_container_name');
        });
        
        // Restaura valores
        DB::statement("UPDATE nextcloud_instances SET management_method = management_method_temp::text");
        DB::statement("ALTER TABLE nextcloud_instances DROP COLUMN management_method_temp");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Similar ao up(), mas remove 'docker' do enum
        DB::statement("ALTER TABLE nextcloud_instances ADD COLUMN management_method_temp VARCHAR(10)");
        DB::statement("UPDATE nextcloud_instances SET management_method_temp = management_method::text");
        
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->dropColumn('management_method');
        });
        
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->enum('management_method', ['ssh', 'api'])->default('ssh')->after('docker_container_name');
        });
        
        DB::statement("UPDATE nextcloud_instances SET management_method = management_method_temp::text");
        DB::statement("ALTER TABLE nextcloud_instances DROP COLUMN management_method_temp");
    }
};
