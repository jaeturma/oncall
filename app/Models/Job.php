<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Policies\JobPolicy;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['service_request_id', 'service_finder_id', 'provider_id', 'agreed_price', 'status', 'accepted_at', 'on_the_way_at', 'started_at', 'completed_at', 'cancelled_at'])]
#[UsePolicy(JobPolicy::class)]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['agreed_price' => 'decimal:2', 'status' => JobStatus::class, 'accepted_at' => 'datetime', 'on_the_way_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function serviceFinder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_finder_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(JobStatusLog::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(JobMessage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return list<JobStatus> */
    public function allowedTransitionsFor(User $user): array
    {
        $participantActions = match ($this->status) {
            JobStatus::Accepted, JobStatus::OnTheWay, JobStatus::InProgress => [JobStatus::Cancelled, JobStatus::Disputed],
            JobStatus::Completed => [JobStatus::Disputed],
            default => [],
        };

        if ($user->id !== $this->provider_id || $user->role !== UserRole::ServiceProvider) {
            return $participantActions;
        }

        $providerProgress = match ($this->status) {
            JobStatus::Accepted => [JobStatus::OnTheWay],
            JobStatus::OnTheWay => [JobStatus::InProgress],
            JobStatus::InProgress => [JobStatus::Completed],
            default => [],
        };

        return [...$providerProgress, ...$participantActions];
    }
}
