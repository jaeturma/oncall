<x-layouts.admin title="Location &amp; map settings" description="Configure maps, geocoding, and the admin-bounded search radius policy used across nearby-provider discovery.">
    <form class="card card-pad grid max-w-3xl gap-6 sm:p-8" method="POST" action="{{ route('admin.settings.location.update') }}">
        @csrf @method('PATCH')
        <x-form.errors />

        <h2 class="h3 mt-2">Maps</h2>
        <x-form.checkbox name="maps_enabled" label="Maps enabled" hint="When off, the Flutter app falls back to List View only." :checked="(bool) old('maps_enabled', $settings->maps_enabled)" boxed />
        <x-form.select name="active_map_provider" label="Active map provider">
            <option value="OPENSTREETMAP" @selected(old('active_map_provider', $settings->active_map_provider) === 'OPENSTREETMAP')>OpenStreetMap</option>
        </x-form.select>

        <h2 class="h3 mt-2">Geocoding</h2>
        <x-form.checkbox name="geocoding_enabled" label="Geocoding enabled" hint="Province/city/barangay resolution always works locally regardless of this setting. When on, an external provider also supplies a human-readable address label." :checked="(bool) old('geocoding_enabled', $settings->geocoding_enabled)" boxed />
        <x-form.input name="geocoding_base_url" label="Geocoding base URL" :value="old('geocoding_base_url', $settings->geocoding_base_url)" maxlength="500" optional hint="Must be a public HTTPS endpoint — internal/private addresses are rejected. Leave blank to use OpenStreetMap's public Nominatim endpoint." placeholder="https://nominatim.openstreetmap.org/reverse" />

        <h2 class="h3 mt-2">Search radius</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="default_search_radius_km" type="number" label="Default search radius (km)" min="1" max="500" :value="old('default_search_radius_km', $settings->default_search_radius_km)" required />
            <x-form.input name="max_search_radius_km" type="number" label="Max search radius (km)" min="1" max="500" :value="old('max_search_radius_km', $settings->max_search_radius_km)" required />
        </div>
        <x-form.input name="allowed_radius_choices" label="Allowed radius choices (km, comma-separated)" :value="old('allowed_radius_choices', implode(', ', $settings->radiusChoices()))" maxlength="200" required hint="e.g. 5, 10, 15, 25, 50 — offered to customers and providers as selectable radius values." />

        <h2 class="h3 mt-2">Policy</h2>
        <div class="grid gap-6 sm:grid-cols-3">
            <x-form.input name="default_country" label="Default country (ISO 2-letter)" :value="old('default_country', $settings->default_country)" maxlength="2" required />
            <x-form.input name="location_freshness_days" type="number" label="Location freshness threshold (days)" min="1" max="365" :value="old('location_freshness_days', $settings->location_freshness_days)" required hint="A provider location older than this is flagged as stale on the diagnostics page." />
            <x-form.select name="provider_location_policy" label="Provider location policy">
                @foreach(App\Enums\ProviderLocationPolicy::cases() as $policy)
                    <option value="{{ $policy->value }}" @selected(old('provider_location_policy', $settings->provider_location_policy->value) === $policy->value)>{{ str($policy->value)->title() }}</option>
                @endforeach
            </x-form.select>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save settings</x-ui.button>
            <x-ui.button variant="secondary" :href="route('admin.location-diagnostics.index')">View diagnostics</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
