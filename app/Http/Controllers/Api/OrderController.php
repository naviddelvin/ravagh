<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CashbackService;
use App\Services\LoyaltyService;
use App\Services\OrderService;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private CashbackService $cashbackService,
        private ReferralService $referralService,
        private LoyaltyService $loyaltyService,
    ) {
    }

    // لیست سفارش‌های کاربر با امکان پیگیری وضعیت (بند ۹ سند)
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->orders()->with('items', 'shop')->latest()->paginate(20)
        );
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        return response()->json($order->load('items.product', 'shop', 'coupon'));
    }

    // ثبت سفارش از روی سبد خرید فعلی کاربر
    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:wallet,gateway'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $orders = $this->orderService->checkout(
            $request->user(),
            $data['payment_method'],
            $data['coupon_code'] ?? null
        );

        return response()->json($orders, 201);
    }

    // بروزرسانی وضعیت توسط غرفه‌دار (آماده‌سازی/ارسال/تحویل/لغو)
    public function confirmPayment(Request $request, Order $order)
    {
        abort_if($order->user_id !== $request->user()->id, 403);
        $data = $request->validate(['gateway_ref' => ['required', 'string']]);
        $this->orderService->confirmGatewayPayment($order, $data['gateway_ref']);
        return response()->json($order->fresh());
    }

    public function updateStatus(Request $request, Order $order)
    {
    public function shopOrders(Request $request, \App\Models\Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403);

        return response()->json($shop->orders()->with('items', 'user:id,name,mobile')->latest()->paginate(20));
    }

        abort_if($order->shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $order->update($data);

        // پس از تحویل قطعی سفارش: بازگشت اعتبار خرید، پاداش معرفی و بازمحاسبه نشان وفاداری غرفه
        if ($data['status'] === 'delivered') {
            $this->cashbackService->applyForPayable($order);
            $this->referralService->rewardIfEligible($order);
            $this->loyaltyService->recalculate($order->shop);
        }

        return response()->json($order);
    }
}
