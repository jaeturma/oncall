<?php

namespace App\Enums;

enum LocationSource: string
{
    case Gps = 'GPS';
    case MapPin = 'MAP_PIN';
    case Manual = 'MANUAL';
    case Admin = 'ADMIN';
}
