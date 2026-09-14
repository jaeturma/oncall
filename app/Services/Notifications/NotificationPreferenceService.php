<?php

namespace App\Services\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;

/**
 * Whether a given channel is allowed for a given event and user (Phase M
 * Step 21). A mandatory event never consults this at all — see call sites
 * in NotificationDispatcher — so there is no preference row that can
 * suppress a security-critical notification.
 */
class NotificationPreferenceService
{
    public function pushAllowed(User $user, string $eventKey): bool
    {
        if (NotificationCatalog::isMandatory($eventKey)) {
            return true;
        }

        return $this->preference($user, $eventKey)?->push_enabled ?? true;
    }

    public function smsAllowed(User $user, string $eventKey): bool
    {
        if (NotificationCatalog::isMandatory($eventKey)) {
            return true;
        }

        return $this->preference($user, $eventKey)?->sms_enabled ?? true;
    }

    private function preference(User $user, string $eventKey): ?NotificationPreference
    {
        $category = NotificationCatalog::category($eventKey);

        return NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('category', $category)
            ->first();
    }
}
