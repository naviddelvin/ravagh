<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    // آستانه امتیاز برای ارتقای نشان وفاداری (بر اساس مجموع مبلغ سفارش‌های پرداخت‌شده)
    public const LOYALTY_THRESHOLDS = [
        'gold' => 50_000_000,
        'silver' => 15_000_000,
        'bronze' => 0,
    ];

    protected $fillable = [
        'user_id', 'category_id', 'name', 'slug', 'logo', 'gallery',
        'description', 'phone', 'address', 'latitude', 'longitude',
        'working_hours', 'status', 'commission_percent', 'trial_ends_at',
        'verified_at', 'loyalty_tier', 'loyalty_points',
    ];

    protected function casts(): array
    {
        return [
            'gallery' => 'array',
            'working_hours' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'trial_ends_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // آیا این غرفه هنوز در دوره ۳ ماهه رایگان است؟
    public function isInTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    // کمیسیون مؤثر: در دوره رایگان صفر است
    public function effectiveCommissionPercent(): int
    {
        return $this->isInTrial() ? 0 : $this->commission_percent;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function followers()
    {
        return $this->hasMany(Follow::class);
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('user_id', $user->id)->exists();
    }
}
