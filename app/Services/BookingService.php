<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

class BookingService
{
    private const DEFAULT_MAX_WALLET_PERCENT = 50;

    public function book(User $user, Service $service, string $scheduledAt, string $paymentMethod): Booking
    {
        abort_if(! $service->is_active, 422, 'این خدمت در حال حاضر قابل رزرو نیست.');

        $total = $service->price;

        [$walletAmount, $gatewayAmount] = $paymentMethod === 'wallet'
            ? $this->splitAmount($total, $user)
            : [0, $total];

        $booking = Booking::create([
            'user_id' => $user->id,
            'shop_id' => $service->shop_id,
            'service_id' => $service->id,
            'booking_number' => 'RVG-B-' . Str::upper(Str::random(10)),
            'service_name' => $service->name,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $service->duration_minutes,
            'total_amount' => $total,
            'wallet_amount' => $walletAmount,
            'gateway_amount' => $gatewayAmount,
            'payment_method' => $paymentMethod,
            'is_paid' => false,
            'status' => 'pending',
        ]);

        if ($walletAmount > 0) {
            $wallet = $user->wallet()->firstOrCreate([]);
            $wallet->debit(
                $walletAmount,
                'service_payment',
                'پرداخت بخشی از ' . $booking->referenceLabel() . ' از کیف‌پول',
                $booking
            );
        }

        if ($gatewayAmount === 0) {
            $booking->update(['is_paid' => true, 'status' => 'confirmed']);
        }

        return $booking;
    }

    private function splitAmount(int $total, User $user): array
    {
        $wallet = $user->wallet()->firstOrCreate([]);
        $maxPercent = (int) Setting::get('max_wallet_payment_percent', self::DEFAULT_MAX_WALLET_PERCENT);
        $maxAllowed = (int) floor($total * $maxPercent / 100);
        $walletAmount = min($wallet->balance, $maxAllowed);
        $gatewayAmount = $total - $walletAmount;

        return [$walletAmount, $gatewayAmount];
    }

    public function confirmGatewayPayment(Booking $booking, string $gatewayRef): void
    {
        $booking->update(['is_paid' => true, 'status' => 'confirmed']);

        $booking->payments()->create([
            'user_id' => $booking->user_id,
            'amount' => $booking->gateway_amount,
            'method' => 'gateway',
            'status' => 'success',
            'gateway_ref' => $gatewayRef,
        ]);
    }
}
