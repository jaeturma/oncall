<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DisputeCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDisputeRequest;
use App\Http\Resources\Api\V1\DisputeResource;
use App\Models\Dispute;
use App\Models\Job;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DisputeController extends Controller
{
    public function store(StoreDisputeRequest $request, Job $job, DisputeService $disputes): JsonResponse
    {
        $dispute = $disputes->open(
            $job,
            $request->user(),
            $request->enum('category', DisputeCategory::class),
            $request->string('description')->trim()->value(),
        );

        return response()->json(['data' => new DisputeResource($dispute)], 201);
    }

    public function withdraw(Request $request, Dispute $dispute, DisputeService $disputes): DisputeResource
    {
        Gate::authorize('withdraw', $dispute);
        $disputes->withdraw($dispute, $request->user());

        return new DisputeResource($dispute->fresh());
    }
}
