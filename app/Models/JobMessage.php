<?php

namespace App\Models;

use App\Enums\JobMessageType;
use App\Policies\JobMessagePolicy;
use Database\Factories\JobMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['job_id', 'sender_id', 'type', 'body'])]
#[UsePolicy(JobMessagePolicy::class)]
class JobMessage extends Model
{
    /** @use HasFactory<JobMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['type' => JobMessageType::class];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
