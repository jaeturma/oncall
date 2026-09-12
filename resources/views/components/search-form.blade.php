@props(['categories', 'provinces', 'filters' => [], 'services' => collect(), 'municipalities' => collect(), 'refine' => false, 'idPrefix' => null])

@php
    $selectedHelp = $filters['help'] ?? '';
    $selectedProvince = $filters['province_id'] ?? '';
    $prefix = $idPrefix ?? ($refine ? 'refine_' : '');
@endphp

@if($refine)
    <form {{ $attributes->class(['grid gap-4']) }} method="GET" action="{{ route('providers.search') }}">
        <x-form.field name="help" label="What help do you need?" :for="$prefix.'help'" required>
            <select class="select" id="{{ $prefix }}help" name="help" required>
                <option value="">Select a service or category</option>
                @foreach($categories as $category)
                    <optgroup label="{{ $category->name }}">
                        <option value="category:{{ $category->id }}" @selected($selectedHelp === 'category:'.$category->id)>All {{ $category->name }}</option>
                        @foreach($category->services as $service)
                            <option value="service:{{ $service->id }}" @selected($selectedHelp === 'service:'.$service->id)>{{ $service->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </x-form.field>
        <x-form.field name="province_id" label="Where do you need help?" :for="$prefix.'province_id'" required>
            <select class="select" id="{{ $prefix }}province_id" name="province_id" required>
                <option value="">Select a province</option>
                @foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) $selectedProvince === (string) $province->id)>{{ $province->name }}</option>@endforeach
            </select>
        </x-form.field>
        <x-form.field name="municipality_id" label="Municipality or city" :for="$prefix.'municipality_id'">
            <select class="select" id="{{ $prefix }}municipality_id" name="municipality_id" data-municipalities-for="{{ $prefix }}province_id" data-municipalities-url="{{ route('locations.municipalities', ['province' => 'PROVINCE']) }}">
                <option value="">Anywhere in the province</option>
                @foreach($municipalities as $municipality)<option value="{{ $municipality->id }}" @selected(($filters['municipality_id'] ?? '') == $municipality->id)>{{ $municipality->name }}</option>@endforeach
            </select>
        </x-form.field>
        <x-form.field name="service_id" label="Specific service" :for="$prefix.'service_id'">
            <select class="select" id="{{ $prefix }}service_id" name="service_id">
                <option value="">Any matching service</option>
                @foreach($services as $service)<option value="{{ $service->id }}" @selected(($filters['service_id'] ?? '') == $service->id)>{{ $service->name }}</option>@endforeach
            </select>
        </x-form.field>
        <x-form.field name="sort" label="Sort by" :for="$prefix.'sort'">
            <select class="select" id="{{ $prefix }}sort" name="sort">
                <option value="recommended" @selected(($filters['sort'] ?? 'recommended') === 'recommended')>Recommended (available, then nearest)</option>
                <option value="nearest" @selected(($filters['sort'] ?? '') === 'nearest')>Nearest first</option>
                <option value="rating" @selected(($filters['sort'] ?? '') === 'rating')>Highest rated first</option>
            </select>
        </x-form.field>
        <x-form.field name="min_rating" label="Minimum rating" :for="$prefix.'min_rating'">
            <select class="select" id="{{ $prefix }}min_rating" name="min_rating">
                <option value="">Any rating</option>
                @foreach([4 => '4 stars & up', 3 => '3 stars & up'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['min_rating'] ?? '') == $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-form.field>
        <label class="choice items-center gap-3 py-3" for="{{ $prefix }}available_only">
            <input class="checkbox" type="checkbox" id="{{ $prefix }}available_only" name="available_only" value="1" @checked((bool) ($filters['available_only'] ?? false))>
            <span class="text-sm font-medium text-ink">Available now only</span>
        </label>
        <div class="flex flex-wrap gap-2 pt-1">
            <x-ui.button variant="dark" class="flex-1">Apply filters</x-ui.button>
            @if(isset($filters['municipality_id']) || isset($filters['service_id']) || isset($filters['available_only']) || isset($filters['min_rating']) || isset($filters['sort']))
                <x-ui.button :href="route('providers.search', ['help' => $selectedHelp, 'province_id' => $selectedProvince])" variant="secondary">Clear</x-ui.button>
            @endif
        </div>
    </form>
@else
    <form {{ $attributes->class(['grid gap-3 rounded-2xl bg-surface p-4 text-ink shadow-pop sm:p-5 md:grid-cols-[1fr_1fr_auto] md:items-end']) }} method="GET" action="{{ route('providers.search') }}" aria-label="Service finder">
        <x-form.field name="help" label="What help do you need?" for="help" required>
            <div class="relative">
                <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-ink-muted" />
                <select class="select select-lg pl-11 {{ $errors->has('help') ? 'select-invalid' : '' }}" id="help" name="help" required>
                    <option value="">Choose a service</option>
                    @foreach($categories as $category)
                        <optgroup label="{{ $category->name }}">
                            <option value="category:{{ $category->id }}" @selected($selectedHelp === 'category:'.$category->id)>All {{ $category->name }}</option>
                            @foreach($category->services as $service)
                                <option value="service:{{ $service->id }}" @selected($selectedHelp === 'service:'.$service->id)>{{ $service->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        </x-form.field>
        <x-form.field name="province_id" label="Where do you need help?" for="province_id" required>
            <div class="relative">
                <x-ui.icon name="map-pin" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-ink-muted" />
                <select class="select select-lg pl-11 {{ $errors->has('province_id') ? 'select-invalid' : '' }}" id="province_id" name="province_id" required>
                    <option value="">Choose a province</option>
                    @foreach($provinces as $province)<option value="{{ $province->id }}" @selected((string) $selectedProvince === (string) $province->id)>{{ $province->name }}</option>@endforeach
                </select>
            </div>
        </x-form.field>
        <x-ui.button variant="primary" size="lg" icon="search" class="md:min-w-44">Find Help</x-ui.button>
    </form>
@endif
