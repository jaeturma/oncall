<?php

namespace App\Models;

use Database\Factories\ProviderServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['provider_profile_id', 'service_id', 'experience_text', 'rate_type', 'rate_from', 'rate_to', 'active'])]
class ProviderService extends Model
{
    /** @use HasFactory<ProviderServiceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'rate_from' => 'decimal:2', 'rate_to' => 'decimal:2'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_profile_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
