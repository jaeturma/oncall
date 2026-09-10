<?php

namespace App\Enums;

enum UserRole: string
{
    case ServiceFinder = 'SERVICE_FINDER';
    case ServiceProvider = 'SERVICE_PROVIDER';
    case Admin = 'ADMIN';
    case Accounting = 'ACCOUNTING';
    case Budget = 'BUDGET';
    case Cashier = 'CASHIER';
}
