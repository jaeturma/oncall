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
        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->text('experience_text')->nullable();
            $table->string('rate_type')->nullable();
            $table->decimal('rate_from', 12, 2)->nullable();
            $table->decimal('rate_to', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['provider_profile_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
