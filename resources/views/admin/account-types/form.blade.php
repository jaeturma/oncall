<x-layouts.admin :title="$accountType->exists ? 'Edit account type' : 'New account type'" description="Fees and commission rules apply to registrations that use this account type.">
    <form class="card card-pad grid max-w-2xl gap-6 sm:p-8" method="POST" action="{{ $accountType->exists ? route('admin.account-types.update', $accountType) : route('admin.account-types.store') }}">
        @csrf
        @if($accountType->exists) @method('PUT') @endif
        <x-form.errors />

        <x-form.input name="name" label="Name" :value="$accountType->name" maxlength="120" required />
        <x-form.input name="registration_fee" type="number" label="Registration fee (₱)" min="0" step="0.01" inputmode="decimal" :value="$accountType->registration_fee ?? '0.00'" required hint="Percentage commissions are calculated against this amount." />

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="sponsor_commission_type" label="Sponsor commission type">
                @foreach(App\Enums\CommissionType::cases() as $type)<option value="{{ $type->value }}" @selected(old('sponsor_commission_type', $accountType->sponsor_commission_type?->value ?? 'NONE') === $type->value)>{{ str($type->value)->lower()->ucfirst() }}</option>@endforeach
            </x-form.select>
            <x-form.input name="sponsor_commission_value" type="number" label="Commission value" min="0" step="0.01" inputmode="decimal" :value="$accountType->sponsor_commission_value ?? '0.00'" required hint="Peso amount for Fixed, or percent (0–100) for Percentage." />
        </div>

        <x-form.input name="platform_commission_percent" type="number" label="Oncall platform commission (%)" min="0" max="100" step="0.01" inputmode="decimal" :value="$accountType->platform_commission_percent" placeholder="Leave blank to use the global default" :hint="'Percent of a completed job\'s agreed price. Blank uses the global default of '.config('oncall.platform.commission_percent').'%.'" />

        <div class="grid gap-3">
            <x-form.checkbox name="requires_identity_verification" label="Requires identity verification" hint="Users must be verified before sending or accepting requests." :checked="(bool) old('requires_identity_verification', $accountType->requires_identity_verification ?? true)" boxed />
            <x-form.checkbox name="active" label="Active" hint="Available to new registrations." :checked="(bool) old('active', $accountType->active ?? true)" boxed />
        </div>

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save account type</x-ui.button>
            <x-ui.button :href="route('admin.account-types.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
