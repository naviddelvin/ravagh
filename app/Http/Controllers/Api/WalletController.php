<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        $wallet = $request->user()->wallet()->firstOrCreate([]);

        return response()->json($wallet);
    }

    // تاریخچه تراکنش‌ها
    public function transactions(Request $request)
    {
        $wallet = $request->user()->wallet()->firstOrCreate([]);

        return response()->json($wallet->transactions()->latest()->paginate(30));
    }

    // شارژ مستقیم کیف پول از طریق درگاه بانکی
    // این متد فقط رکورد پرداخت pending می‌سازد؛ تایید نهایی در Callback درگاه با
    // فراخوانی confirmCharge انجام می‌شود.
    public function requestCharge(Request $request)
    {
        $data = $request->validate(['amount' => ['required', 'integer', 'min:1000']]);

        $payment = $request->user()->payments()->create([
            'amount' => $data['amount'],
            'method' => 'gateway',
            'status' => 'pending',
        ]);

        // TODO: اتصال به درگاه بانکی واقعی (زرین‌پال / آی‌دی‌پی و ...) و بازگرداندن لینک پرداخت
        return response()->json(['payment_id' => $payment->id, 'message' => 'به درگاه پرداخت منتقل شوید.']);
    }

    // Callback درگاه پس از پرداخت موفق شارژ کیف پول
    public function confirmCharge(Request $request, int $paymentId)
    {
        $payment = $request->user()->payments()->findOrFail($paymentId);
        abort_if($payment->status === 'success', 422, 'این تراکنش قبلاً تایید شده است.');

        $payment->update(['status' => 'success', 'gateway_ref' => $request->input('gateway_ref')]);

        $wallet = $request->user()->wallet()->firstOrCreate([]);
        $wallet->credit($payment->amount, 'charge', 'شارژ مستقیم کیف پول', $payment);

        return response()->json($wallet);
    }
}
