<x-layouts.admin title="Service catalog" description="Categories and services customers can search for. Inactive services are hidden from search.">
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($categories as $category)
            <section class="card card-pad">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="font-semibold text-ink">{{ $category->name }}</h2>
                    <x-ui.badge :tone="$category->active ? 'success' : 'neutral'">{{ $category->active ? 'Active' : 'Inactive' }}</x-ui.badge>
                </div>
                <ul class="mt-3 flex flex-wrap gap-1.5">
                    @forelse($category->services as $service)
                        <li class="badge {{ $service->active ? 'badge-brand' : 'badge-outline line-through' }}">{{ $service->name }}</li>
                    @empty
                        <li class="text-sm text-ink-muted">No services in this category yet.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-layouts.admin>
