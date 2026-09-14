<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DevicePlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterDeviceRequest;
use App\Http\Resources\Api\V1\DeviceTokenResource;
use App\Models\DeviceToken;
use App\Services\Notifications\DeviceTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Device-token lifecycle (Phase M Steps 7-9). `register` doubles as the
 * token-refresh endpoint — Flutter calls the same route whether this is a
 * brand-new installation or an existing one whose FCM token just rotated.
 */
class DeviceController extends Controller
{
    public function register(RegisterDeviceRequest $request, DeviceTokenService $devices): JsonResponse
    {
        $device = $devices->register(
            $request->user(),
            $request->string('installation_id')->value(),
            $request->enum('platform', DevicePlatform::class),
            $request->string('fcm_token')->value(),
            $request->string('device_name')->value() ?: null,
            $request->string('app_version')->value() ?: null,
        );

        return response()->json(['data' => new DeviceTokenResource($device)], 201);
    }

    /** Per-device logout/unregister — only ever deactivates the caller's own record. */
    public function destroy(Request $request, DeviceToken $device, DeviceTokenService $devices): JsonResponse
    {
        abort_unless($device->user_id === $request->user()->id, 403);

        $devices->deactivate($device);

        return response()->json(['message' => 'Device unregistered.']);
    }
}
