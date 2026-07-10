<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'shop_id', 'code', 'discount_type', 'discount_value',
        'usage_limit', 'used_count', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $amount): int
    {
        if ($this->discount_type === 'percent') {
            return (int) round($amount * $this->discount_value / 100);
        }

        return min($this->discount_value, $amount);
    }
}
