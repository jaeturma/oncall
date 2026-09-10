<?php

namespace App\Services;

use App\Enums\DisputeCategory;
use App\Enums\DisputeStatus;
use App\Enums\EnforcementCaseStatus;
use App\Enums\JobPaymentStatus;
use App\Enums\JobStatus;
use App\Enums\ViolationSeverity;
use App\Models\AuditLog;
use App\Models\Dispute;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DisputeService
{
    public function __construct(private readonly JobPaymentService $jobPayments) {}

    public function open(Job $job, User $raiser, DisputeCategory $category, string $description): Dispute
    {
        return DB::transaction(function () use ($job, $raiser, $category, $description): Dispute {
            $lockedJob = Job::whereKey($job)->lockForUpdate()->firstOrFail();

            if (! in_array($raiser->id, [$lockedJob->service_finder_id, $lockedJob->provider_id], true)) {
                throw new ConflictHttpException('Only a participant can dispute this job.');
            }

            if ($lockedJob->dispute()->exists() || in_array($lockedJob->status, [JobStatus::Cancelled, JobStatus::Disputed], true)) {
                throw new ConflictHttpException('This job cannot be disputed.');
            }

            $dispute = Dispute::create([
                'job_id' => $lockedJob->id,
                'raised_by' => $raiser->id,
                'against_user_id' => $raiser->id === $lockedJob->service_finder_id ? $lockedJob->provider_id : $lockedJob->service_finder_id,
                'category' => $category,
                'description' => $description,
                'status' => DisputeStatus::Open,
                'job_prior_status' => $lockedJob->status,
            ]);

            $lockedJob->update(['status' => JobStatus::Disputed]);
            $lockedJob->statusLogs()->create(['from_status' => $dispute->job_prior_status, 'to_status' => JobStatus::Disputed, 'changed_by' => $raiser->id, 'notes' => 'Dispute opened: '.$category->value]);

            $this->audit($raiser, 'dispute.opened', $dispute, null);

            return $dispute;
        });
    }

    public function withdraw(Dispute $dispute, User $raiser): Dispute
    {
        return DB::transaction(function () use ($dispute, $raiser): Dispute {
            $locked = Dispute::whereKey($dispute)->lockForUpdate()->firstOrFail();

            if ($locked->raised_by !== $raiser->id || ! $locked->isOpen()) {
                throw new ConflictHttpException('This dispute can no longer be withdrawn.');
            }

            $before = $locked->toArray();
            $this->restoreJob($locked);
            $locked->update(['status' => DisputeStatus::Withdrawn, 'resolved_at' => now()]);
            $this->audit($raiser, 'dispute.withdrawn', $locked, $before);

            return $locked;
        });
    }

    public function startReview(Dispute $dispute, User $admin): Dispute
    {
        $before = $dispute->toArray();
        $dispute->update(['status' => DisputeStatus::UnderReview]);
        $this->audit($admin, 'dispute.review_started', $dispute, $before);

        return $dispute;
    }

    /**
     * @param  array{action: 'uphold'|'reject'|'partial', resolution: string, refund_amount?: string|null, open_enforcement?: bool, enforce_against?: 'raiser'|'respondent'}  $data
     */
    public function resolve(Dispute $dispute, User $admin, array $data): Dispute
    {
        return DB::transaction(function () use ($dispute, $admin, $data): Dispute {
            $locked = Dispute::whereKey($dispute)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new ConflictHttpException('This dispute is already resolved.');
            }

            $before = $locked->toArray();
            $payment = $locked->job->jobPayment;

            $status = match ($data['action']) {
                'uphold' => $this->uphold($locked, $payment, $admin),
                'reject' => $this->reject($locked),
                'partial' => $this->partial($locked, $payment, $admin, $data['refund_amount'] ?? '0'),
            };

            $locked->update([
                'status' => $status,
                'resolution' => $data['resolution'],
                'refund_amount' => $data['action'] === 'partial' ? ($data['refund_amount'] ?? null) : null,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);

            if (($data['open_enforcement'] ?? false) === true) {
                $this->openEnforcement($locked, $data['enforce_against'] ?? 'respondent');
            }

            $this->audit($admin, 'dispute.resolved', $locked, $before);

            return $locked;
        });
    }

    private function uphold(Dispute $dispute, ?JobPayment $payment, User $admin): DisputeStatus
    {
        if ($payment !== null && $payment->status !== JobPaymentStatus::Reversed) {
            $this->jobPayments->reverse($payment, $admin, 'Dispute upheld');
        }

        $dispute->job->update(['status' => JobStatus::Cancelled, 'cancelled_at' => now()]);
        $dispute->job->statusLogs()->create(['from_status' => JobStatus::Disputed, 'to_status' => JobStatus::Cancelled, 'changed_by' => $admin->id, 'notes' => 'Dispute upheld']);

        return DisputeStatus::Upheld;
    }

    private function reject(Dispute $dispute): DisputeStatus
    {
        $this->restoreJob($dispute);

        return DisputeStatus::Rejected;
    }

    private function partial(Dispute $dispute, ?JobPayment $payment, User $admin, string $refundAmount): DisputeStatus
    {
        if ($payment !== null) {
            $this->jobPayments->applyRefund($payment, $refundAmount, $admin, 'Dispute partially upheld');
        }

        $this->restoreJob($dispute);

        return DisputeStatus::PartiallyUpheld;
    }

    private function restoreJob(Dispute $dispute): void
    {
        $dispute->job->update(['status' => $dispute->job_prior_status]);
        $dispute->job->statusLogs()->create(['from_status' => JobStatus::Disputed, 'to_status' => $dispute->job_prior_status, 'changed_by' => $dispute->resolved_by ?? $dispute->raised_by, 'notes' => 'Dispute closed without upholding']);
    }

    private function openEnforcement(Dispute $dispute, string $against): void
    {
        $targetId = $against === 'raiser' ? $dispute->raised_by : $dispute->against_user_id;

        $case = EnforcementCase::create([
            'user_id' => $targetId,
            'related_job_id' => $dispute->job_id,
            'violation_category' => $dispute->category->toReportCategory(),
            'severity' => ViolationSeverity::Low,
            'status' => EnforcementCaseStatus::Open,
        ]);

        $dispute->update(['enforcement_case_id' => $case->id]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function audit(User $actor, string $event, Dispute $dispute, ?array $before): void
    {
        AuditLog::create([
            'actor_id' => $actor->id,
            'event' => $event,
            'subject_type' => Dispute::class,
            'subject_id' => $dispute->id,
            'before_json' => $before,
            'after_json' => $dispute->fresh()->toArray(),
        ]);
    }
}
