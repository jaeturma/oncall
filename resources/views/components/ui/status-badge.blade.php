@props(['status', 'size' => null])

@php
    /**
     * Maps every backend status vocabulary onto a small set of tones so the
     * whole product reads the same way. Text is always shown; colour is never
     * the only signal.
     */
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $tone = match ($value) {
        'ACTIVE', 'VERIFIED', 'COMPLETED', 'ACCEPTED', 'RELEASED', 'APPROVED', 'AVAILABLE', 'RESOLVED', 'DISBURSED', 'PAID', 'POSTED' => 'success',
        'PENDING', 'SUBMITTED', 'REQUESTED', 'SEARCHING', 'WARNING', 'FOR_DISBURSEMENT', 'ACCOUNTING_REVIEW', 'BUDGET_APPROVAL', 'OPEN', 'RETURNED' => 'warning',
        'REJECTED', 'SUSPENDED', 'RESTRICTED', 'CANCELLED', 'DISPUTED', 'REVERSED', 'EXPIRED', 'DENIED', 'UPHELD', 'VOID', 'DISMISSED' => 'danger',
        'IN_PROGRESS', 'ON_THE_WAY', 'UNDER_REVIEW', 'PARTIALLY_UPHELD' => 'info',
        default => 'neutral',
    };

    $label = str($value)->replace('_', ' ')->lower()->ucfirst();
@endphp

<x-ui.badge :tone="$tone" dot {{ $attributes }}>{{ $label }}</x-ui.badge>
