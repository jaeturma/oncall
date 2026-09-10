<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('REQUESTED')->index();
            $table->string('payout_method');
            $table->string('payout_reference');
            $table->foreignId('hold_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('accounting_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accounting_reviewed_at')->nullable();
            $table->foreignId('budget_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('budget_approved_at')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disbursed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
