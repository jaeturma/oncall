<?php

namespace App\Console\Commands;

use App\Services\ReconciliationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs Oncall's internal-consistency payment checks (Phase Q) and upserts
 * ReconciliationFlag rows for the admin queue. Schedulable by ops; not
 * auto-scheduled here to avoid introducing a surprise recurring job.
 */
#[Signature('payments:reconcile')]
#[Description('Flag stale payments, stale attempts, duplicate references, and refund-cache mismatches')]
class ReconcilePayments extends Command
{
    public function handle(ReconciliationService $reconciliation): int
    {
        $flags = $reconciliation->flag();

        $this->info("Reconciliation complete. {$flags->count()} flag(s) open or refreshed.");

        return self::SUCCESS;
    }
}
