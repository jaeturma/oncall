@php
    $user = auth()->user();
    $status = $user->identity_verification_status;
    $awaiting = $documents->contains('status', App\Enums\VerificationStatus::Submitted);
    $documentLabels = [
        'NATIONAL_ID' => 'National ID or other government ID',
        'DRIVERS_LICENSE' => "Driver's license",
        'PASSPORT' => 'Passport',
        'PROFESSIONAL_CREDENTIAL' => 'Professional license or credential',
    ];
    $emailVerified = $user->hasVerifiedEmail();
    $mobileVerified = $user->isMobileVerified();
@endphp

<x-layouts.app title="Verification" eyebrow="Trust and safety" description="Verifying your email, mobile number, and identity builds trust with the people you request or receive services from. Only staff-approved identity documents unlock an Identity Verified badge; email and mobile are self-service.">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <div class="grid content-start gap-6">
            {{-- Verification checklist --}}
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="card card-pad">
                    <div class="flex items-start justify-between gap-2">
                        <span class="flex size-9 items-center justify-center rounded-lg {{ $emailVerified ? 'bg-success-50 text-success-700' : 'bg-navy-50 text-navy-800' }}"><x-ui.icon name="envelope" class="size-5" /></span>
                        @if($emailVerified)<x-ui.icon name="check-badge" class="size-5 text-success-600" />@endif
                    </div>
                    <p class="mt-3 font-semibold text-ink">Email</p>
                    <p class="mt-0.5 truncate text-sm text-ink-secondary">{{ $user->email }}</p>
                    @if($emailVerified)
                        <p class="mt-3 text-sm font-medium text-success-700">Verified</p>
                    @else
                        <form method="POST" action="{{ route('verification.send') }}" class="mt-3">
                            @csrf
                            <x-ui.button variant="secondary" size="sm" data-loading-text="Sending…">Resend verification link</x-ui.button>
                        </form>
                    @endif
                </div>

                <div class="card card-pad">
                    <div class="flex items-start justify-between gap-2">
                        <span class="flex size-9 items-center justify-center rounded-lg {{ $mobileVerified ? 'bg-success-50 text-success-700' : 'bg-navy-50 text-navy-800' }}"><x-ui.icon name="phone" class="size-5" /></span>
                        @if($mobileVerified)<x-ui.icon name="check-badge" class="size-5 text-success-600" />@endif
                    </div>
                    <p class="mt-3 font-semibold text-ink">Mobile number</p>
                    <p class="mt-0.5 truncate text-sm text-ink-secondary">{{ $user->phone ?? 'Not added yet' }}</p>
                    @if($mobileVerified)
                        <p class="mt-3 text-sm font-medium text-success-700">Verified</p>
                    @else
                        <details class="mt-3 group" @if($hasPendingMobileCode) open @endif>
                            <summary class="cursor-pointer text-sm font-semibold text-navy-800 hover:underline">{{ $hasPendingMobileCode ? 'Enter your code' : 'Verify a mobile number' }}</summary>
                            <div class="mt-3 grid gap-3">
                                <form method="POST" action="{{ route('verification.mobile.send') }}">
                                    @csrf
                                    <x-form.input name="phone" label="Mobile number" :value="$user->phone" placeholder="09171234567" required />
                                    <x-ui.button variant="secondary" size="sm" class="mt-2" data-loading-text="Sending…">{{ $hasPendingMobileCode ? 'Resend code' : 'Send code' }}</x-ui.button>
                                </form>
                                @if($hasPendingMobileCode)
                                    <form method="POST" action="{{ route('verification.mobile.verify') }}" class="border-t border-line pt-3">
                                        @csrf
                                        <x-form.input name="code" label="6-digit code" inputmode="numeric" maxlength="6" required />
                                        <x-ui.button variant="dark" size="sm" class="mt-2" data-loading-text="Verifying…">Verify code</x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </details>
                    @endif
                </div>

                <div class="card card-pad">
                    <div class="flex items-start justify-between gap-2">
                        <span class="flex size-9 items-center justify-center rounded-lg {{ $status === App\Enums\VerificationStatus::Verified ? 'bg-success-50 text-success-700' : 'bg-navy-50 text-navy-800' }}"><x-ui.icon name="identification" class="size-5" /></span>
                        @if($status === App\Enums\VerificationStatus::Verified)<x-ui.icon name="check-badge" class="size-5 text-success-600" />@endif
                    </div>
                    <p class="mt-3 font-semibold text-ink">Identity</p>
                    <p class="mt-0.5 text-sm text-ink-secondary">Government ID, reviewed by staff</p>
                    <div class="mt-3"><x-ui.status-badge :status="$status" /></div>
                </div>
            </div>

            @if($status === App\Enums\VerificationStatus::Verified)
                <x-ui.alert tone="success" title="Your identity is verified">Your Identity Verified badge is active. You can submit another document (for example a driver's or professional license) to add more verifications.</x-ui.alert>
            @elseif($awaiting)
                <x-ui.alert tone="warning" title="Your document is being reviewed">Oncall staff review submissions privately. You'll get a notification when it's done. You can submit another document after this review is completed.</x-ui.alert>
            @elseif($status === App\Enums\VerificationStatus::Rejected)
                <x-ui.alert tone="danger" title="Your last submission was not approved">Check the review notes below, then upload a clearer or different document.</x-ui.alert>
            @endif

            @unless($awaiting)
                <form class="card card-pad grid gap-5 sm:p-8" method="POST" action="{{ route('verification.documents.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div>
                        <h2 class="h3">Submit an identity document</h2>
                        <p class="mt-1 text-sm text-ink-secondary">Make sure the whole document is visible, in focus, and not expired.</p>
                    </div>
                    <x-form.select name="document_type" label="Document type" placeholder="Select document type" required>
                        @foreach($documentTypes as $documentType)<option value="{{ $documentType->value }}" @selected(old('document_type') === $documentType->value)>{{ $documentLabels[$documentType->value] ?? str($documentType->value)->headline() }}</option>@endforeach
                    </x-form.select>
                    <x-form.file name="document" label="Document file" accept=".jpg,.jpeg,.png,.pdf" required hint="JPG, PNG, or PDF up to 5 MB." />
                    <div><x-ui.button variant="dark" size="lg" data-loading-text="Uploading…">Submit for review</x-ui.button></div>
                </form>
            @endunless

            <section class="card">
                <div class="card-header"><h2 class="h3">Submission history</h2></div>
                @if($documents->isEmpty())
                    <div class="px-5 py-8 text-center sm:px-6"><p class="font-medium text-ink">No documents submitted yet</p><p class="mt-1 text-sm text-ink-secondary">Your submissions and their review outcome will appear here.</p></div>
                @else
                    <ul class="stack-list">
                        @foreach($documents as $document)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-6">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink">{{ $documentLabels[$document->document_type->value] ?? str($document->document_type->value)->headline() }}</p>
                                    <p class="text-sm text-ink-muted">Submitted {{ $document->created_at->format('M j, Y') }}@if($document->expires_at) &middot; valid until {{ $document->expires_at->format('M j, Y') }}@endif</p>
                                    @if($document->notes)<p class="mt-1 text-sm text-ink-secondary"><span class="font-medium text-ink">Review notes:</span> {{ $document->notes }}</p>@endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-ui.status-badge :status="$document->status" />
                                    <a class="text-sm font-semibold text-navy-800 hover:underline" href="{{ route('verification.documents.download', $document) }}">Open file</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="grid content-start gap-4">
            <div class="card card-pad text-sm">
                <p class="flex items-center gap-2 font-semibold text-ink"><x-ui.icon name="lock-closed" class="size-4 text-navy-700" />Why Oncall asks for this</p>
                <ul class="mt-3 grid gap-2.5 text-ink-secondary">
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-success-600" />Customers know a real, identified person is coming to help.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-success-600" />Providers know requests come from identified customers.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-success-600" />If something goes wrong, Oncall can follow up with accountable accounts.</li>
                </ul>
                <p class="mt-4 border-t border-line pt-3 text-xs text-ink-muted">Files are stored on a private disk and can be opened only by you and authorised administrators. An Identity Verified badge is shown only after staff approval. Email and mobile verification are self-service and shown as separate badges.</p>
            </div>
        </aside>
    </div>
</x-layouts.app>
