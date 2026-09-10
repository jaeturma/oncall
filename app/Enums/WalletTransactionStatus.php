<?php

namespace App\Enums;

/**
 * Pending  – recorded but not yet counted toward the available balance (unapproved commission).
 * Posted   – counts toward the available balance. Immutable once set.
 * Void     – a Pending entry that was cancelled before it ever counted (no reversing entry needed).
 */
enum WalletTransactionStatus: string
{
    case Pending = 'PENDING';
    case Posted = 'POSTED';
    case Void = 'VOID';
}
