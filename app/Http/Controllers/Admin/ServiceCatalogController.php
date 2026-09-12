<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCatalogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canAccessAdmin(), 403);

        return view('admin.catalog', ['categories' => ServiceCategory::with('services')->orderBy('sort_order')->get()]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        ServiceCategory::create($request->validate(['name' => ['required', 'max:255'], 'slug' => ['required', 'alpha_dash', 'unique:service_categories']]));

        return back();
    }

    public function storeService(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        Service::create($request->validate(['service_category_id' => ['required', 'exists:service_categories,id'], 'name' => ['required', 'max:255'], 'slug' => ['required', 'alpha_dash', 'unique:services']]));

        return back();
    }
}
