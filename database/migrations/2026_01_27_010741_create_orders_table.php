<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela de Pedidos/Vendas
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_amount', 10, 2); // Valor Total
            $table->string('status')->default('pending'); // pending, approved (pago), cancelled
            
            // Dados do Pagamento (Igual ao Agendamento)
            $table->string('payment_id')->nullable(); // ID do Mercado Pago
            $table->text('pix_copy_paste')->nullable();
            $table->text('qr_code_base64')->nullable();
            
            $table->timestamps();
        });

        // Itens da Venda (Qual produto e quanto custou na época)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Preço de Venda
            $table->decimal('cost_price', 10, 2); // Preço de Custo (Para saber o lucro exato)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};