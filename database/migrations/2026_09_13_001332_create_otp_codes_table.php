<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persistent OTP state (Phase L), replacing the earlier cache-only
     * `MobileVerificationService` implementation. A database row (rather
     * than only a cache entry) is what lets policy enforcement (resend
     * cooldown, per-mobile/per-hour caps) look at recent history instead
     * of a single slot, and gives the audit trail something to point at.
     * `otp_hash` only ever stores a hash — the plaintext code exists just
     * long enough to build the outgoing SMS body.
     */
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile');
            $table->string('purpose');
            $table->string('otp_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts');
            $table->timestamp('sent_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->string('request_ip')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
            $table->index(['mobile', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
