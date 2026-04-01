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
        Schema::table('barbershops', function (Blueprint $table) {
            // Token secreto — usado pelo backend para criar pagamentos na conta do barbeiro
            $table->text('mp_access_token')->nullable()->after('pix_key_type');
            // Chave pública — enviada ao app para inicializar o MP Bricks (card form)
            $table->text('mp_public_key')->nullable()->after('mp_access_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn(['mp_access_token', 'mp_public_key']);
        });
    }
};
