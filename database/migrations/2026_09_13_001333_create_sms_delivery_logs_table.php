<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Delivery history (Phase L), written by `SmsManager` on every send
     * attempt regardless of outcome. Never stores the message body for
     * OTP sends (`message_type = OTP_VERIFICATION` is enough context) —
     * see `SmsManager::send()`.
     */
    public function up(): void
    {
        Schema::create('sms_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile');
            $table->string('provider');
            $table->string('purpose')->nullable();
            $table->string('message_type');
            $table->string('provider_message_id')->nullable();
            $table->string('status');
            $table->timestamp('queued_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_delivery_logs');
    }
};
