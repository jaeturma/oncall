<?php

namespace App\Models;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceUrgency;
use App\Policies\ServiceRequestPolicy;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['service_finder_id', 'requested_provider_id', 'service_id', 'province_id', 'municipality_id', 'title', 'description', 'urgency', 'needed_at', 'budget_min', 'budget_max', 'status', 'safety_acknowledged_at'])]
#[UsePolicy(ServiceRequestPolicy::class)]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['urgency' => ServiceUrgency::class, 'needed_at' => 'datetime', 'budget_min' => 'decimal:2', 'budget_max' => 'decimal:2', 'status' => ServiceRequestStatus::class, 'safety_acknowledged_at' => 'datetime'];
    }

    public function serviceFinder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_finder_id');
    }

    public function requestedProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function job(): HasOne
    {
        return $this->hasOne(Job::class);
    }
}
