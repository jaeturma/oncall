<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 80);
            $table->string('channel', 20);
            $table->string('provider', 40)->nullable();
            $table->foreignId('device_id')->nullable()->constrained('device_tokens')->nullOnDelete();
            $table->string('status', 20);
            $table->string('provider_message_id')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_code', 60)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel']);
            $table->index(['status']);
            $table->index(['event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_delivery_logs');
    }
};
