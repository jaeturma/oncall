<x-layouts.app title="Request service">
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        <p class="text-sm font-bold uppercase tracking-widest text-navy-800">Verified provider</p>
        <h2 class="mt-2 text-2xl font-black text-slate-900">Request service from {{ $profile->user->name }}</h2>
        <p class="mt-2 text-slate-600">Serving {{ $profile->municipality->name }}, {{ $profile->province->name }}</p>

        <form class="mt-8 grid gap-6" method="POST" action="{{ route('service-requests.store', $profile) }}">
            @csrf
            <input type="hidden" name="province_id" value="{{ $profile->province_id }}">
            <input type="hidden" name="municipality_id" value="{{ $profile->municipality_id }}">

            <label class="grid gap-2 font-semibold">Service
                <select class="rounded-lg border-slate-300" name="service_id" required>
                    <option value="">Choose a service</option>
                    @foreach($providerServices as $providerService)
                        <option value="{{ $providerService->service_id }}" @selected(old('service_id') == $providerService->service_id)>{{ $providerService->service->name }}</option>
                    @endforeach
                </select>
                @error('service_id')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
            </label>

            <label class="grid gap-2 font-semibold">Request title
                <input class="rounded-lg border-slate-300" type="text" name="title" value="{{ old('title') }}" maxlength="160" required>
                @error('title')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
            </label>

            <label class="grid gap-2 font-semibold">Describe the work
                <textarea class="rounded-lg border-slate-300" name="description" rows="6" maxlength="3000">{{ old('description') }}</textarea>
                @error('description')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
            </label>

            <div class="grid gap-6 sm:grid-cols-2">
                <label class="grid gap-2 font-semibold">Urgency
                    <select class="rounded-lg border-slate-300" name="urgency" required>
                        @foreach(App\Enums\ServiceUrgency::cases() as $urgency)
                            <option value="{{ $urgency->value }}" @selected(old('urgency') === $urgency->value)>{{ str($urgency->value)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                    @error('urgency')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 font-semibold">Needed at <span class="text-sm font-normal text-slate-500">Required when scheduled</span>
                    <input class="rounded-lg border-slate-300" type="datetime-local" name="needed_at" value="{{ old('needed_at') }}">
                    @error('needed_at')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <label class="grid gap-2 font-semibold">Minimum budget <span class="text-sm font-normal text-slate-500">Optional</span>
                    <input class="rounded-lg border-slate-300" type="number" name="budget_min" value="{{ old('budget_min') }}" min="0" step="0.01">
                    @error('budget_min')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 font-semibold">Maximum budget <span class="text-sm font-normal text-slate-500">Optional</span>
                    <input class="rounded-lg border-slate-300" type="number" name="budget_max" value="{{ old('budget_max') }}" min="0" step="0.01">
                    @error('budget_max')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
            </div>

            <div class="grid gap-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-950">
                <p class="font-black">Stay on Oncall. Stay protected.</p>
                <p>Do not include phone numbers, email addresses, links, or social handles before booking confirmation. Oncall may be unable to help with disputes or incidents when contact, booking, payment, or transactions are deliberately taken outside platform monitoring.</p>
                <label class="flex items-start gap-3 font-semibold"><input class="mt-1 rounded border-amber-400" type="checkbox" name="safety_acknowledged" value="1" @checked(old('safety_acknowledged')) required><span>I understand and agree to keep the booking, agreements, and important confirmations recorded on Oncall.</span></label>
                @error('safety_acknowledged')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
            </div>
            <button class="justify-self-start rounded-lg bg-navy-900 px-6 py-3 font-bold text-white hover:bg-navy-800" type="submit">Send service request</button>
        </form>
    </div>
</x-layouts.app>
