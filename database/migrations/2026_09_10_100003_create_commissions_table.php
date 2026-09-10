<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sponsored_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('account_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('PENDING');
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['sponsored_user_id', 'trigger']);
            $table->index(['sponsor_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
