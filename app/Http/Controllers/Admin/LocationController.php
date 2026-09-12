<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function municipalities(Province $province): JsonResponse
    {
        return response()->json($province->municipalities()->orderBy('name')->get(['id', 'name', 'type']));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        $data = $request->validate(['province' => ['required', 'max:255'], 'municipality' => ['required', 'max:255'], 'type' => ['required', 'in:municipality,city']]);
        $province = Province::firstOrCreate(['name' => $data['province']]);
        Municipality::firstOrCreate(['province_id' => $province->id, 'name' => $data['municipality']], ['type' => $data['type']]);

        return back();
    }
}
