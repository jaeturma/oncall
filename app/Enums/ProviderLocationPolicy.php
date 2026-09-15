<?php

namespace App\Enums;

enum ProviderLocationPolicy: string
{
    case Optional = 'OPTIONAL';
    case Required = 'REQUIRED';
}
