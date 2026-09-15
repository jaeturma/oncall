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
        Schema::create('review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->restrictOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->restrictOnDelete();
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('status')->default('SUBMITTED');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->timestamps();
            $table->unique(['review_id', 'reporter_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
