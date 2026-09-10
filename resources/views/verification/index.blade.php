<x-layouts.app title="Identity verification">
    <div class="grid gap-6">
        @if(session('status'))<div class="rounded-xl border border-gold-200 bg-gold-50 p-4 font-semibold text-navy-900">{{ session('status') }}</div>@endif
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><p class="font-bold uppercase tracking-widest text-navy-800">Private review</p><h2 class="mt-1 text-2xl font-black">Verify your identity</h2><p class="mt-2 max-w-2xl text-slate-600">Documents are stored privately and can only be opened by you and authorized administrators. A badge appears only after approval.</p></div>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-bold">{{ str(auth()->user()->identity_verification_status->value)->headline() }}</span>
            </div>
            @unless($documents->contains('status', \App\Enums\VerificationStatus::Submitted))
                <form class="mt-6 grid gap-5" method="POST" action="{{ route('verification.documents.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="grid gap-2 font-semibold">Document type<select class="rounded-lg border border-slate-300 p-3" name="document_type" required><option value="">Select document type</option>@foreach($documentTypes as $documentType)<option value="{{ $documentType->value }}" @selected(old('document_type') === $documentType->value)>{{ str($documentType->value)->headline() }}</option>@endforeach</select>@error('document_type')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
                    <label class="grid gap-2 font-semibold">Document file<input class="rounded-lg border border-dashed border-slate-300 p-5 file:mr-4 file:rounded-lg file:border-0 file:bg-navy-900 file:px-4 file:py-2 file:font-bold file:text-white" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required><span class="text-sm font-normal text-slate-500">JPG, PNG, or PDF up to 5 MB.</span>@error('document')<span class="text-sm text-red-700">{{ $message }}</span>@enderror</label>
                    <button class="justify-self-start rounded-lg bg-navy-900 px-5 py-3 font-bold text-white">Submit for review</button>
                </form>
            @else
                <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">Your latest document is awaiting admin review. You can submit another after this review is completed.</div>
            @endunless
        </section>
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm"><div class="border-b border-slate-200 p-6"><h2 class="text-xl font-black">Submission history</h2></div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-left text-sm"><thead class="bg-slate-50"><tr><th class="px-6 py-3">Type</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Submitted</th><th class="px-6 py-3">Document</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($documents as $document)<tr><td class="px-6 py-4 font-semibold">{{ str($document->document_type->value)->headline() }}</td><td class="px-6 py-4">{{ str($document->status->value)->headline() }}</td><td class="px-6 py-4">{{ $document->created_at->format('M j, Y') }}</td><td class="px-6 py-4"><a class="font-bold text-navy-800 hover:underline" href="{{ route('verification.documents.download', $document) }}">Open private file</a></td></tr>@empty<tr><td class="px-6 py-8 text-center text-slate-500" colspan="4">No documents submitted.</td></tr>@endforelse</tbody></table></div></section>
    </div>
</x-layouts.app>
