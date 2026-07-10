<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Story;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // خلاصه وضعیت مالی پلتفرم (بند ۱۷ سند)
    public function dashboard()
    {
        return response()->json([
            'users_count' => User::where('role', 'user')->count(),
            'shops_count' => User::where('role', 'shop_owner')->count(),
            'active_shops_count' => \App\Models\Shop::where('status', 'active')->count(),
            'orders_total_revenue' => Order::where('is_paid', true)->sum('total_amount'),
            'orders_paid_count' => Order::where('is_paid', true)->count(),
            'stories_platform_income' => Story::sum('platform_share'),
            'stories_reward_pool_total' => Story::sum('reward_pool_share'),
            'pending_withdraws' => WithdrawRequest::where('status', 'pending')->count(),
            'pending_withdraws_amount' => WithdrawRequest::where('status', 'pending')->sum('amount'),
        ]);
    }

    // گزارش درآمد استوری در بازه زمانی
    public function storyIncome(Request $request)
    {
        $query = Story::query();

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        return response()->json([
            'total_paid' => $query->sum('amount_paid'),
            'platform_income' => $query->sum('platform_share'),
            'reward_pool' => $query->sum('reward_pool_share'),
            'stories_count' => $query->count(),
        ]);
    }
}
