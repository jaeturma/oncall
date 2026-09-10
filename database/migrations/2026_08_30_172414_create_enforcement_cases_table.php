<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('enforcement_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('related_job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->foreignId('related_report_id')->nullable()->constrained('user_reports')->nullOnDelete();
            $table->string('violation_category');
            $table->string('severity')->default('LOW');
            $table->string('status')->default('OPEN');
            $table->string('action')->nullable();
            $table->json('restricted_capabilities')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->string('appeal_status')->default('NONE');
            $table->text('appeal_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enforcement_cases');
    }
};
