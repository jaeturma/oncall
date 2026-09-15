<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\LocationSetting;
use App\Models\Municipality;
use App\Models\Province;
use App\Services\Sms\SmsUrlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Map-provider-independent geocoding (Phase O §19). Reverse-geocoding is
 * always resolved locally against our own {@see Municipality}/{@see Barangay}
 * centroids — small, trusted, always-available reference data, so this is
 * the *authoritative* resolver for province/municipality/barangay and never
 * makes an external call. When an admin has opted into an external provider
 * (`LocationSetting::geocoding_enabled`), a human-readable label is fetched
 * as a display-only enrichment — it is never authoritative for eligibility,
 * matching, or search filtering. Swapping the external provider later means
 * changing {@see requestLabel()} only.
 */
class GeocodingService
{
    public function __construct(private readonly DistanceEstimator $distanceEstimator) {}

    /**
     * @return array{province: ?Province, municipality: ?Municipality, barangay: ?Barangay, label: ?string}
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        $municipality = $this->nearestMunicipality($latitude, $longitude);
        $barangay = $municipality ? $this->nearestBarangay($municipality, $latitude, $longitude) : null;

        return [
            'province' => $municipality?->province,
            'municipality' => $municipality,
            'barangay' => $barangay,
            'label' => $this->requestLabel($latitude, $longitude),
        ];
    }

    private function nearestMunicipality(float $latitude, float $longitude): ?Municipality
    {
        return Municipality::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('province')
            ->get()
            ->sortBy(fn (Municipality $municipality) => $this->distanceEstimator->kilometersBetween(
                $latitude, $longitude, (float) $municipality->latitude, (float) $municipality->longitude,
            ))
            ->first();
    }

    private function nearestBarangay(Municipality $municipality, float $latitude, float $longitude): ?Barangay
    {
        return $municipality->barangays()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->sortBy(fn (Barangay $barangay) => $this->distanceEstimator->kilometersBetween(
                $latitude, $longitude, (float) $barangay->latitude, (float) $barangay->longitude,
            ))
            ->first();
    }

    /**
     * Display-only human-readable label from the admin-configured external
     * geocoder. Returns null on any misconfiguration, disabled setting, or
     * failure — a missing label never blocks the (already-resolved) local
     * match from being used.
     */
    private function requestLabel(float $latitude, float $longitude): ?string
    {
        $settings = LocationSetting::current();

        if (! $settings->geocoding_enabled || blank($settings->geocoding_base_url)) {
            return null;
        }

        $baseUrl = $settings->geocoding_base_url;

        try {
            // Defense in depth — the base URL is also validated when the
            // admin saves it (SafeGeocodingUrl), but re-checked here right
            // before every outbound request, mirroring GenericHttpSmsProvider.
            SmsUrlValidator::assertSafe($baseUrl);

            $response = Http::withHeaders(['User-Agent' => config('app.name').' geocoder (contact: '.config('mail.from.address', 'no-reply@oncall.app').')'])
                ->timeout(5)
                ->connectTimeout(3)
                ->get($baseUrl, ['format' => 'jsonv2', 'lat' => $latitude, 'lon' => $longitude, 'zoom' => 16]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('display_name');
        } catch (InvalidArgumentException) {
            return null;
        } catch (Throwable $exception) {
            Log::warning('Geocoding provider request failed', ['exception' => $exception::class]);

            return null;
        }
    }
}
