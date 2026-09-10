<x-layouts.app :title="$profile ? 'Edit provider profile' : 'Create provider profile'">
    <div class="mx-auto max-w-3xl rounded-2xl bg-white p-6 shadow-sm">
        <h1 class="text-3xl font-black">{{ $profile ? 'Edit provider profile' : 'Create provider profile' }}</h1>
        <p class="mt-2 text-slate-600">Tell Service Finders what you do and where you can help. Contact details stay protected on Oncall.</p>
        @if($errors->any())
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
                <p class="font-bold">Please correct the highlighted fields.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form class="mt-8 grid gap-6" method="POST" action="{{ $profile ? route('provider.profiles.update', $profile) : route('provider.profiles.store') }}">
            @csrf @if($profile) @method('PUT') @endif
            <label class="grid gap-2 font-semibold">Province
                <select class="rounded-lg border border-slate-300 p-3" name="province_id" required><option value="">Select province</option>@foreach($municipalities->pluck('province')->unique('id') as $province)<option value="{{ $province->id }}" @selected(old('province_id', $profile?->province_id) == $province->id)>{{ $province->name }}</option>@endforeach</select>
            </label>
            <label class="grid gap-2 font-semibold">Municipality or city
                <select class="rounded-lg border border-slate-300 p-3" name="municipality_id" required><option value="">Select location</option>@foreach($municipalities as $municipality)<option value="{{ $municipality->id }}" @selected(old('municipality_id', $profile?->municipality_id) == $municipality->id)>{{ $municipality->name }}, {{ $municipality->province->name }}</option>@endforeach</select>
            </label>
            <label class="grid gap-2 font-semibold">About your work<textarea class="rounded-lg border border-slate-300 p-3" name="bio" rows="5">{{ old('bio', $profile?->bio) }}</textarea></label>
            <label class="grid gap-2 font-semibold">Service radius (km)<input class="rounded-lg border border-slate-300 p-3" type="number" min="1" max="500" name="service_radius_km" value="{{ old('service_radius_km', $profile?->service_radius_km) }}"></label>
            <fieldset class="grid gap-3"><legend class="font-semibold">Services</legend>@foreach($services as $service)<label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, old('service_ids', $profile?->providerServices->pluck('service_id')->all() ?? [])))>{{ $service->name }}</label>@endforeach</fieldset>
            <label class="grid gap-2 font-semibold">Credential summary<input class="rounded-lg border border-slate-300 p-3" name="credentials_metadata[]" value="{{ old('credentials_metadata.0', $profile?->credentials_metadata[0] ?? '') }}" placeholder="e.g. TESDA NC II"><span class="text-sm font-normal text-slate-500">Metadata only for now; document verification is handled in a later phase.</span></label>
            <button class="rounded-lg bg-navy-900 px-5 py-3 font-bold text-white">Save provider profile</button>
        </form>
    </div>
</x-layouts.app>
