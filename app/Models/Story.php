<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Story extends Model
{
    // تعرفه‌های مجاز انتشار استوری (تومان)
    public const TARIFFS = [100_000, 500_000, 1_000_000, 10_000_000];

    public const PLATFORM_SHARE_PERCENT = 30;
    public const REWARD_POOL_PERCENT = 70;

    protected $fillable = [
        'shop_id', 'media_url', 'media_type', 'amount_paid',
        'platform_share', 'reward_pool_share', 'views_count',
        'clicks_count', 'likes_count', 'shares_count', 'comments_count',
        'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    // ساخت استوری جدید همراه با محاسبه خودکار تقسیم درآمد ۷۰/۳۰
    public static function createWithSplit(int $shopId, string $mediaUrl, string $mediaType, int $amountPaid): self
    {
        return self::create([
            'shop_id' => $shopId,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'amount_paid' => $amountPaid,
            'platform_share' => (int) round($amountPaid * self::PLATFORM_SHARE_PERCENT / 100),
            'reward_pool_share' => (int) round($amountPaid * self::REWARD_POOL_PERCENT / 100),
            'status' => 'active',
            'expires_at' => Carbon::now()->addHours(24),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function views()
    {
        return $this->hasMany(StoryView::class);
    }
}
