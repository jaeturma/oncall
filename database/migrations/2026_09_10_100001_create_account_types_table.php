<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->string('sponsor_commission_type')->default('NONE');
            $table->decimal('sponsor_commission_value', 12, 2)->default(0);
            $table->boolean('requires_identity_verification')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable()->after('sponsor_user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_type_id');
        });

        Schema::dropIfExists('account_types');
    }
};
