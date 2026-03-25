<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->foreignId('saas_plan_id')
                ->nullable()
                ->after('id')
                ->constrained('saas_plans')
                ->nullOnDelete();

            $table->string('saas_payment_id')->nullable()->after('subscription_expires_at');
            $table->text('saas_pix_copy_paste')->nullable()->after('saas_payment_id');
            $table->longText('saas_pix_qr_code')->nullable()->after('saas_pix_copy_paste');
        });
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saas_plan_id');
            $table->dropColumn(['saas_payment_id', 'saas_pix_copy_paste', 'saas_pix_qr_code']);
        });
    }
};
