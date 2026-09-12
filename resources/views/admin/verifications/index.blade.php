@php
    $documentLabels = [
        'NATIONAL_ID' => 'National ID / government ID',
        'DRIVERS_LICENSE' => "Driver's license",
        'PASSPORT' => 'Passport',
        'PROFESSIONAL_CREDENTIAL' => 'Professional license or credential',
    ];
@endphp

<x-layouts.admin title="Verification review queue" description="Approval must be based on the private document itself. A submission never proves identity on its own.">
    @if($documents->isEmpty())
        <x-empty-state icon="identification" title="Queue is clear" message="No verification submissions are waiting for review." />
    @else
        <div class="grid gap-4">
            @foreach($documents as $document)
                <article class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$document->user->name" size="md" />
                            <div>
                                <h2 class="font-semibold text-ink">{{ $document->user->name }}</h2>
                                <p class="text-sm text-ink-muted">{{ $documentLabels[$document->document_type->value] ?? str($document->document_type->value)->headline() }} &middot; submitted {{ $document->created_at->diffForHumans() }} &middot; {{ str($document->user->role->value)->replace('_', ' ')->lower()->ucfirst() }}</p>
                            </div>
                        </div>
                        <x-ui.button :href="route('verification.documents.download', $document)" variant="secondary" size="sm" icon="document-check">Open private document</x-ui.button>
                    </div>
                    <form class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6" method="POST" action="{{ route('admin.verifications.update', $document) }}">
                        @csrf @method('PATCH')
                        <x-form.select name="status" label="Decision" required>
                            <option value="VERIFIED">Verify — document is genuine and matches the account</option>
                            <option value="REJECTED">Reject — unclear, mismatched, or invalid</option>
                            <option value="EXPIRED">Mark expired</option>
                        </x-form.select>
                        <x-form.input name="expires_at" type="date" label="Valid until" :min="now()->addDay()->toDateString()" optional hint="Set for licenses and IDs with an expiry date." />
                        <x-form.textarea name="notes" label="Review notes" rows="2" wrapper-class="sm:col-span-2" placeholder="Required when rejecting or marking expired. Shown to the user." />
                        <div class="sm:col-span-2"><x-ui.button variant="dark" data-loading-text="Saving…">Save review</x-ui.button></div>
                    </form>
                </article>
            @endforeach
        </div>
        @if($documents->hasPages())<div class="mt-5">{{ $documents->links() }}</div>@endif
    @endif
</x-layouts.admin>
