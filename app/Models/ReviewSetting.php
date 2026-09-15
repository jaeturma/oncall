<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding admin-configurable review policy.
 * Always go through {@see current()} rather than querying the table
 * directly — mirrors {@see SmsSetting} / {@see LocationSetting}.
 */
#[Fillable([
    'reviews_enabled', 'review_window_days', 'comment_required', 'max_comment_length',
    'provider_response_enabled', 'response_max_length', 'reviews_per_page',
])]
class ReviewSetting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'reviews_enabled' => true,
        'review_window_days' => 30,
        'comment_required' => false,
        'max_comment_length' => 2000,
        'provider_response_enabled' => true,
        'response_max_length' => 1000,
        'reviews_per_page' => 10,
    ];

    protected function casts(): array
    {
        return [
            'reviews_enabled' => 'boolean',
            'comment_required' => 'boolean',
            'provider_response_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
