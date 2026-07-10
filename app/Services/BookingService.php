<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Str;

class BookingService
{
    // ثبت رزرو نوبت برای یک خدمت مشخص در زمان انتخابی کاربر
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

        if ($paymentMethod === 'wallet') {
            $this->payWithWallet($booking);
        }

        return $booking;
    }

    // پرداخت رزرو از موجودی کیف پول
    public function payWithWallet(Booking $booking): void
    {
        $wallet = $booking->user->wallet()->firstOrCreate([]);

        $wallet->debit(
            amount: $booking->total_amount,
            source: 'service_payment',
            description: "پرداخت " . $booking->referenceLabel(),
            reference: $booking
        );

        $booking->update(['is_paid' => true, 'status' => 'confirmed']);
    }

    // تایید پرداخت درگاه بانکی (Callback)
    public function confirmGatewayPayment(Booking $booking, string $gatewayRef): void
    {
        $booking->update(['is_paid' => true, 'status' => 'confirmed']);

        $booking->payments()->create([
            'user_id' => $booking->user_id,
            'amount' => $booking->total_amount,
            'method' => 'gateway',
            'status' => 'success',
            'gateway_ref' => $gatewayRef,
        ]);
    }
}
