<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CommissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SponsoredUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    /** Mirrors `SponsorController@index` on the web: sponsorship is single-level, so only direct referrals are shown. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $sponsored = $user->sponsoredUsers()
            ->with('accountType:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        $commissions = $user->commissionsEarned()->get();
        $commissionByUser = $commissions->keyBy('sponsored_user_id');

        return response()->json([
            'data' => $sponsored->getCollection()->map(fn ($sponsoredUser) => new SponsoredUserResource($sponsoredUser, $commissionByUser)),
            'meta' => ['current_page' => $sponsored->currentPage(), 'last_page' => $sponsored->lastPage(), 'total' => $sponsored->total()],
            'totals' => [
                'available' => $commissions->where('status', CommissionStatus::Available)->sum('amount'),
                'pending' => $commissions->whereIn('status', [CommissionStatus::Pending, CommissionStatus::Approved])->sum('amount'),
                'count' => $sponsored->total(),
            ],
        ]);
    }
}
