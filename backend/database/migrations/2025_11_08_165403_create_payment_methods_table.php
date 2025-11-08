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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do método (ex: "Cartão de Crédito Stripe")
            $table->string('type'); // Tipo: credit_card, pix, boleto, paypal, etc
            $table->text('api_key')->nullable(); // Chave pública/API key
            $table->text('api_secret')->nullable(); // Chave secreta
            $table->string('webhook_url')->nullable(); // URL para receber webhooks
            $table->json('config')->nullable(); // Configurações adicionais específicas do gateway
            $table->boolean('is_available')->default(false); // Disponível para usuários
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
