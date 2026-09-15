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
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('status')->default('PUBLISHED')->after('comment');
            $table->text('response')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('response');
            $table->timestamp('moderated_at')->nullable()->after('responded_at');

            // Add the replacement index before dropping the old one — MySQL
            // won't drop an index still covering the reviewee_id foreign key
            // if it would leave that column momentarily unindexed.
            $table->index(['reviewee_id', 'status', 'created_at'], 'reviews_reviewee_status_created_idx');
            $table->dropIndex(['reviewee_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['reviewee_id', 'created_at']);
            $table->dropIndex('reviews_reviewee_status_created_idx');
            $table->dropColumn(['status', 'response', 'responded_at', 'moderated_at']);
        });
    }
};
