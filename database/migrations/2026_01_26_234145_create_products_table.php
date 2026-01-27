<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome (Ex: Pomada Matte)
            $table->text('description')->nullable();
            
            // Controle Financeiro
            $table->decimal('cost_price', 10, 2)->default(0); // Preço de Custo (Quanto paguei)
            $table->decimal('sale_price', 10, 2)->default(0); // Preço de Venda (Quanto cobro)
            
            // Controle de Estoque
            $table->integer('quantity')->default(0);      // Quantidade Atual
            $table->integer('min_stock_alert')->default(5); // Alerta de Estoque Baixo
            
            // Tipo: Uso Interno (Gilete) ou Revenda (Pomada)
            $table->enum('type', ['usage', 'resale'])->default('usage'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};