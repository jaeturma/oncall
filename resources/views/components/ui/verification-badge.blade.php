@props(['type', 'size' => 'sm', 'onDark' => false])

@php
    /**
     * Only render a verification badge for a verification that actually
     * exists in backend data. Callers decide *whether* to show it; this
     * component only decides *how*.
     */
    $meta = match ($type) {
        'identity' => ['label' => 'Identity Verified', 'icon' => 'shield-check', 'help' => 'A government ID was reviewed and approved by Oncall staff.'],
        'mobile' => ['label' => 'Mobile Verified', 'icon' => 'phone', 'help' => 'The mobile number on this account has been confirmed.'],
        'email' => ['label' => 'Email Verified', 'icon' => 'envelope', 'help' => 'The email address on this account has been confirmed.'],
        'drivers-license' => ['label' => "Driver's License Verified", 'icon' => 'identification', 'help' => "A driver's license was reviewed and approved by Oncall staff."],
        'professional-license' => ['label' => 'Professional License Verified', 'icon' => 'academic-cap', 'help' => 'A professional license or credential was reviewed and approved by Oncall staff.'],
        'passport' => ['label' => 'Passport Verified', 'icon' => 'identification', 'help' => 'A passport was reviewed and approved by Oncall staff.'],
        'provider' => ['label' => 'Approved Provider', 'icon' => 'check-badge', 'help' => 'This provider profile was reviewed and approved by Oncall staff.'],
        default => ['label' => str($type)->headline().' Verified', 'icon' => 'check-badge', 'help' => 'Verified by Oncall staff.'],
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-full font-semibold whitespace-nowrap',
    'px-2.5 py-1 text-xs' => $size === 'sm',
    'px-3 py-1.5 text-sm' => $size === 'md',
    'bg-success-50 text-success-800 ring-1 ring-success-100' => ! $onDark,
    'bg-white/10 text-white ring-1 ring-white/15' => $onDark,
]) }} title="{{ $meta['help'] }}">
    <x-ui.icon :name="$meta['icon']" class="{{ $size === 'sm' ? 'size-3.5' : 'size-4' }} {{ $onDark ? 'text-gold-300' : 'text-success-600' }}" />
    {{ $meta['label'] }}
</span>
