<x-layouts.admin title="Location diagnostics" description="Read-only data-quality signals for the location/discovery layer. No coordinates are displayed here.">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.stat-card label="Providers missing coordinates" :value="$providersMissingCoordinates" hint="of {{ $totalProviders }} total providers" icon="map-pin" :tone="$providersMissingCoordinates > 0 ? 'warning' : 'neutral'" />
        <x-ui.stat-card label="Providers with stale location" :value="$providersWithStaleLocation" hint="not updated in over {{ $settings->location_freshness_days }} days" icon="clock" :tone="$providersWithStaleLocation > 0 ? 'warning' : 'neutral'" />
        <x-ui.stat-card label="Municipalities missing centroid" :value="$municipalitiesMissingCentroid" icon="map-pin" :tone="$municipalitiesMissingCentroid > 0 ? 'danger' : 'neutral'" />
        <x-ui.stat-card label="Barangays missing centroid" :value="$barangaysMissingCentroid" hint="of {{ $totalBarangays }} total barangays" icon="map-pin" :tone="$barangaysMissingCentroid > 0 ? 'warning' : 'neutral'" />
        <x-ui.stat-card label="Maps enabled" :value="$settings->maps_enabled ? 'Yes' : 'No'" icon="cog" tone="brand" />
        <x-ui.stat-card label="Geocoding enabled" :value="$settings->geocoding_enabled ? 'Yes' : 'No'" icon="cog" tone="brand" />
    </div>

    <div class="mt-6">
        <x-ui.button variant="secondary" :href="route('admin.settings.location.edit')">Edit location settings</x-ui.button>
    </div>
</x-layouts.admin>
