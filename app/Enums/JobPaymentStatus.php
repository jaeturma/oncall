<?php

namespace App\Enums;

/**
 * Pending  – job completed, waiting for the Service Finder to confirm payment.
 * Paid     – Finder confirmed payment; the provider earning is recorded but not
 *            yet withdrawable (Oncall has not confirmed it holds the funds).
 * Released – Accounting confirmed receipt; the provider earning is now spendable.
 * Reversed – The earning was clawed back (dispute / error).
 */
enum JobPaymentStatus: string
{
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Released = 'RELEASED';
    case Reversed = 'REVERSED';
}
