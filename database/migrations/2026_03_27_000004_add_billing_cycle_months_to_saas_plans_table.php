<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->unsignedInteger('billing_cycle_months')->default(1)->after('price');
        });

        DB::table('saas_plans')
            ->whereIn('name', ['Starter', 'Basico', 'Básico'])
            ->update(['billing_cycle_months' => 1]);

        DB::table('saas_plans')
            ->where('name', 'Pro')
            ->update(['billing_cycle_months' => 6]);

        DB::table('saas_plans')
            ->where('name', 'Premium')
            ->update(['billing_cycle_months' => 12]);
    }

    public function down(): void
    {
        Schema::table('saas_plans', function (Blueprint $table) {
            $table->dropColumn('billing_cycle_months');
        });
    }
};