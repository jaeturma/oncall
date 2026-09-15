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
        Schema::table('provider_profiles', function (Blueprint $table) {
            // Bayesian-adjusted ranking-only score (Phase P §32/§33) — never
            // displayed to users, who always see the honest `rating_cached`
            // arithmetic average. Consumed only by ProviderSearchService.
            $table->decimal('reputation_score', 4, 3)->nullable()->after('rating_cached');
            // Denormalized alongside rating_cached (which already duplicates
            // User.rating_cached for marketplace-resource independence) so
            // ProviderProfileResource can embed a reputation summary without
            // an extra User query per search result.
            $table->unsignedInteger('reviews_count')->default(0)->after('completed_jobs_cached');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->dropColumn(['reputation_score', 'reviews_count']);
        });
    }
};
