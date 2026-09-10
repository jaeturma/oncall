<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Database\Factories\VerificationRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'status', 'reviewed_by', 'reviewed_at', 'notes'])]
class VerificationRecord extends Model
{
    /** @use HasFactory<VerificationRecordFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => VerificationStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
