<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ✅ Adiciona campos de segurança (CPF, Telefone) para usuários
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Adiciona campos se não existirem
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->text('cpf')->nullable()->comment('CPF encriptado (Laravel model cast)');
            }
            
            if (!Schema::hasColumn('users', 'phone')) {
                $table->text('phone')->nullable()->comment('Telefone encriptado (Laravel model cast)');
            }

            // Indices para melhor performance
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->comment('Último login');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove as colunas se existirem
            if (Schema::hasColumn('users', 'cpf')) {
                $table->dropColumn('cpf');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });
    }
};
