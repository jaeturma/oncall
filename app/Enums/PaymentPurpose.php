<?php

namespace App\Enums;

enum PaymentPurpose: string
{
    case ServiceTransaction = 'SERVICE_TRANSACTION';
    case RegistrationFee = 'REGISTRATION_FEE';
}
