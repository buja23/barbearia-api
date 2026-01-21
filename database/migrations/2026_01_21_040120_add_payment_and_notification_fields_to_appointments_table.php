<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // === Módulo Financeiro (Mercado Pago) ===
            
            // ID da transação no Mercado Pago (String porque pode ser longo)
            $table->string('payment_id')->nullable()->index()->after('total_price');
            
            // Status do pagamento: 'pending', 'approved', 'rejected', 'refunded'
            $table->string('payment_status')->default('pending')->after('payment_id');
            
            // Método: 'pix', 'credit_card', 'cash'
            $table->string('payment_method')->default('pix')->after('payment_status');
            
            // O "Hashtag" gigante do Pix Copia e Cola
            $table->text('pix_copy_paste')->nullable()->after('payment_method');
            
            // O Link para ver o QR Code (Ticket URL) - opcional, mas útil
            $table->text('pix_qr_code_url')->nullable()->after('pix_copy_paste');

            // === Módulo Notificações (Controle de Envio) ===
            
            // Flag para saber se o lembrete de 1 hora já foi enviado (Evita duplicidade)
            $table->boolean('reminder_sent')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_id',
                'payment_status',
                'payment_method',
                'pix_copy_paste',
                'pix_qr_code_url',
                'reminder_sent',
            ]);
        });
    }
};