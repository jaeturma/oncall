<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OncallEvent;

/**
 * Thin wrapper so call sites don't repeat the OncallEvent shape. In-app
 * (database) only for now; a queued mail/SMS channel can be added later without
 * touching callers.
 */
class Notifier
{
    public function push(?User $user, string $key, string $title, string $body, ?string $url = null): void
    {
        $user?->notify(new OncallEvent($key, $title, $body, $url));
    }
}
