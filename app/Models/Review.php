<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Policies\ReviewPolicy;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['job_id', 'reviewer_id', 'reviewee_id', 'rating', 'comment', 'status', 'response', 'responded_at', 'moderated_at'])]
#[UsePolicy(ReviewPolicy::class)]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'responded_at' => 'datetime',
            'moderated_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    /**
     * Only published reviews count toward public listings and reputation
     * aggregates — hidden/removed/withdrawn are excluded consistently
     * everywhere via this single scope (Phase P §24).
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ReviewStatus::Published);
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}
