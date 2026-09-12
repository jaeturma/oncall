@php
    $selectedProvince = (int) old('province_id', $profile?->province_id ?? 0);
    $selectedMunicipality = (int) old('municipality_id', $profile?->municipality_id ?? 0);
    $provinces = $municipalities->pluck('province')->unique('id')->sortBy('name');
    $visibleMunicipalities = $selectedProvince ? $municipalities->where('province_id', $selectedProvince) : $municipalities;
    $selectedServices = old('service_ids', $profile?->providerServices->pluck('service_id')->all() ?? []);
@endphp

<x-layouts.app :title="$profile ? 'Edit provider profile' : 'Create provider profile'" eyebrow="Provider profile" description="Tell customers what you do and where you can help. Phone and email stay private until a booking is confirmed.">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <form class="card card-pad grid gap-8 sm:p-8" method="POST" action="{{ $profile ? route('provider.profiles.update', $profile) : route('provider.profiles.store') }}">
            @csrf @if($profile) @method('PUT') @endif
            <x-form.errors />

            <section class="grid gap-5">
                <div><h2 class="h3">Where you work</h2><p class="mt-1 text-sm text-ink-secondary">Customers search by province and can filter by municipality or city.</p></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="province_id" label="Province" placeholder="Select province" required>
                        @foreach($provinces as $province)<option value="{{ $province->id }}" @selected($selectedProvince === $province->id)>{{ $province->name }}</option>@endforeach
                    </x-form.select>
                    <x-form.select name="municipality_id" label="Municipality or city" placeholder="Select municipality or city" required data-municipalities-for="province_id" data-municipalities-url="{{ route('locations.municipalities', ['province' => 'PROVINCE']) }}">
                        @foreach($visibleMunicipalities as $municipality)<option value="{{ $municipality->id }}" @selected($selectedMunicipality === $municipality->id)>{{ $municipality->name }}@unless($selectedProvince), {{ $municipality->province->name }}@endunless</option>@endforeach
                    </x-form.select>
                </div>
                <x-form.input name="service_radius_km" type="number" label="How far will you travel? (km)" min="1" max="500" inputmode="numeric" :value="$profile?->service_radius_km" optional hint="Shown on your profile as 'up to N km'." class="sm:max-w-xs" />
            </section>

            <section class="grid gap-5 border-t border-line pt-8">
                <div><h2 class="h3">Services you offer</h2><p class="mt-1 text-sm text-ink-secondary">Choose every service you can do well. The first one you pick is shown as your main service.</p></div>
                <fieldset class="grid gap-2 sm:grid-cols-2" @if($errors->has('service_ids')) data-has-error @endif>
                    <legend class="sr-only">Services</legend>
                    @foreach($services as $service)
                        <label class="choice items-center py-3">
                            <input class="checkbox" type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selectedServices))>
                            <span class="text-sm font-medium text-ink">{{ $service->name }}</span>
                        </label>
                    @endforeach
                </fieldset>
                @error('service_ids')<p class="field-error"><x-ui.icon name="exclamation-triangle" class="mt-0.5 size-4" />{{ $message }}</p>@enderror
            </section>

            <section class="grid gap-5 border-t border-line pt-8">
                <div><h2 class="h3">About you</h2><p class="mt-1 text-sm text-ink-secondary">Shown to verified customers only. Do not include phone numbers, emails, or social handles.</p></div>
                <x-form.textarea name="bio" label="Introduce your work" rows="5" :value="$profile?->bio" optional placeholder="e.g. 8 years as a residential electrician. I handle wiring, panel upgrades, and repairs, and I bring my own tools." />
                <x-form.input name="credentials_metadata[0]" label="Credential summary" :value="$profile?->credentials_metadata[0] ?? ''" optional placeholder="e.g. TESDA NC II Electrical Installation" hint="Listed as self-declared. Upload the actual license under Verification to earn a verified badge." />
            </section>

            <div class="flex flex-wrap items-center gap-3 border-t border-line pt-6">
                <x-ui.button variant="primary" size="lg" data-loading-text="Saving…">{{ $profile ? 'Save changes' : 'Create profile' }}</x-ui.button>
                <x-ui.button :href="route('provider.dashboard')" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>

        <aside class="grid content-start gap-4">
            <div class="card card-pad text-sm">
                <p class="font-semibold text-ink">What customers see</p>
                <ul class="mt-3 grid gap-2 text-ink-secondary">
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-success-600" />Your services, location, rating, and completed jobs.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-success-600" />Verification badges approved by Oncall staff.</li>
                    <li class="flex gap-2"><x-ui.icon name="eye-slash" class="mt-0.5 size-4 shrink-0 text-ink-muted" />Guests see you as "Verified {service} #ID" — never your name.</li>
                </ul>
            </div>
            <x-safety-notice variant="compact" />
        </aside>
    </div>
</x-layouts.app>
