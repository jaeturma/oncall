<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('raised_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('against_user_id')->constrained('users')->restrictOnDelete();
            $table->string('category');
            $table->text('description');
            $table->string('status')->default('OPEN')->index();
            $table->string('job_prior_status');
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('enforcement_case_id')->nullable()->constrained('enforcement_cases')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('rating_cached', 3, 2)->nullable()->after('identity_verification_status');
            $table->unsignedInteger('reviews_count')->default(0)->after('rating_cached');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating_cached', 'reviews_count']);
        });

        Schema::dropIfExists('disputes');
    }
};
