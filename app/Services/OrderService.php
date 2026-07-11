<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderService
{
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

                $order = Order::create([
                    'user_id' => $user->id,
                    'shop_id' => $shopId,
                    'coupon_id' => $coupon?->id,
                    'order_number' => 'RVG-' . Str::upper(Str::random(10)),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
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

                $this->processPayment($order, $paymentMethod);

                $orders[] = $order;
            }

            $coupon?->increment('used_count');
            $user->cart->items()->delete();
        });

        return $orders;
    }

    public function processPayment(Order $order, string $paymentMethod): void
    {
        $capPercent = $paymentMethod === 'gateway'
            ? 0
            : (int) Setting::get('wallet_payment_max_percent', 50);

        $wallet = $order->user->wallet()->firstOrCreate([]);

        $walletCapAmount = (int) floor($order->total_amount * $capPercent / 100);
        $walletAmount = min($walletCapAmount, $wallet->balance, $order->total_amount);
        $gatewayAmount = $order->total_amount - $walletAmount;

        if ($walletAmount > 0) {
            $wallet->debit(
                amount: $walletAmount,
                source: 'order_payment',
                description: "پرداخت بخشی از سفارش {$order->order_number} از کیف

cat app/Services/OrderService.php | head -5


