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
        Schema::create('nextcloud_instances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('ssh_host');
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_user');
            $table->text('ssh_private_key')->nullable();
            $table->string('docker_container_name')->default('nextcloud-docker-app-1');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->integer('max_users')->default(100);
            $table->integer('current_users')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nextcloud_instances');
    }
};
