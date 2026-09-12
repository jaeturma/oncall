<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the messaging inbox's unread counts. Null means unread; messages
     * are marked read for whichever participant is NOT the sender when they
     * open the booking page.
     */
    public function up(): void
    {
        Schema::table('job_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('job_messages', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};
