<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('platform_fee', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->string('status')->default('PENDING')->index();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->foreignId('earning_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('account_types', function (Blueprint $table) {
            $table->decimal('platform_commission_percent', 5, 2)->nullable()->after('sponsor_commission_value');
        });
    }

    public function down(): void
    {
        Schema::table('account_types', function (Blueprint $table) {
            $table->dropColumn('platform_commission_percent');
        });

        Schema::dropIfExists('job_payments');
    }
};
