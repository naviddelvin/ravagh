<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;

class BookingService
{
    public function book(User $user, Service $service, string $scheduledAt, string $paymentMethod): Booking
    {
        abort_if(! $service->is_active, 422, 'این خدمت در حال حاضر قابل رزرو نیست.');

        $booking = Booking::create([
            'user_id' => $user->id,
            'shop_id' => $service->shop_id,
            'service_id' => $service->id,
            'booking_number' => 'RVG-B-' . Str::upper(Str::random(10)),
            'service_name' => $service->name,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $service->duration_minutes,
            'total_amount' => $service->price,
            'payment_method' => $paymentMethod,
            'is_paid' => false,
            'status' => 'pending',
        ]);

        $this->processPayment($booking, $paymentMethod);

        return $booking;
    }

    public function processPayment(Booking $booking, string $paymentMethod): void
    {
        $capPercent = $paymentMethod === 'gateway'
            ? 0
            : (int) Setting::get('wallet_payment_max_percent', 50);

        $wallet = $booking->user->wallet()->firstOrCreate([]);

        $walletCapAmount = (int) floor($booking->total_amount * $capPercent / 100);
        $walletAmount = min($walletCapAmount, $wallet->balance, $booking->total_amount);
        $gatewayAmount = $booking->total_amount - $walletAmount;

        if ($walletAmount > 0) {
            $wallet->debit(
                amount: $walletAmount,
                source: 'service_payment',
                description: 'پرداخت بخشی از ' . $booking->referenceLabel() . ' از کیف پول',
                reference: $booking
            );
        }

        $booking->update([
            'wallet_amount' => $walletAmount,
            'gateway_amount' => $gatewayAmount,
        ]);

        if ($gatewayAmount <= 0) {
            $booking->update(['is_paid' => true, 'status' => 'confirmed']);

            return;
        }

        $booking->payments()->create([
            'user_id' => $booking->user_id,
            'amount' => $gatewayAmount,
            'method' => 'gateway',
            'status' => 'pending',
        ]);

        $booking->update(['status' => 'awaiting_payment']);
    }

    public function confirmGatewayPayment(Booking $booking, string $gatewayRef): void
    {
        $payment = $booking->payments()->where('status', 'pending')->latest()->first();

        if ($payment) {
            $payment->update(['status' => 'success', 'gateway_ref' => $gatewayRef]);
        } else {
            $booking->payments()->create([
                'user_id' => $booking->user_id,
                'amount' => $booking->gateway_amount,
                'method' => 'gateway',
                'status' => 'success',
                'gateway_ref' => $gatewayRef,
            ]);
        }

        $booking->update(['is_paid' => true, 'status' => 'confirmed']);
    }
}
