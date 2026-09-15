<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'CASH';
    case Gcash = 'GCASH';
    case Maya = 'MAYA';
    case BankTransfer = 'BANK_TRANSFER';
    case Other = 'OTHER';
}
