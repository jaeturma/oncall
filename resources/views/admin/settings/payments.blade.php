<x-layouts.admin title="Payment settings" description="Configure accepted payment methods, transaction limits, receipts, and refund policy.">
    <form class="card card-pad grid max-w-3xl gap-6 sm:p-8" method="POST" action="{{ route('admin.settings.payments.update') }}">
        @csrf @method('PATCH')
        <x-form.errors />

        <x-form.checkbox name="payments_enabled" label="Payments enabled" hint="When off, no new job payments can be confirmed." :checked="(bool) old('payments_enabled', $settings->payments_enabled)" boxed />

        <h2 class="h3 mt-2">Accepted payment methods</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach(\App\Enums\PaymentMethod::cases() as $method)
                <x-form.checkbox name="allowed_payment_methods[]" :label="str($method->value)->replace('_', ' ')->title()" :value="$method->value" :checked="in_array($method->value, old('allowed_payment_methods', $settings->allowed_payment_methods ?? []))" boxed />
            @endforeach
        </div>
        <x-form.checkbox name="manual_payment_enabled" label="Manual (self-reported) payment enabled" hint="Oncall does not process payments; this is the only supported flow today." :checked="(bool) old('manual_payment_enabled', $settings->manual_payment_enabled)" boxed />
        <x-form.checkbox name="sandbox_mode" label="Sandbox mode" hint="Reserved for a future real gateway integration; the manual gateway ignores this." :checked="(bool) old('sandbox_mode', $settings->sandbox_mode)" boxed />

        <h2 class="h3 mt-2">Transaction limits</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="min_transaction_amount" type="number" step="0.01" label="Minimum transaction amount (PHP)" :value="old('min_transaction_amount', $settings->min_transaction_amount)" required />
            <x-form.input name="max_transaction_amount" type="number" step="0.01" label="Maximum transaction amount (PHP)" :value="old('max_transaction_amount', $settings->max_transaction_amount)" required />
            <x-form.input name="payment_expiry_minutes" type="number" label="Payment attempt expiry (minutes)" min="5" max="1440" :value="old('payment_expiry_minutes', $settings->payment_expiry_minutes)" required />
            <x-form.input name="receipt_prefix" label="Receipt number prefix" maxlength="10" :value="old('receipt_prefix', $settings->receipt_prefix)" required />
        </div>

        <h2 class="h3 mt-2">Platform fee</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="platform_fee_type" label="Fee type">
                @foreach(\App\Enums\CommissionType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('platform_fee_type', $settings->platform_fee_type->value) === $type->value)>{{ str($type->value)->title() }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="platform_fee_value" type="number" step="0.01" label="Fee value (% or fixed PHP, per type)" :value="old('platform_fee_value', $settings->platform_fee_value)" required />
        </div>
        <p class="text-sm text-ink-muted">An account type's own commission override always takes precedence over this platform-wide default.</p>

        <h2 class="h3 mt-2">Refunds</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="refund_window_days" type="number" label="Refund window (days after payment)" min="1" max="365" :value="old('refund_window_days', $settings->refund_window_days)" required />
            <x-form.input name="max_refund_requests_per_payment" type="number" label="Max refund requests per payment" min="1" max="20" :value="old('max_refund_requests_per_payment', $settings->max_refund_requests_per_payment)" required />
        </div>

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save settings</x-ui.button>
            <x-ui.button variant="secondary" :href="route('admin.finance.reconciliation.index')">View reconciliation queue</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
