@props(['categories', 'provinces', 'filters' => [], 'services' => collect(), 'municipalities' => collect(), 'refine' => false])

<form {{ $attributes->merge(['class' => 'grid gap-3 rounded-2xl bg-white p-4 text-slate-900 shadow-xl']) }} method="GET" action="{{ route('providers.search') }}">
    <label class="grid gap-2 font-semibold" for="help">What help do you need?
        <select class="rounded-lg border border-slate-300 p-4" id="help" name="help" required>
            <option value="">Select a service or category</option>
            @foreach($categories as $category)
                <option value="category:{{ $category->id }}" @selected(($filters['help'] ?? '') === 'category:'.$category->id)>All {{ $category->name }}</option>
                @foreach($category->services as $service)
                    <option value="service:{{ $service->id }}" @selected(($filters['help'] ?? '') === 'service:'.$service->id)>{{ $service->name }}</option>
                @endforeach
            @endforeach
        </select>
        @error('help')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
    </label>
    <label class="grid gap-2 font-semibold" for="province_id">Where do you need help?
        <select class="rounded-lg border border-slate-300 p-4" id="province_id" name="province_id" required>
            <option value="">Select a province</option>
            @foreach($provinces as $province)<option value="{{ $province->id }}" @selected(($filters['province_id'] ?? '') == $province->id)>{{ $province->name }}</option>@endforeach
        </select>
        @error('province_id')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
    </label>
    @if($refine)
        <label class="grid gap-2 font-semibold" for="service_id">Refine by service
            <select class="rounded-lg border border-slate-300 p-4" id="service_id" name="service_id"><option value="">Any matching service</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(($filters['service_id'] ?? '') == $service->id)>{{ $service->name }}</option>@endforeach</select>
        </label>
        <label class="grid gap-2 font-semibold" for="municipality_id">Refine by municipality or city
            <select class="rounded-lg border border-slate-300 p-4" id="municipality_id" name="municipality_id"><option value="">Anywhere in the province</option>@foreach($municipalities as $municipality)<option value="{{ $municipality->id }}" @selected(($filters['municipality_id'] ?? '') == $municipality->id)>{{ $municipality->name }}</option>@endforeach</select>
        </label>
    @endif
    <button class="self-end rounded-lg bg-gold-400 px-7 py-4 font-bold text-navy-900 hover:bg-gold-500">Find Help</button>
</form>
