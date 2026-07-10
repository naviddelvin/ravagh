<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Shop;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    // بررسی اعتبار کد تخفیف پیش از پرداخت
    public function check(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string']]);

        $coupon = Coupon::where('code', $data['code'])->first();
        abort_if(! $coupon || ! $coupon->isValid(), 422, 'کد تخفیف نامعتبر است.');

        return response()->json($coupon);
    }

    // ساخت کوپن توسط غرفه‌دار برای غرفه خودش
    public function store(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'unique:coupons,code'],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => ['required', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['shop_id'] = $shop->id;

        $coupon = Coupon::create($data);

        return response()->json($coupon, 201);
    }

    public function index(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403);

        return response()->json($shop->coupons()->latest()->get());
    }

    public function update(Request $request, Coupon $coupon)
    {
        abort_if($coupon->shop_id && $coupon->shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'discount_value' => ['sometimes', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $coupon->update($data);

        return response()->json($coupon);
    }
}
