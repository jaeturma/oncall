<?php

namespace App\Enums;

enum ReconciliationCategory: string
{
    case StalePayment = 'STALE_PAYMENT';
    case StaleAttempt = 'STALE_ATTEMPT';
    case DuplicateReference = 'DUPLICATE_REFERENCE';
    case RefundMismatch = 'REFUND_MISMATCH';
}
