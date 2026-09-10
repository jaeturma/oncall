<?php

namespace App\Http\Controllers;

use App\Enums\CommissionStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SponsorController extends Controller
{
    /**
     * Shows only the users this user directly sponsored. Sponsorship is
     * single level, so there is nothing deeper to display.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $sponsored = $user->sponsoredUsers()
            ->with('accountType:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        $commissions = $user->commissionsEarned()->get();

        return view('sponsor.referrals', [
            'sponsored' => $sponsored,
            'commissionByUser' => $user->commissionsEarned()->get()->keyBy('sponsored_user_id'),
            'totals' => [
                'available' => $commissions->where('status', CommissionStatus::Available)->sum('amount'),
                'pending' => $commissions->whereIn('status', [CommissionStatus::Pending, CommissionStatus::Approved])->sum('amount'),
                'count' => $sponsored->total(),
            ],
        ]);
    }
}
