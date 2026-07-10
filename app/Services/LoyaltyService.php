<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Shop;

class LoyaltyService
{
    // پاداش استوری رایگان به ازای ارتقای نشان (به‌صورت اعتبار کیف‌پول برای غرفه‌دار)
    private const UPGRADE_REWARDS = [
        'silver' => 500_000,   // معادل تعرفه یک استوری متوسط
        'gold' => 1_000_000,
    ];

    // بازمحاسبه نشان وفاداری بر اساس مجموع درآمد پرداخت‌شده غرفه (سفارش محصول + رزرو خدمت)
    public function recalculate(Shop $shop): void
    {
        $totalRevenue = $shop->orders()->where('is_paid', true)->sum('total_amount')
            + $shop->bookings()->where('is_paid', true)->sum('total_amount');

        $goldThreshold = (int) Setting::get('loyalty_gold_threshold', Shop::LOYALTY_THRESHOLDS['gold']);
        $silverThreshold = (int) Setting::get('loyalty_silver_threshold', Shop::LOYALTY_THRESHOLDS['silver']);

        $newTier = match (true) {
            $totalRevenue >= $goldThreshold => 'gold',
            $totalRevenue >= $silverThreshold => 'silver',
            default => 'bronze',
        };

        $previousTier = $shop->loyalty_tier;

        $shop->update([
            'loyalty_tier' => $newTier,
            'loyalty_points' => $totalRevenue,
        ]);

        // فقط در صورت ارتقای واقعی نشان، پاداش استوری رایگان اعطا می‌شود
        if ($newTier !== $previousTier && isset(self::UPGRADE_REWARDS[$newTier])) {
            $wallet = $shop->owner->wallet()->firstOrCreate([]);
            $wallet->credit(
                amount: self::UPGRADE_REWARDS[$newTier],
                source: 'reward',
                description: "پاداش ارتقا به نشان " . $this->tierLabel($newTier) . " (اعتبار انتشار استوری رایگان)",
                reference: $shop
            );
        }
    }

    public function tierLabel(string $tier): string
    {
        return match ($tier) {
            'gold' => 'طلایی',
            'silver' => 'نقره‌ای',
            default => 'برنزی',
        };
    }
}
