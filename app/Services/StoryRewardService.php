<?php

namespace App\Services;

use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StoryRewardService
{
    // مبلغ پاداش هر نوع تعامل، به عنوان درصدی از استخر پاداش استوری (reward_pool_share)
    // این نسبت‌ها قابل تنظیم در جدول settings هستند؛ در اینجا مقادیر پیش‌فرض آمده است.
    private const WEIGHT_VIEW = 40;    // مشاهده کامل
    private const WEIGHT_LIKE = 15;
    private const WEIGHT_SHARE = 25;
    private const WEIGHT_COMMENT = 20;

    // ثبت تعامل کاربر با استوری و اعطای پاداش (در صورت نبود تقلب)
    public function recordInteraction(Story $story, User $user, array $interaction, ?string $ip = null, ?string $deviceId = null): StoryView
    {
        abort_if($story->isExpired(), 422, 'این استوری منقضی شده است.');

        return DB::transaction(function () use ($story, $user, $interaction, $ip, $deviceId) {
            $view = StoryView::firstOrCreate(
                ['story_id' => $story->id, 'user_id' => $user->id],
                ['ip_address' => $ip, 'device_id' => $deviceId]
            );

            // ضدتقلب پایه: اگر IP/دستگاه با تعداد غیرعادی از کاربران متفاوت در بازه کوتاه تکرار شود
            // باید در لایه AntiFraud (بند ۱۶ سند) بررسی و مسدود شود؛ اینجا فقط یک‌بار در ازای هر کاربر مجاز است.
            if ($view->reward_granted) {
                return $view; // قبلاً پاداش گرفته، دوباره پرداخت نمی‌شود
            }

            $rewardWeight = 0;

            if (! empty($interaction['watched_full']) && ! $view->watched_full) {
                $view->watched_full = true;
                $rewardWeight += self::WEIGHT_VIEW;
                $story->increment('views_count');
            }
            if (! empty($interaction['liked']) && ! $view->liked) {
                $view->liked = true;
                $rewardWeight += self::WEIGHT_LIKE;
                $story->increment('likes_count');
            }
            if (! empty($interaction['shared']) && ! $view->shared) {
                $view->shared = true;
                $rewardWeight += self::WEIGHT_SHARE;
                $story->increment('shares_count');
            }
            if (! empty($interaction['commented']) && ! $view->commented) {
                $view->commented = true;
                $rewardWeight += self::WEIGHT_COMMENT;
                $story->increment('comments_count');
            }

            if ($rewardWeight > 0) {
                $rewardAmount = (int) round($story->reward_pool_share * $rewardWeight / 100);

                if ($rewardAmount > 0) {
                    $wallet = $user->wallet()->firstOrCreate([]);
                    $wallet->credit(
                        amount: $rewardAmount,
                        source: 'reward',
                        description: "پاداش تعامل با استوری #{$story->id}",
                        reference: $story
                    );
                }

                $view->reward_granted = true;
            }

            $view->save();

            return $view;
        });
    }

    // ثبت صرف کلیک روی استوری (بدون پاداش مستقیم، فقط آمار برای غرفه‌دار)
    public function recordClick(Story $story): void
    {
        $story->increment('clicks_count');
    }
}
