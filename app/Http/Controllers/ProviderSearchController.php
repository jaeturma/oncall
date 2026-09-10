<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SearchProvidersRequest;
use App\Models\Municipality;
use App\Models\Province;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\ProviderSearchService;
use Illuminate\View\View;

class ProviderSearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SearchProvidersRequest $request, ProviderSearchService $providerSearch): View
    {
        $filters = $request->validated();

        return view('search.index', [
            'providers' => $providerSearch->search($filters, $request->user()),
            'canRevealIdentity' => $request->user()?->role === UserRole::ServiceFinder && $request->user()->isIdentityVerified(),
            'filters' => $filters,
            'categories' => ServiceCategory::query()->where('active', true)->with(['services' => fn ($query) => $query->where('active', true)->orderBy('name')])->orderBy('name')->get(),
            'services' => Service::query()->where('active', true)->orderBy('name')->get(),
            'provinces' => Province::query()->orderBy('name')->get(),
            'municipalities' => Municipality::query()->where('province_id', $filters['province_id'])->orderBy('name')->get(),
        ]);
    }
}
