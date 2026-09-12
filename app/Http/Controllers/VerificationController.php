<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use App\Http\Requests\StoreProviderDocumentRequest;
use App\Models\ProviderDocument;
use App\Services\MobileVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class VerificationController extends Controller
{
    public function index(Request $request, MobileVerificationService $mobileVerification): View
    {
        return view('verification.index', [
            'documentTypes' => DocumentType::cases(),
            'documents' => $request->user()->providerDocuments()->latest()->get(),
            'hasPendingMobileCode' => $mobileVerification->hasPendingCode($request->user()),
        ]);
    }

    public function store(StoreProviderDocumentRequest $request): RedirectResponse
    {
        $path = $request->file('document')->store('verification-documents/'.$request->user()->id, 'local');

        try {
            DB::transaction(function () use ($request, $path): void {
                $request->user()->providerDocuments()->create([
                    'document_type' => $request->enum('document_type', DocumentType::class),
                    'private_path' => $path,
                    'status' => VerificationStatus::Submitted,
                ]);
                $request->user()->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Submitted]);
                $request->user()->update(['identity_verification_status' => VerificationStatus::Submitted]);
                $request->user()->providerProfile?->update(['verification_status' => VerificationStatus::Submitted]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return redirect()->route('verification.index')->with('status', 'Your document was submitted for private admin review.');
    }

    public function download(Request $request, ProviderDocument $providerDocument): StreamedResponse
    {
        abort_unless($providerDocument->user_id === $request->user()->id || $request->user()->canAccessAdmin(), 404);

        return Storage::disk('local')->download($providerDocument->private_path);
    }
}
