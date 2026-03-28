<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('barbershop_id');
            }
        });

        $owners = DB::table('barbershops')
            ->select('user_id', DB::raw('MIN(trial_ends_at) as shared_trial_ends_at'))
            ->whereNotNull('user_id')
            ->whereNotNull('trial_ends_at')
            ->groupBy('user_id')
            ->get();

        foreach ($owners as $owner) {
            DB::table('users')
                ->where('id', $owner->user_id)
                ->update(['trial_ends_at' => $owner->shared_trial_ends_at]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'trial_ends_at')) {
                $table->dropColumn('trial_ends_at');
            }
        });
    }
};