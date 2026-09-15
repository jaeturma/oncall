<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\LocationSetting;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LocationDiagnosticsController extends Controller
{
    public function index(): View
    {
        Gate::authorize('view-location-diagnostics');
        $settings = LocationSetting::current();
        $staleBefore = now()->subDays($settings->location_freshness_days);

        return view('admin.location-diagnostics', [
            'settings' => $settings,
            'providersMissingCoordinates' => ProviderProfile::query()->whereNull('latitude')->orWhereNull('longitude')->count(),
            'providersWithStaleLocation' => ProviderProfile::query()->whereNotNull('location_updated_at')->where('location_updated_at', '<', $staleBefore)->count(),
            'municipalitiesMissingCentroid' => Municipality::query()->whereNull('latitude')->orWhereNull('longitude')->count(),
            'barangaysMissingCentroid' => Barangay::query()->whereNull('latitude')->orWhereNull('longitude')->count(),
            'totalProviders' => ProviderProfile::query()->count(),
            'totalBarangays' => Barangay::query()->count(),
        ]);
    }
}
