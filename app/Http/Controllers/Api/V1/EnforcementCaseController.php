<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EnforcementCaseResource;
use App\Models\EnforcementCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnforcementCaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cases = EnforcementCase::query()->whereBelongsTo($request->user())->latest('id')->paginate(15);

        return response()->json([
            'data' => EnforcementCaseResource::collection($cases),
            'meta' => ['current_page' => $cases->currentPage(), 'last_page' => $cases->lastPage(), 'total' => $cases->total()],
        ]);
    }

    public function show(EnforcementCase $enforcementCase): EnforcementCaseResource
    {
        Gate::authorize('view', $enforcementCase);

        return new EnforcementCaseResource($enforcementCase->load('relatedReport'));
    }
}
