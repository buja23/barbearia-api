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
            // Token CSRF temporário usado durante o fluxo OAuth do MercadoPago
            $table->string('mp_oauth_state', 64)->nullable()->after('mp_public_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn('mp_oauth_state');
        });
    }
};
