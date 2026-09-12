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
use Illuminate\Support\Collection;

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

    /**
     * Currently valid (verified, unexpired) document types per user, in one
     * query. Used to render verification badges from real data without an
     * N+1 and without loading anything else about the user.
     *
     * @param  list<int>  $userIds
     * @return Collection<int, list<string>>
     */
    public static function verifiedTypesByUser(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return static::query()
            ->whereIn('user_id', $userIds)
            ->where('status', VerificationStatus::Verified)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get(['user_id', 'document_type'])
            ->groupBy('user_id')
            ->map(fn (Collection $documents): array => $documents->map(fn (self $document): string => $document->document_type->value)->unique()->values()->all());
    }
}
