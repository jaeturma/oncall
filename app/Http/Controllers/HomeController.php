<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\ServiceCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        return view('welcome', [
            'categories' => ServiceCategory::query()->where('active', true)->with(['services' => fn ($query) => $query->where('active', true)->orderBy('name')])->orderBy('sort_order')->orderBy('name')->get(),
            'provinces' => Province::query()->orderBy('name')->get(),
        ]);
    }
}
