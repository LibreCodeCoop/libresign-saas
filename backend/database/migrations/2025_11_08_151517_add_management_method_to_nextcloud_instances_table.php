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
            $table->enum('management_method', ['ssh', 'api'])->default('ssh')->after('docker_container_name');
            $table->string('api_username')->nullable()->after('management_method');
            $table->string('api_password')->nullable()->after('api_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->dropColumn(['management_method', 'api_username', 'api_password']);
        });
    }
};
