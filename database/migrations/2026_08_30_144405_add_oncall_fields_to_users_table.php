<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('role')->default('SERVICE_FINDER')->index();
            $table->string('status')->default('ACTIVE')->index();
            $table->string('identity_verification_status')->default('PENDING')->index();
            $table->timestamp('phone_verified_at')->nullable();
            $table->foreignId('sponsor_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_user_id');
            $table->dropColumn(['phone', 'role', 'status', 'identity_verification_status', 'phone_verified_at']);
        });
    }
};
