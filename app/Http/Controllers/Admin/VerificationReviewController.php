<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewVerificationRequest;
use App\Models\ProviderDocument;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerificationReviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canAccessAdmin(), 403);

        return view('admin.verifications.index', [
            'documents' => ProviderDocument::query()->with('user')->where('status', VerificationStatus::Submitted)->oldest()->paginate(20),
        ]);
    }

    public function update(ReviewVerificationRequest $request, ProviderDocument $providerDocument, Notifier $notifier): RedirectResponse
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

        $notifier->push(
            $providerDocument->user,
            'verification.reviewed',
            'Identity verification '.($status === VerificationStatus::Verified ? 'approved' : str($status->value)->lower()),
            $status === VerificationStatus::Verified
                ? 'Your identity is verified. A badge now appears on your profile.'
                : 'Your latest document was not approved. Review the notes and submit again.',
            route('verification.index'),
        );

        return back()->with('status', 'Verification review saved.');
    }
}
