<?php

namespace App\Enums;

enum DisputeCategory: string
{
    case ServiceNotAsAgreed = 'SERVICE_NOT_AS_AGREED';
    case NoShow = 'NO_SHOW';
    case PaymentIssue = 'PAYMENT_ISSUE';
    case Conduct = 'CONDUCT';
    case Safety = 'SAFETY';
    case Other = 'OTHER';

    /** Maps to the enforcement-case violation vocabulary when a dispute escalates. */
    public function toReportCategory(): ReportCategory
    {
        return match ($this) {
            self::ServiceNotAsAgreed, self::PaymentIssue => ReportCategory::Fraud,
            self::NoShow => ReportCategory::NoShow,
            self::Conduct => ReportCategory::Harassment,
            self::Safety => ReportCategory::UnsafeBehavior,
            self::Other => ReportCategory::Other,
        };
    }
}
