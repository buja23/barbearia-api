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
        Schema::table('users', function (Blueprint $table) {
            // Adiciona coluna role se não existir
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'barber', 'client'])->default('client');
            }
            // Adiciona coluna barbershop_id se não existir
            if (!Schema::hasColumn('users', 'barbershop_id')) {
                $table->foreignId('barbershop_id')->nullable()->constrained('barbershops')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'barbershop_id')) {
                $table->dropForeign(['barbershop_id']);
                $table->dropColumn('barbershop_id');
            }
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
