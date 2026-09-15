<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLocationSettingsRequest;
use App\Models\AuditLog;
use App\Models\LocationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LocationSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-location-settings');

        return view('admin.settings.location', ['settings' => LocationSetting::current()]);
    }

    public function update(UpdateLocationSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('manage-location-settings');
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $settings = LocationSetting::current();
            $before = $settings->toArray();

            $settings->update([
                'maps_enabled' => $request->boolean('maps_enabled'),
                'active_map_provider' => $data['active_map_provider'],
                'geocoding_enabled' => $request->boolean('geocoding_enabled'),
                'geocoding_base_url' => $data['geocoding_base_url'] ?? null,
                'default_search_radius_km' => $data['default_search_radius_km'],
                'max_search_radius_km' => $data['max_search_radius_km'],
                'allowed_radius_choices' => $request->radiusChoices(),
                'default_country' => strtoupper($data['default_country']),
                'location_freshness_days' => $data['location_freshness_days'],
                'provider_location_policy' => $data['provider_location_policy'],
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'event' => 'location_settings.updated',
                'subject_type' => LocationSetting::class,
                'subject_id' => $settings->id,
                'before_json' => $before,
                'after_json' => $settings->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.settings.location.edit')->with('status', 'Location settings saved.');
    }
}
