<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Commission = 'COMMISSION';
    case WithdrawalHold = 'WITHDRAWAL_HOLD';
    case Withdrawal = 'WITHDRAWAL';
    case WithdrawalRelease = 'WITHDRAWAL_RELEASE';
    case Reversal = 'REVERSAL';
    case Adjustment = 'ADJUSTMENT';
    case Refund = 'REFUND';
}
