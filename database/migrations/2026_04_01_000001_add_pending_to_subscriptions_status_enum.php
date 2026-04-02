<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adiciona 'pending' ao enum status da tabela subscriptions.
     *
     * PostgreSQL não suporta ALTER COLUMN ... TYPE enum diretamente.
     * A solução segura é: remover a constraint antiga e criar uma nova.
     *
     * MySQL: o Laravel trata enum como string com CHECK constraint ou como tipo nativo,
     * portanto usamos DB::statement para cobrir ambos os drivers.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Remove a constraint de CHECK criada pelo Laravel para o enum
            DB::statement("
                ALTER TABLE subscriptions
                DROP CONSTRAINT IF EXISTS subscriptions_status_check
            ");

            // Recria com os 4 valores permitidos
            DB::statement("
                ALTER TABLE subscriptions
                ADD CONSTRAINT subscriptions_status_check
                CHECK (status IN ('active', 'pending', 'expired', 'canceled'))
            ");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("
                ALTER TABLE subscriptions
                MODIFY COLUMN status ENUM('active', 'pending', 'expired', 'canceled')
                NOT NULL DEFAULT 'active'
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("
                ALTER TABLE subscriptions
                DROP CONSTRAINT IF EXISTS subscriptions_status_check
            ");

            DB::statement("
                ALTER TABLE subscriptions
                ADD CONSTRAINT subscriptions_status_check
                CHECK (status IN ('active', 'expired', 'canceled'))
            ");
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("
                ALTER TABLE subscriptions
                MODIFY COLUMN status ENUM('active', 'expired', 'canceled')
                NOT NULL DEFAULT 'active'
            ");
        }
    }
};
