<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ProviderReputationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Rebuilds every provider's rating_cached/reviews_count/reputation_score
 * from authoritative Review rows (Phase P §59). Never modifies review rows
 * themselves — only the denormalized aggregate caches.
 */
#[Signature('reputation:recalculate')]
#[Description('Rebuild provider rating/reputation aggregates from authoritative review records')]
class RecalculateReputation extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ProviderReputationService $reputation): int
    {
        $providers = User::query()->where('role', UserRole::ServiceProvider)->get();
        $bar = $this->output->createProgressBar($providers->count());

        foreach ($providers as $provider) {
            $reputation->recalculate($provider);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recalculated reputation for {$providers->count()} providers.");

        return self::SUCCESS;
    }
}
