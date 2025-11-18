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
            $table->foreignId('nextcloud_instance_id')->nullable()->constrained('nextcloud_instances')->onDelete('set null');
            $table->string('nextcloud_user_id')->nullable();
            $table->enum('nextcloud_status', ['pending', 'creating', 'active', 'failed'])->default('pending');
            $table->text('nextcloud_error')->nullable();
            $table->timestamp('nextcloud_created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['nextcloud_instance_id']);
            $table->dropColumn(['nextcloud_instance_id', 'nextcloud_user_id', 'nextcloud_status', 'nextcloud_error', 'nextcloud_created_at']);
        });
    }
};
