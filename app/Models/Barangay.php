<?php

namespace App\Models;

use Database\Factories\BarangayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['municipality_id', 'psgc_code', 'name', 'latitude', 'longitude'])]
class Barangay extends Model
{
    /** @use HasFactory<BarangayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['latitude' => 'decimal:6', 'longitude' => 'decimal:6'];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }
}
