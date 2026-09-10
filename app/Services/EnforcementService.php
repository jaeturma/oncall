<?php

namespace App\Services;

use App\Enums\AppealStatus;
use App\Enums\EnforcementAction;
use App\Enums\EnforcementCaseStatus;
use App\Enums\RestrictedCapability;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnforcementService
{
    public function __construct(private readonly Notifier $notifier) {}

    /** @param array<string, mixed> $attributes */
    public function apply(EnforcementCase $case, User $admin, array $attributes, ?string $ipAddress, ?string $userAgent): EnforcementCase
    {
        return DB::transaction(function () use ($case, $admin, $attributes, $ipAddress, $userAgent): EnforcementCase {
            $lockedCase = EnforcementCase::whereKey($case)->lockForUpdate()->firstOrFail();
            $targetAction = EnforcementAction::from($attributes['action']);

            if ($targetAction !== $this->nextAction($lockedCase->action)) {
                throw new ConflictHttpException('Enforcement actions must follow the required progression.');
            }

            $before = $lockedCase->toArray();
            $capabilities = $attributes['restricted_capabilities'] ?? [];
            $endsAt = isset($attributes['duration_days']) ? now()->addDays((int) $attributes['duration_days']) : null;

            if ($targetAction === EnforcementAction::TemporaryRestriction && ($capabilities === [] || $endsAt === null)) {
                throw new ConflictHttpException('Temporary restrictions require targeted capabilities and a duration.');
            }

            if ($targetAction === EnforcementAction::Suspension && $capabilities === []) {
                $capabilities = [RestrictedCapability::FullAccountAccess->value];
            }

            [$caseStatus, $userStatus] = match ($targetAction) {
                EnforcementAction::Warning => [EnforcementCaseStatus::Open, UserStatus::Warning],
                EnforcementAction::AccountReview => [EnforcementCaseStatus::UnderReview, UserStatus::UnderReview],
                EnforcementAction::TemporaryRestriction => [EnforcementCaseStatus::Restricted, UserStatus::Restricted],
                EnforcementAction::Suspension => [EnforcementCaseStatus::Suspended, UserStatus::Suspended],
            };

            $lockedCase->update([
                'severity' => $attributes['severity'],
                'status' => $caseStatus,
                'action' => $targetAction,
                'restricted_capabilities' => $capabilities,
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'handled_by' => $admin->id,
                'resolution' => $attributes['resolution'] ?? null,
                'appeal_status' => $attributes['appeal_status'] ?? $lockedCase->appeal_status,
            ]);
            $lockedCase->user()->update(['status' => $userStatus]);
            $this->audit($admin, 'enforcement.action_applied', $lockedCase, $before, $lockedCase->fresh()->toArray(), $ipAddress, $userAgent);
            $this->notifier->push(
                $lockedCase->user,
                'enforcement.action_applied',
                'Account action: '.str($targetAction->value)->replace('_', ' ')->title(),
                'An admin applied a '.str($targetAction->value)->replace('_', ' ')->lower().' to your account. You can view the case and appeal.',
                route('enforcement-cases.show', $lockedCase),
            );

            return $lockedCase->fresh();
        });
    }

    public function resolve(EnforcementCase $case, User $admin, string $resolution, ?string $ipAddress, ?string $userAgent): EnforcementCase
    {
        return DB::transaction(function () use ($case, $admin, $resolution, $ipAddress, $userAgent): EnforcementCase {
            $lockedCase = EnforcementCase::whereKey($case)->lockForUpdate()->firstOrFail();
            $before = $lockedCase->toArray();
            $lockedCase->update(['status' => EnforcementCaseStatus::Resolved, 'resolution' => $resolution, 'handled_by' => $admin->id, 'ends_at' => now()]);

            if (! $lockedCase->user->enforcementCases()->where('id', '!=', $lockedCase->id)->whereIn('status', ['OPEN', 'UNDER_REVIEW', 'RESTRICTED', 'SUSPENDED'])->exists()) {
                $lockedCase->user()->update(['status' => UserStatus::Active]);
            }

            $this->audit($admin, 'enforcement.case_resolved', $lockedCase, $before, $lockedCase->fresh()->toArray(), $ipAddress, $userAgent);

            return $lockedCase->fresh();
        });
    }

    public function nextAction(?EnforcementAction $currentAction): EnforcementAction
    {
        return match ($currentAction) {
            null => EnforcementAction::Warning,
            EnforcementAction::Warning => EnforcementAction::AccountReview,
            EnforcementAction::AccountReview => EnforcementAction::TemporaryRestriction,
            EnforcementAction::TemporaryRestriction => EnforcementAction::Suspension,
            EnforcementAction::Suspension => throw new ConflictHttpException('The enforcement case is already at suspension.'),
        };
    }

    public function reviewAppeal(EnforcementCase $case, User $admin, AppealStatus $appealStatus, string $resolution, ?string $ipAddress, ?string $userAgent): EnforcementCase
    {
        return DB::transaction(function () use ($case, $admin, $appealStatus, $resolution, $ipAddress, $userAgent): EnforcementCase {
            $lockedCase = EnforcementCase::whereKey($case)->lockForUpdate()->firstOrFail();
            abort_unless($lockedCase->appeal_status === AppealStatus::Requested, 409);
            $before = $lockedCase->toArray();
            $lockedCase->update(['appeal_status' => $appealStatus, 'resolution' => $resolution, 'handled_by' => $admin->id]);
            $this->audit($admin, 'enforcement.appeal_reviewed', $lockedCase, $before, $lockedCase->fresh()->toArray(), $ipAddress, $userAgent);
            $this->notifier->push(
                $lockedCase->user,
                'enforcement.appeal_reviewed',
                'Appeal '.str($appealStatus->value)->replace('_', ' ')->lower(),
                $resolution,
                route('enforcement-cases.show', $lockedCase),
            );

            return $lockedCase->fresh();
        });
    }

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    private function audit(User $actor, string $event, EnforcementCase $case, array $before, array $after, ?string $ipAddress, ?string $userAgent): void
    {
        AuditLog::create(['actor_id' => $actor->id, 'event' => $event, 'subject_type' => EnforcementCase::class, 'subject_id' => $case->id, 'before_json' => $before, 'after_json' => $after, 'ip_address' => $ipAddress, 'user_agent' => $userAgent]);
    }
}
