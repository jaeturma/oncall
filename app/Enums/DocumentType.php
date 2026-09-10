<?php

namespace App\Enums;

enum DocumentType: string
{
    case NationalId = 'NATIONAL_ID';
    case DriversLicense = 'DRIVERS_LICENSE';
    case Passport = 'PASSPORT';
    case ProfessionalCredential = 'PROFESSIONAL_CREDENTIAL';
}
