<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\JobStatusLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'from_status', 'to_status', 'changed_by', 'notes'])]
class JobStatusLog extends Model
{
    /** @use HasFactory<JobStatusLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['from_status' => JobStatus::class, 'to_status' => JobStatus::class];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
