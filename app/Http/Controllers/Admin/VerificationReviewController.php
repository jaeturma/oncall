<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewVerificationRequest;
use App\Models\ProviderDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerificationReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::Admin, 403);

        return view('admin.verifications.index', [
            'documents' => ProviderDocument::query()->with('user')->where('status', VerificationStatus::Submitted)->oldest()->paginate(20),
        ]);
    }

    public function update(ReviewVerificationRequest $request, ProviderDocument $providerDocument): RedirectResponse
    {
        abort_unless($providerDocument->status === VerificationStatus::Submitted, 422);
        $status = $request->enum('status', VerificationStatus::class);

        DB::transaction(function () use ($request, $providerDocument, $status): void {
            $review = ['status' => $status, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'notes' => $request->string('notes')->trim()->value() ?: null];
            $providerDocument->update([...$review, 'expires_at' => $request->date('expires_at')]);
            $providerDocument->user->verificationRecords()->where('status', VerificationStatus::Submitted)->latest()->first()?->update($review);
            $providerDocument->user->update(['identity_verification_status' => $status]);
            $providerDocument->user->providerProfile?->update(['verification_status' => $status]);
        });

        return back()->with('status', 'Verification review saved.');
    }
}
