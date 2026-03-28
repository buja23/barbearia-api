<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'barbershop_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->foreignId('barbershop_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('barbershops')
                    ->cascadeOnDelete();

                $table->index('barbershop_id');
            });
        }

        $globalPlans = DB::table('plans')
            ->whereNull('barbershop_id')
            ->orderBy('id')
            ->get();

        $barbershops = DB::table('barbershops')->select('id')->orderBy('id')->get();

        if ($globalPlans->isEmpty() || $barbershops->isEmpty()) {
            return;
        }

        foreach ($barbershops as $barbershop) {
            foreach ($globalPlans as $plan) {
                $newPlanId = DB::table('plans')->insertGetId([
                    'barbershop_id' => $barbershop->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'cuts_per_month' => $plan->cuts_per_month,
                    'is_active' => $plan->is_active,
                    'created_at' => $plan->created_at,
                    'updated_at' => $plan->updated_at,
                ]);

                DB::table('subscriptions')
                    ->join('users', 'users.id', '=', 'subscriptions.user_id')
                    ->where('subscriptions.plan_id', $plan->id)
                    ->where('users.barbershop_id', $barbershop->id)
                    ->update(['subscriptions.plan_id' => $newPlanId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'barbershop_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropIndex(['barbershop_id']);
                $table->dropConstrainedForeignId('barbershop_id');
            });
        }
    }
};