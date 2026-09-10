<?php

namespace App\Http\Controllers;

use App\Models\EnforcementCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EnforcementCaseController extends Controller
{
    public function index(Request $request): View
    {
        $cases = EnforcementCase::query()->whereBelongsTo($request->user())->latest('id')->paginate(15);

        return view('enforcement.index', ['cases' => $cases]);
    }

    public function show(EnforcementCase $enforcementCase): View
    {
        Gate::authorize('view', $enforcementCase);

        return view('enforcement.show', ['case' => $enforcementCase->load('relatedReport')]);
    }
}
