<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use App\Services\BookingService;
use App\Services\CashbackService;
use App\Services\LoyaltyService;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private CashbackService $cashbackService,
        private ReferralService $referralService,
        private LoyaltyService $loyaltyService,
    ) {
    }

    // لیست نوبت‌های مشتری با امکان پیگیری وضعیت
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->bookings()->with('service', 'shop')->latest()->paginate(20)
        );
    }

    public function show(Request $request, Booking $booking)
    {
        abort_if($booking->user_id !== $request->user()->id, 403);

        return response()->json($booking->load('service', 'shop'));
    }

    // رزرو نوبت جدید برای یک خدمت
    public function store(Request $request, Service $service)
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'payment_method' => ['required', 'in:wallet,gateway'],
        ]);

        $booking = $this->bookingService->book(
            $request->user(),
            $service,
            $data['scheduled_at'],
            $data['payment_method']
        );

        return response()->json($booking, 201);
    }

    // لیست نوبت‌های یک غرفه برای غرفه‌دار
    public function shopBookings(Request $request, \App\Models\Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403);

        return response()->json(
            $shop->bookings()->with('service', 'user:id,name,mobile')->latest()->paginate(20)
        );
    }

    // تغییر وضعیت نوبت توسط غرفه‌دار (تایید/انجام‌شده/لغو)
    public function updateStatus(Request $request, Booking $booking)
    {
        abort_if($booking->shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:confirmed,completed,cancelled'],
        ]);

        $booking->update($data);

        // پس از انجام قطعی خدمت: بازگشت اعتبار خرید، پاداش معرفی و بازمحاسبه نشان وفاداری غرفه
        if ($data['status'] === 'completed') {
            $this->cashbackService->applyForPayable($booking);
            $this->referralService->rewardIfEligible($booking);
            $this->loyaltyService->recalculate($booking->shop);
        }

        return response()->json($booking);
    }
}
