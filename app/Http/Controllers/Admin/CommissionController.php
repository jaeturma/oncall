<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCommissionRequest;
use App\Models\Commission;
use App\Services\CommissionEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Commission::class);

        return view('admin.commissions.index', [
            'commissions' => Commission::query()
                ->with(['sponsor:id,name', 'sponsoredUser:id,name', 'accountType:id,name'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function update(UpdateCommissionRequest $request, Commission $commission, CommissionEngine $engine): RedirectResponse
    {
        if ($request->input('decision') === 'approve') {
            $engine->approve($commission, $request->user());
            $message = 'Commission approved and released to the sponsor wallet.';
        } else {
            $engine->reverse($commission, $request->user(), $request->string('reason')->trim()->value());
            $message = 'Commission reversed.';
        }

        return back()->with('status', $message);
    }
}
