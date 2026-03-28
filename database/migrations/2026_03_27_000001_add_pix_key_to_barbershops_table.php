<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->string('pix_key')->nullable()->after('address');
            $table->string('pix_key_type')->nullable()->after('pix_key');
        });
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropColumn(['pix_key', 'pix_key_type']);
        });
    }
};