<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user, per-category channel opt-out. Only ever consulted for a category
 * the catalog marks optional for a given event — a mandatory event (Phase M
 * Step 21) never queries this table at all, so there is no row state that
 * can suppress it.
 */
#[Fillable(['user_id', 'category', 'push_enabled', 'sms_enabled'])]
class NotificationPreference extends Model
{
    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'push_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
