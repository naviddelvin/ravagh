<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Order;
use App\Models\ReferralReward;
use App\Models\Setting;

class ReferralService
{
    private const DEFAULT_REFERRER_AMOUNT = 50_000;
    private const DEFAULT_REFERRED_AMOUNT = 50_000;

    // در صورتی که این سفارش/رزرو، اولین خرید پرداخت‌شده‌ی یک کاربر معرفی‌شده باشد
    // (چه خرید محصول، چه رزرو خدمت)، هم معرف و هم کاربر معرفی‌شده اعتبار می‌گیرند (هرکاربر فقط یک‌بار).
    public function rewardIfEligible(Order|Booking $payable): void
    {
        $user = $payable->user;

        if (! $user->referred_by || ! $payable->is_paid) {
            return;
        }

        // قبلاً برای این کاربر پاداش معرفی صادر شده؟
        if (ReferralReward::where('referred_id', $user->id)->exists()) {
            return;
        }

        // آیا این اولین خرید پرداخت‌شده کاربر است؟ (مجموع سفارش‌ها و رزروهای پرداخت‌شده)
        $paidCount = $user->orders()->where('is_paid', true)->count()
            + $user->bookings()->where('is_paid', true)->count();

        if ($paidCount > 1) {
            return;
        }

        $referrer = $user->referrer;
        if (! $referrer) {
            return;
        }

        $referrerAmount = (int) Setting::get('referral_referrer_amount', self::DEFAULT_REFERRER_AMOUNT);
        $referredAmount = (int) Setting::get('referral_referred_amount', self::DEFAULT_REFERRED_AMOUNT);

        $referrerWallet = $referrer->wallet()->firstOrCreate([]);
        $referrerWallet->credit($referrerAmount, 'reward', "پاداش معرفی کاربر {$user->name}", $payable);

        $referredWallet = $user->wallet()->firstOrCreate([]);
        $referredWallet->credit($referredAmount, 'reward', 'پاداش خرید اول با کد معرف', $payable);

        ReferralReward::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
            'trigger_type' => get_class($payable),
            'trigger_id' => $payable->id,
            'referrer_amount' => $referrerAmount,
            'referred_amount' => $referredAmount,
        ]);
    }
}
