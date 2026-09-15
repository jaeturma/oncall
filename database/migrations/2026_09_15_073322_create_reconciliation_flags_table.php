<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_flags', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->foreignId('job_payment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('status')->default('OPEN');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->unique(['category', 'job_payment_id', 'payment_attempt_id'], 'reconciliation_flags_dedupe_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_flags');
    }
};
