<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Requested = 'REQUESTED';
    case AccountingReview = 'ACCOUNTING_REVIEW';
    case BudgetApproval = 'BUDGET_APPROVAL';
    case ForDisbursement = 'FOR_DISBURSEMENT';
    case Completed = 'COMPLETED';
    case Returned = 'RETURNED';
    case Rejected = 'REJECTED';
    case Cancelled = 'CANCELLED';

    /**
     * Non-terminal states where the held amount is still reserved.
     *
     * @return list<self>
     */
    public static function openStates(): array
    {
        return [self::Requested, self::AccountingReview, self::BudgetApproval, self::ForDisbursement];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::openStates(), true);
    }

    /**
     * The staff role responsible for acting on a withdrawal in this state.
     */
    public function actingRole(): ?UserRole
    {
        return match ($this) {
            self::Requested, self::AccountingReview => UserRole::Accounting,
            self::BudgetApproval => UserRole::Budget,
            self::ForDisbursement => UserRole::Cashier,
            default => null,
        };
    }
}
