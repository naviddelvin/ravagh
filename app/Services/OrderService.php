<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderService
{
    // حداکثر درصدی از مبلغ خرید که مجاز است از کیف‌پول کم شود؛ باقی حتماً باید از درگاه پرداخت شود
    // تا موجودی کیف‌پول (که معمولاً از پاداش/کش‌بک است) بین چند خرید تقسیم بماند.
    // این عدد از پنل مدیریت (settings.max_wallet_payment_percent) قابل تغییر است.
    private const DEFAULT_MAX_WALLET_PERCENT = 50;

    public function checkout(User $user, string $paymentMethod, ?string $couponCode = null): array
    {
        $cart = $user->cart()->with('items.product.shop')->firstOrFail();
        abort_if($cart->items->isEmpty(), 422, 'سبد خرید خالی است.');

        $itemsByShop = $cart->items->groupBy(fn ($item) => $item->product->shop_id);

        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            abort_if(! $coupon || ! $coupon->isValid(), 422, 'کد تخفیف نامعتبر است.');
        }

        $orders = [];

        DB::transaction(function () use ($itemsByShop, $user, $paymentMethod, $coupon, &$orders) {
            foreach ($itemsByShop as $shopId => $items) {
                $subtotal = $items->sum(fn ($item) => $item->quantity * $item->product->final_price);

                $discount = 0;
                if ($coupon && (! $coupon->shop_id || $coupon->shop_id == $shopId)) {
                    $discount = $coupon->calculateDiscount($subtotal);
                }

                $total = max(0, $subtotal - $discount);

                // اگر کاربر «درگاه» را انتخاب کرده باشد، اصلاً از کیف‌پول کم نمی‌شود
                [$walletAmount, $gatewayAmount] = $paymentMethod === 'wallet'
                    ? $this->splitAmount($total, $user)
                    : [0, $total];

                $order = Order::create([
                    'user_id' => $user->id,
                    'shop_id' => $shopId,
                    'coupon_id' => $coupon?->id,
                    'order_number' => 'RVG-' . Str::upper(Str::random(10)),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'wallet_amount' => $walletAmount,
                    'gateway_amount' => $gatewayAmount,
                    'payment_method' => $paymentMethod,
                    'is_paid' => false,
                ]);

                foreach ($items as $item) {
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price' => $item->product->final_price,
                    ]);

                    $item->product->decrement('stock', $item->quantity);
                }

                if ($walletAmount > 0) {
                    $wallet = $user->wallet()->firstOrCreate([]);
                    $wallet->debit(
                        $walletAmount,
                        'order_payment',
                        'پرداخت بخشی از ' . $order->referenceLabel() . ' از کیف‌پول',
                        $order
                    );
                }

                // اگر کل مبلغ با کیف‌پول پوشش داده شد، سفارش پرداخت‌شده است؛
                // در غیر این صورت باید مابقی (gateway_amount) از درگاه تکمیل شود.
                if ($gatewayAmount === 0) {
                    $order->update(['is_paid' => true, 'status' => 'processing']);
                }

                $coupon?->increment('used_count');
                $orders[] = $order;
            }

            $user->cart->items()->delete();
        });

        return $orders;
    }

    // محاسبه سهم کیف‌پول و درگاه بر اساس سقف مجاز در تنظیمات
    private function splitAmount(int $total, User $user): array
    {
        $wallet = $user->wallet()->firstOrCreate([]);
        $maxPercent = (int) Setting::get('max_wallet_payment_percent', self::DEFAULT_MAX_WALLET_PERCENT);
        $maxAllowed = (int) floor($total * $maxPercent / 100);
        $walletAmount = min($wallet->balance, $maxAllowed);
        $gatewayAmount = $total - $walletAmount;

        return [$walletAmount, $gatewayAmount];
    }

    // تایید پرداخت درگاه برای باقی‌ماندهٔ مبلغ (gateway_amount)
    public function confirmGatewayPayment(Order $order, string $gatewayRef): void
    {
        $order->update(['is_paid' => true, 'status' => 'processing']);

        $order->payments()->create([
            'user_id' => $order->user_id,
            'amount' => $order->gateway_amount,
            'method' => 'gateway',
            'status' => 'success',
            'gateway_ref' => $gatewayRef,
        ]);
    }
}
