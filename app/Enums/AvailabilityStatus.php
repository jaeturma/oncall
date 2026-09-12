<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Available = 'AVAILABLE';
    case Busy = 'BUSY';
    case ByAppointment = 'BY_APPOINTMENT';
    case Offline = 'OFFLINE';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available now',
            self::Busy => 'Busy',
            self::ByAppointment => 'By appointment',
            self::Offline => 'Offline',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Available => 'Ready to take requests right away. Listed first in search results.',
            self::Busy => 'On a job right now. Customers can still send requests for later.',
            self::ByAppointment => 'Not taking same-day work, but open to scheduled bookings.',
            self::Offline => 'Hidden from "available" filters. You still appear in search results.',
        };
    }

    /** Lower ranks are listed first in search results. */
    public function rank(): int
    {
        return match ($this) {
            self::Available => 0,
            self::ByAppointment => 1,
            self::Busy => 2,
            self::Offline => 3,
        };
    }

    public function isAvailableNow(): bool
    {
        return $this === self::Available;
    }
}
