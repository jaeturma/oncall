<?php

namespace App\Models;

use Database\Factories\ProvinceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['psgc_code', 'name', 'latitude', 'longitude'])]
class Province extends Model
{
    /** @use HasFactory<ProvinceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['latitude' => 'decimal:6', 'longitude' => 'decimal:6'];
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }
}
