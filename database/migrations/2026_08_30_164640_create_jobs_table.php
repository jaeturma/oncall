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
        Schema::rename('jobs', 'queue_jobs');

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('service_finder_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained('users')->restrictOnDelete();
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->string('status')->default('ACCEPTED');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('on_the_way_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['service_finder_id', 'status']);
            $table->index(['provider_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::rename('queue_jobs', 'jobs');
    }
};
