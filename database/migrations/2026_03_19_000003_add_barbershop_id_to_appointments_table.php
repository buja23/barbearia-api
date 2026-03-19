<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Appointments precisam de barbershop_id direto para que o Filament
     * multi-tenancy faça o scoping automático por tenant.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'barbershop_id')) {
                $table->foreignId('barbershop_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('barbershops')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'barbershop_id')) {
                $table->dropForeign(['barbershop_id']);
                $table->dropColumn('barbershop_id');
            }
        });
    }
};
