<?php

namespace App\Enums;

/**
 * Pending   – submitted, awaiting verification.
 * Verified  – accepted as proof of the payment recorded against the JobPayment.
 * Rejected  – reviewed and refused (reserved for a future manual-review mode).
 * Expired   – never verified before its expiry window elapsed.
 * Cancelled – withdrawn before it was reviewed.
 */
enum PaymentAttemptStatus: string
{
    case Pending = 'PENDING';
    case Verified = 'VERIFIED';
    case Rejected = 'REJECTED';
    case Expired = 'EXPIRED';
    case Cancelled = 'CANCELLED';
}
