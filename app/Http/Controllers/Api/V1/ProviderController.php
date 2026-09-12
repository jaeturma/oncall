<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProviderProfileResource;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Review;
use App\Models\User;
use App\Services\DistanceEstimator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    /**
     * Public provider/service profile. Mirrors the web `ProviderController`:
     * only a verified Service Finder viewer gets the provider's real name;
     * contact details are never sent to any viewer here.
     */
    public function show(Request $request, ProviderProfile $providerProfile, DistanceEstimator $distanceEstimator): JsonResponse
    {
        abort_unless($providerProfile->isRequestable(), 404);

        $viewer = $request->user();
        $reveal = $viewer->role === UserRole::ServiceFinder && $viewer->isIdentityVerified();

        $providerProfile->load([
            'province:id,name',
            'municipality:id,province_id,name,latitude,longitude',
            'providerServices' => fn ($query) => $query->where('active', true)
                ->with(['service:id,name,active,service_category_id', 'service.category:id,name']),
        ]);

        $providerProfile->distance_km = $this->distanceFromSearchOrigin($request, $providerProfile, $distanceEstimator);
        $providerProfile->verified_document_types = ProviderDocument::verifiedTypesByUser([$providerProfile->user_id])->get($providerProfile->user_id, []);
        $contactVerification = User::contactVerificationByIds([$providerProfile->user_id])->get($providerProfile->user_id);
        $providerProfile->mobile_verified = $contactVerification['mobile'] ?? false;
        $providerProfile->email_verified = $contactVerification['email'] ?? false;

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

        return response()->json([
            'data' => new ProviderProfileResource($providerProfile),
            'reviews' => ReviewResource::collection($reviews),
            'reviews_count' => (int) ($providerProfile->user()->value('reviews_count') ?? 0),
        ]);
    }

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
