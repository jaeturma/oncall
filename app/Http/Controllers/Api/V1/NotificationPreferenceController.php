<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\NotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use App\Services\Notifications\NotificationCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Marketplace-facing notification preferences (Phase M Step 21). A category
 * with `has_mandatory_events: true` still delivers those specific events
 * regardless of what the user sets here — see
 * NotificationPreferenceService — the toggle only ever governs the
 * optional events within that category.
 */
class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $existing = NotificationPreference::query()->where('user_id', $request->user()->id)->get()->keyBy(fn (NotificationPreference $p) => $p->category->value);
        $mandatoryByCategory = $this->mandatoryByCategory();

        $data = collect(NotificationCategory::cases())->map(function (NotificationCategory $category) use ($existing, $mandatoryByCategory): array {
            $row = $existing->get($category->value);

            return [
                'category' => $category->value,
                'push_enabled' => $row->push_enabled ?? true,
                'sms_enabled' => $row->sms_enabled ?? true,
                'has_mandatory_events' => $mandatoryByCategory[$category->value] ?? false,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        foreach ($request->input('preferences') as $preference) {
            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'category' => $preference['category']],
                array_filter([
                    'push_enabled' => $preference['push_enabled'] ?? null,
                    'sms_enabled' => $preference['sms_enabled'] ?? null,
                ], fn (mixed $value): bool => $value !== null),
            );
        }

        return response()->json(['message' => 'Notification preferences updated.']);
    }

    /** @return array<string, bool> */
    private function mandatoryByCategory(): array
    {
        $result = [];
        foreach (NotificationCatalog::events() as $event) {
            $category = $event['category']->value;
            $result[$category] = ($result[$category] ?? false) || $event['mandatory'];
        }

        return $result;
    }
}
