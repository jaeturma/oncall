<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['actor_id', 'event', 'subject_type', 'subject_id', 'before_json', 'after_json', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['before_json' => 'array', 'after_json' => 'array'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
