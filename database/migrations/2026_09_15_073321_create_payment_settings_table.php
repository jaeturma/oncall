<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('payments_enabled')->default(true);
            $table->json('allowed_payment_methods');
            $table->string('active_gateway')->default('MANUAL');
            $table->boolean('sandbox_mode')->default(true);
            $table->decimal('min_transaction_amount', 12, 2)->default(0);
            $table->decimal('max_transaction_amount', 12, 2)->default(500000);
            $table->unsignedInteger('payment_expiry_minutes')->default(60);
            $table->string('receipt_prefix')->default('ONC');
            $table->boolean('manual_payment_enabled')->default(true);
            $table->unsignedSmallInteger('refund_window_days')->default(30);
            $table->unsignedSmallInteger('max_refund_requests_per_payment')->default(3);
            $table->string('platform_fee_type')->default('PERCENTAGE');
            $table->decimal('platform_fee_value', 8, 2)->default(15);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
