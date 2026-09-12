<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProviderDocumentRequest;
use App\Http\Resources\Api\V1\ProviderDocumentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => ProviderDocumentResource::collection($request->user()->providerDocuments()->latest()->get())]);
    }

    public function store(StoreProviderDocumentRequest $request): JsonResponse
    {
        $path = $request->file('document')->store('verification-documents/'.$request->user()->id, 'local');

        try {
            $document = DB::transaction(function () use ($request, $path) {
                $document = $request->user()->providerDocuments()->create([
                    'document_type' => $request->enum('document_type', DocumentType::class),
                    'private_path' => $path,
                    'status' => VerificationStatus::Submitted,
                ]);
                $request->user()->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Submitted]);
                $request->user()->update(['identity_verification_status' => VerificationStatus::Submitted]);
                $request->user()->providerProfile?->update(['verification_status' => VerificationStatus::Submitted]);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return response()->json(['data' => new ProviderDocumentResource($document)], 201);
    }
}
