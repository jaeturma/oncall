<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\ServiceRequest;
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
    public function __invoke(Request $request, ProviderProfile $providerProfile): View
    {
        $isRequestable = $providerProfile->isRequestable();
        $providerProfile->unsetRelation('user');
        abort_unless($isRequestable, 404);

        $viewer = $request->user();
        $reveal = $viewer?->role === UserRole::ServiceFinder && $viewer->isIdentityVerified();

        $providerProfile->load([
            'province:id,name',
            'municipality:id,province_id,name',
            'providerServices' => fn ($query) => $query->where('active', true)
                ->with(['service:id,name,active,service_category_id', 'service.category:id,name']),
        ]);

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
        ]);
    }
}
