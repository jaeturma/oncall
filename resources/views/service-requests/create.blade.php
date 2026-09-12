<x-layouts.app title="Request a service" eyebrow="New request" :description="'You are requesting '.$profile->user->name.', serving '.$profile->municipality->name.', '.$profile->province->name.'.'">
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        <form class="card card-pad grid gap-6 sm:p-8" method="POST" action="{{ route('service-requests.store', $profile) }}">
            @csrf
            <input type="hidden" name="province_id" value="{{ $profile->province_id }}">
            <input type="hidden" name="municipality_id" value="{{ $profile->municipality_id }}">

            <x-form.errors />

            <section class="grid gap-5">
                <h2 class="h3">What do you need?</h2>
                <x-form.select name="service_id" label="Service" placeholder="Choose a service" required>
                    @foreach($providerServices as $providerService)
                        <option value="{{ $providerService->service_id }}" @selected(old('service_id') == $providerService->service_id)>{{ $providerService->service->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.input name="title" label="Short title" placeholder="e.g. Fix leaking kitchen faucet" maxlength="160" required hint="One line that tells the provider what the job is." />
                <x-form.textarea name="description" label="Describe the work" rows="5" maxlength="3000" placeholder="What needs to be done, where in the house, anything the provider should bring or know." optional />
            </section>

            <section class="grid gap-5 border-t border-line pt-6">
                <h2 class="h3">When and how much?</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="urgency" label="How soon?" required>
                        @foreach(App\Enums\ServiceUrgency::cases() as $urgency)
                            <option value="{{ $urgency->value }}" @selected(old('urgency', 'SAME_DAY') === $urgency->value)>{{ match ($urgency) { App\Enums\ServiceUrgency::Immediate => 'As soon as possible', App\Enums\ServiceUrgency::SameDay => 'Today', App\Enums\ServiceUrgency::Scheduled => 'On a specific date' } }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.input name="needed_at" type="datetime-local" label="Date and time" hint="Required when scheduling a specific date." />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="budget_min" type="number" label="Budget from (₱)" min="0" step="0.01" inputmode="decimal" placeholder="0.00" optional />
                    <x-form.input name="budget_max" type="number" label="Budget up to (₱)" min="0" step="0.01" inputmode="decimal" placeholder="0.00" optional />
                </div>
            </section>

            <section class="grid gap-3 rounded-xl bg-gold-50 p-4 ring-1 ring-gold-200 sm:p-5">
                <p class="flex items-center gap-2 font-semibold text-navy-900"><x-ui.icon name="shield-check" class="size-5 text-gold-700" />Stay protected with Oncall</p>
                <p class="text-sm text-navy-800">Do not include phone numbers, email addresses, links, or social handles before the booking is confirmed. Oncall may be unable to help with disputes or incidents when contact, booking, or payment is deliberately taken outside the platform.</p>
                <x-form.checkbox name="safety_acknowledged" label="I understand and agree to keep the booking, agreements, and important confirmations recorded on Oncall." :checked="(bool) old('safety_acknowledged')" required />
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary" size="lg" data-loading-text="Sending…">Send service request</x-ui.button>
                <x-ui.button :href="route('providers.show', $profile)" variant="ghost">Cancel</x-ui.button>
            </div>
        </form>

        <aside class="grid content-start gap-4">
            <div class="card card-pad">
                <p class="eyebrow text-navy-700">Provider</p>
                <div class="mt-3 flex items-center gap-3">
                    <x-ui.avatar :name="$profile->user->name" size="md" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink">{{ $profile->user->name }}</p>
                        <p class="truncate text-sm text-ink-muted">{{ $profile->municipality->name }}, {{ $profile->province->name }}</p>
                    </div>
                </div>
                <div class="mt-3"><x-ui.availability-badge :status="$profile->availability_status" /></div>
                <ul class="mt-4 grid gap-2 text-sm text-ink-secondary">
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-success-600" />The provider reviews your request and sets an agreed price.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-success-600" />Contact details are shared once the booking is confirmed.</li>
                    <li class="flex gap-2"><x-ui.icon name="check" class="mt-0.5 size-4 text-success-600" />You can cancel while the request is still pending.</li>
                </ul>
            </div>
        </aside>
    </div>
</x-layouts.app>
