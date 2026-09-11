<?php

namespace App\Models;

use Database\Factories\MunicipalityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['province_id', 'psgc_code', 'name', 'type', 'latitude', 'longitude'])]
class Municipality extends Model
{
    /** @use HasFactory<MunicipalityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['latitude' => 'decimal:6', 'longitude' => 'decimal:6'];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
