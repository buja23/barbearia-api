<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('subscriptions', 'barbershop_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->foreignId('barbershop_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('barbershops')
                    ->cascadeOnDelete();

                $table->index('barbershop_id');
            });
        }

        // Preenche barbershop_id a partir do plano vinculado
        DB::statement('
            UPDATE subscriptions
            SET barbershop_id = plans.barbershop_id
            FROM plans
            WHERE subscriptions.plan_id = plans.id
              AND plans.barbershop_id IS NOT NULL
              AND subscriptions.barbershop_id IS NULL
        ');
    }

    public function down(): void
    {
        if (Schema::hasColumn('subscriptions', 'barbershop_id')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex(['barbershop_id']);
                $table->dropConstrainedForeignId('barbershop_id');
            });
        }
    }
};
