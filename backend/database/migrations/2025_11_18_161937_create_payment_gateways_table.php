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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do gateway (ex: Sicoob PIX, Sicoob Boleto, Stripe)
            $table->string('slug')->unique(); // Identificador único (ex: sicoob_pix, sicoob_boleto)
            $table->string('type'); // Tipo: pix, boleto, credit_card, stripe, etc
            $table->text('description')->nullable(); // Descrição
            $table->boolean('is_active')->default(false); // Ativo/inativo
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->json('settings')->nullable(); // Configurações específicas (API keys, certificados, etc)
            $table->json('metadata')->nullable(); // Metadados adicionais
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
