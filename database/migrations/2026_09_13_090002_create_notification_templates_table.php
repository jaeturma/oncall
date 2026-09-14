<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->boolean('enabled')->default(true);
            $table->string('push_title', 120)->nullable();
            $table->string('push_body', 240)->nullable();
            $table->string('database_title', 160)->nullable();
            $table->text('database_body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
