@props(['title' => 'Dashboard', 'eyebrow' => null, 'description' => null, 'heading' => true, 'wide' => false])

<x-layouts.public :title="$title">
    <div class="container-x grid gap-6 py-6 sm:py-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10 lg:py-10">
        <x-partials.sidebar />
        <div class="min-w-0 {{ $wide ? '' : 'max-w-5xl' }}">
            @if($heading)
                <x-ui.page-heading :title="$title" :eyebrow="$eyebrow" :description="$description" class="mb-6">
                    @isset($actions)
        <x-slot:actions>{{ $actions }}</x-slot:actions>
    @endisset
                </x-ui.page-heading>
            @endif
            <x-flash class="mb-6" />
            {{ $slot }}
        </div>
    </div>
</x-layouts.public>
