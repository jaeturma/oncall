<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaymentSettingsRequest;
use App\Models\AuditLog;
use App\Models\PaymentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-payment-settings');

        return view('admin.settings.payments', ['settings' => PaymentSetting::current()]);
    }

    public function update(UpdatePaymentSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('manage-payment-settings');
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $settings = PaymentSetting::current();
            $before = $settings->toArray();

            $settings->update([
                'payments_enabled' => $request->boolean('payments_enabled'),
                'allowed_payment_methods' => $data['allowed_payment_methods'],
                'sandbox_mode' => $request->boolean('sandbox_mode'),
                'min_transaction_amount' => $data['min_transaction_amount'],
                'max_transaction_amount' => $data['max_transaction_amount'],
                'payment_expiry_minutes' => $data['payment_expiry_minutes'],
                'receipt_prefix' => strtoupper($data['receipt_prefix']),
                'manual_payment_enabled' => $request->boolean('manual_payment_enabled'),
                'refund_window_days' => $data['refund_window_days'],
                'max_refund_requests_per_payment' => $data['max_refund_requests_per_payment'],
                'platform_fee_type' => $data['platform_fee_type'],
                'platform_fee_value' => $data['platform_fee_value'],
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'event' => 'payment_settings.updated',
                'subject_type' => PaymentSetting::class,
                'subject_id' => $settings->id,
                'before_json' => $before,
                'after_json' => $settings->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.settings.payments.edit')->with('status', 'Payment settings saved.');
    }
}
