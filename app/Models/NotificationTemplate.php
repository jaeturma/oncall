<?php

namespace App\Models;

use App\Services\Notifications\NotificationTemplateService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-editable override for one catalog event key (Phase M Step 24). A
 * missing row, a disabled row, or a row with a blank field all fall back to
 * the safe application default in `config('notifications.events')` — see
 * {@see NotificationTemplateService} — so a
 * broken template can never silently kill every notification for an event
 * (Step 25).
 */
#[Fillable(['event_key', 'enabled', 'push_title', 'push_body', 'database_title', 'database_body'])]
class NotificationTemplate extends Model
{
    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }
}
