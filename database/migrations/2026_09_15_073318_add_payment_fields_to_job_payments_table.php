<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_payments', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->unique()->after('status');
            $table->decimal('refunded_amount', 12, 2)->default(0)->after('net_amount');
            $table->string('purpose')->default('SERVICE_TRANSACTION')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('job_payments', function (Blueprint $table) {
            $table->dropColumn(['receipt_number', 'refunded_amount', 'purpose']);
        });
    }
};
