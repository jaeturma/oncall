<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
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

    public function barangays(Municipality $municipality): JsonResponse
    {
        return response()->json($municipality->barangays()->orderBy('name')->get(['id', 'name']));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        $data = $request->validate([
            'province' => ['required', 'max:255'],
            'municipality' => ['required', 'max:255'],
            'type' => ['required', 'in:municipality,city'],
            'barangay' => ['nullable', 'max:255'],
        ]);
        $province = Province::firstOrCreate(['name' => $data['province']]);
        $municipality = Municipality::firstOrCreate(['province_id' => $province->id, 'name' => $data['municipality']], ['type' => $data['type']]);

        if (filled($data['barangay'] ?? null)) {
            Barangay::firstOrCreate(['municipality_id' => $municipality->id, 'name' => $data['barangay']]);
        }

        return back();
    }
}
