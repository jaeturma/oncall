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
        Schema::create('review_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('reviews_enabled')->default(true);
            $table->unsignedSmallInteger('review_window_days')->default(30);
            $table->boolean('comment_required')->default(false);
            $table->unsignedSmallInteger('max_comment_length')->default(2000);
            $table->boolean('provider_response_enabled')->default(true);
            $table->unsignedSmallInteger('response_max_length')->default(1000);
            $table->unsignedSmallInteger('reviews_per_page')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_settings');
    }
};
