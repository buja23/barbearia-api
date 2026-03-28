<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->string('saas_last_payment_id')->nullable()->after('saas_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn('saas_last_payment_id');
        });
    }
};