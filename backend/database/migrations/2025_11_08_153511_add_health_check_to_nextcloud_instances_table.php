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
            $table->json('health_check_results')->nullable()->after('version');
            $table->timestamp('last_health_check')->nullable()->after('health_check_results');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nextcloud_instances', function (Blueprint $table) {
            $table->dropColumn(['health_check_results', 'last_health_check']);
        });
    }
};
