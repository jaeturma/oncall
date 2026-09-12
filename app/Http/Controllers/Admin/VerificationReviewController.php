<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewVerificationRequest;
use App\Models\AuditLog;
use App\Models\ProviderDocument;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerificationReviewController extends Controller
{
    /**
     * Statuses a reviewer may act on. Submitted is the normal review queue;
     * Verified is included so a verifier can revoke a previously-approved
     * document (e.g. re-marking it Expired). Rejected/Expired are terminal —
     * the user resubmits a new document rather than an existing one being
     * reopened.
     */
    private const ACTIONABLE_STATUSES = [VerificationStatus::Submitted, VerificationStatus::Verified];

    public function index(Request $request): View
    {
        abort_unless($request->user()->canAccessAdmin(), 403);
        $status = VerificationStatus::tryFrom($request->string('status')->value()) ?? VerificationStatus::Submitted;

        return view('admin.verifications.index', [
            'documents' => ProviderDocument::query()->with('user')->where('status', $status)->oldest()->paginate(20),
            'status' => $status,
        ]);
    }

    public function update(ReviewVerificationRequest $request, ProviderDocument $providerDocument, Notifier $notifier): RedirectResponse
    {
        abort_unless(in_array($providerDocument->status, self::ACTIONABLE_STATUSES, true), 422);
        $previousStatus = $providerDocument->status;
        $status = $request->enum('status', VerificationStatus::class);

        DB::transaction(function () use ($request, $providerDocument, $previousStatus, $status): void {
            $review = ['status' => $status, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'notes' => $request->string('notes')->trim()->value() ?: null];
            $providerDocument->update([...$review, 'expires_at' => $request->date('expires_at')]);
            $providerDocument->user->verificationRecords()->where('status', $previousStatus)->latest()->first()?->update($review);
            $providerDocument->user->update(['identity_verification_status' => $status]);
            $providerDocument->user->providerProfile?->update(['verification_status' => $status]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'event' => $previousStatus === VerificationStatus::Verified ? 'verification.revoked' : 'verification.reviewed',
                'subject_type' => ProviderDocument::class,
                'subject_id' => $providerDocument->id,
                'before_json' => ['document_type' => $providerDocument->document_type->value, 'status' => $previousStatus->value],
                'after_json' => ['document_type' => $providerDocument->document_type->value, 'status' => $status->value, 'notes' => $review['notes']],
            ]);
        });

        $wasRevoked = $previousStatus === VerificationStatus::Verified && $status !== VerificationStatus::Verified;
        $notifier->push(
            $providerDocument->user,
            'verification.reviewed',
            'Identity verification '.($status === VerificationStatus::Verified ? 'approved' : ($wasRevoked ? 'revoked' : str($status->value)->lower())),
            match (true) {
                $status === VerificationStatus::Verified => 'Your identity is verified. A badge now appears on your profile.',
                $wasRevoked => 'Your identity verification was revoked. Review the notes and submit a new document.',
                default => 'Your latest document was not approved. Review the notes and submit again.',
            },
            route('verification.index'),
        );

        return back()->with('status', 'Verification review saved.');
    }
}
