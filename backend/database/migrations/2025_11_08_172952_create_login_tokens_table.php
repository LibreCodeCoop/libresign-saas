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
        Schema::create('login_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token', 64)->unique(); // Token único
            $table->timestamp('expires_at'); // Expiração (5 minutos)
            $table->boolean('used')->default(false); // Se foi usado
            $table->timestamp('used_at')->nullable(); // Quando foi usado
            $table->string('ip_address', 45)->nullable(); // IP do cliente
            $table->text('user_agent')->nullable(); // User agent
            $table->timestamps();
            
            $table->index(['token', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_tokens');
    }
};
