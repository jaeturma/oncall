<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\VerificationStatus;
use Database\Factories\ProviderDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'document_type', 'private_path', 'status', 'reviewed_by', 'reviewed_at', 'expires_at', 'notes'])]
#[Hidden(['private_path'])]
class ProviderDocument extends Model
{
    /** @use HasFactory<ProviderDocumentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['document_type' => DocumentType::class, 'status' => VerificationStatus::class, 'reviewed_at' => 'datetime', 'expires_at' => 'datetime'];
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
