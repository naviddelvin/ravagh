<?php

namespace App\Services;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OtpService
{
    private const EXPIRY_MINUTES = 2;
    private const CODE_LENGTH = 5;

    // تولید و ارسال کد تایید به شماره موبایل
    public function send(string $mobile): OtpCode
    {
        $code = (string) random_int(
            (int) str_pad('1', self::CODE_LENGTH, '0'),
            (int) str_pad('9', self::CODE_LENGTH, '9')
        );

        $otp = OtpCode::create([
            'mobile' => $mobile,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        // TODO: اتصال به سرویس پیامکی واقعی (مثلاً کاوه‌نگار / ملی پیامک)
        Log::info("OTP برای {$mobile}: {$code}");

        return $otp;
    }

    // بررسی صحت کد وارد شده
    public function verify(string $mobile, string $code): bool
    {
        $otp = OtpCode::where('mobile', $mobile)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->increment('attempts');

        if (! $otp->isValid($code)) {
            return false;
        }

        $otp->update(['is_used' => true]);

        return true;
    }
}
