<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Municipality;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Services\DistanceEstimator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderController extends Controller
{
    /**
     * Public provider/service profile.
     *
     * Guests and unverified users see an anonymized profile. Only a verified
     * Service Finder sees the provider's real name; direct contact details are
     * never sent to the browser here for anyone.
     */
    public function __invoke(Request $request, ProviderProfile $providerProfile, DistanceEstimator $distanceEstimator): View
    {
        $isRequestable = $providerProfile->isRequestable();
        $providerProfile->unsetRelation('user');
        abort_unless($isRequestable, 404);

        $viewer = $request->user();
        $reveal = $viewer?->role === UserRole::ServiceFinder && $viewer->isIdentityVerified();

        $providerProfile->load([
            'province:id,name',
            'municipality:id,province_id,name,latitude,longitude',
            'providerServices' => fn ($query) => $query->where('active', true)
                ->with(['service:id,name,active,service_category_id', 'service.category:id,name']),
        ]);

        $distanceKm = $this->distanceFromSearchOrigin($request, $providerProfile, $distanceEstimator);

        if ($reveal) {
            $providerProfile->load('user:id,name');
        }

        $reviews = Review::query()
            ->where('reviewee_id', $providerProfile->user_id)
            ->whereNotNull('comment')
            ->with('reviewer:id,name')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('providers.show', [
            'profile' => $providerProfile,
            'reveal' => $reveal,
            'canRequest' => $reveal && $viewer->can('create', ServiceRequest::class),
            'reviews' => $reviews,
            'reviewsCount' => (int) ($providerProfile->user()->value('reviews_count') ?? 0),
            'distanceKm' => $distanceKm,
        ]);
    }

    /**
     * Approximate distance from wherever the visitor searched from, carried
     * over via the search result link. Centroid-based, never live GPS.
     */
    private function distanceFromSearchOrigin(Request $request, ProviderProfile $providerProfile, DistanceEstimator $distanceEstimator): ?float
    {
        if ($providerProfile->municipality->latitude === null || $providerProfile->municipality->longitude === null) {
            return null;
        }

        $municipality = $request->filled('from_municipality_id')
            ? Municipality::query()->find($request->integer('from_municipality_id'), ['latitude', 'longitude'])
            : null;

        $origin = $municipality ?? ($request->filled('from_province_id')
            ? Province::query()->find($request->integer('from_province_id'), ['latitude', 'longitude'])
            : null);

        if (! $origin || $origin->latitude === null || $origin->longitude === null) {
            return null;
        }

        return $distanceEstimator->kilometersBetween(
            (float) $origin->latitude,
            (float) $origin->longitude,
            (float) $providerProfile->municipality->latitude,
            (float) $providerProfile->municipality->longitude,
        );
    }
}
