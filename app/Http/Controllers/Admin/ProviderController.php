<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::Admin, 403);
        $verification = VerificationStatus::tryFrom($request->string('verification')->value());
        $providers = ProviderProfile::query()->with(['user:id,name,email,status', 'province:id,name', 'municipality:id,name'])->withCount('providerServices')->when($verification, fn ($query) => $query->where('verification_status', $verification))->latest('id')->paginate(20)->withQueryString();

        return view('admin.providers.index', ['providers' => $providers]);
    }
}
