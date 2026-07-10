<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Story;
use App\Services\StoryRewardService;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function __construct(private StoryRewardService $rewardService)
    {
    }

    // فید استوری‌های فعال برای صفحه اصلی
    public function index()
    {
        return response()->json(
            Story::where('status', 'active')
                ->where('expires_at', '>', now())
                ->with('shop:id,name,logo')
                ->latest()
                ->get()
        );
    }

    // انتشار استوری جدید توسط غرفه‌دار — پرداخت باید قبلاً از طریق کیف‌پول/درگاه انجام شده باشد
    public function store(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403, 'شما مالک این غرفه نیستید.');

        $data = $request->validate([
            'media_url' => ['required', 'string'],
            'media_type' => ['required', 'in:image,video'],
            'amount_paid' => ['required', 'integer', 'in:' . implode(',', Story::TARIFFS)],
        ]);

        // کسر مبلغ از کیف پول غرفه‌دار برای انتشار استوری
        $wallet = $request->user()->wallet()->firstOrCreate([]);
        $wallet->debit(
            amount: $data['amount_paid'],
            source: 'ad_purchase',
            description: 'خرید انتشار استوری تبلیغاتی'
        );

        $story = Story::createWithSplit($shop->id, $data['media_url'], $data['media_type'], $data['amount_paid']);

        return response()->json($story, 201);
    }

    // ثبت تعامل کاربر (مشاهده کامل/لایک/اشتراک‌گذاری/کامنت) و اعطای پاداش خودکار
    public function interact(Request $request, Story $story)
    {
        $data = $request->validate([
            'watched_full' => ['sometimes', 'boolean'],
            'liked' => ['sometimes', 'boolean'],
            'shared' => ['sometimes', 'boolean'],
            'commented' => ['sometimes', 'boolean'],
        ]);

        $view = $this->rewardService->recordInteraction(
            $story,
            $request->user(),
            $data,
            $request->ip(),
            $request->header('X-Device-Id')
        );

        return response()->json($view);
    }

    public function click(Story $story)
    {
        $this->rewardService->recordClick($story);

        return response()->json(['message' => 'ثبت شد.']);
    }

    // آمار برای غرفه‌دار (بازدید، کلیک، تعامل، تمدید)
    public function stats(Request $request, Story $story)
    {
        abort_if($story->shop->user_id !== $request->user()->id, 403);

        return response()->json([
            'views' => $story->views_count,
            'clicks' => $story->clicks_count,
            'likes' => $story->likes_count,
            'shares' => $story->shares_count,
            'comments' => $story->comments_count,
            'status' => $story->status,
            'expires_at' => $story->expires_at,
        ]);
    }

    // تمدید استوری (پرداخت مجدد تعرفه و تمدید ۲۴ ساعت)
    public function renew(Request $request, Story $story)
    {
        abort_if($story->shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'amount_paid' => ['required', 'integer', 'in:' . implode(',', Story::TARIFFS)],
        ]);

        $wallet = $request->user()->wallet()->firstOrCreate([]);
        $wallet->debit($data['amount_paid'], 'ad_purchase', 'تمدید استوری');

        $story->update([
            'amount_paid' => $story->amount_paid + $data['amount_paid'],
            'platform_share' => $story->platform_share + (int) round($data['amount_paid'] * Story::PLATFORM_SHARE_PERCENT / 100),
            'reward_pool_share' => $story->reward_pool_share + (int) round($data['amount_paid'] * Story::REWARD_POOL_PERCENT / 100),
            'status' => 'active',
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json($story);
    }
}
