<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Setting;

class CashbackService
{
    // درصد پیش‌فرض بازگشت اعتبار در صورت نبود تنظیم دستی در جدول settings
    private const DEFAULT_PERCENT = 5;

    // اعطای اعتبار کیف پول پس از تکمیل سفارش یا رزرو خدمت (فقط یک‌بار برای هر مورد)
    // مطابق مدل «بازگشت اعتبار خرید»: اعتبار فقط داخل رواق قابل خرج است.
    public function applyForPayable(Order|Booking $payable): void
    {
        if ($payable->cashback_applied || ! $payable->is_paid) {
            return;
        }

        $percent = (int) Setting::get('cashback_percent', self::DEFAULT_PERCENT);
        $amount = (int) round($payable->total_amount * $percent / 100);

        if ($amount > 0) {
            $wallet = $payable->user->wallet()->firstOrCreate([]);
            $wallet->credit(
                amount: $amount,
                source: 'reward',
                description: "بازگشت {$percent}٪ اعتبار خرید " . $payable->referenceLabel(),
                reference: $payable
            );
        }

        $payable->update(['cashback_applied' => true]);
    }
}
