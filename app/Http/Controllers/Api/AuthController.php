<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private OtpService $otpService)
    {
    }

    // مرحله ۱: ارسال کد تایید به موبایل (برای ثبت‌نام یا ورود)
    public function sendOtp(Request $request)
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);

        $this->otpService->send($data['mobile']);

        return response()->json([
            'message' => 'کد تایید ارسال شد.',
        ]);
    }

    // مرحله ۲: تایید کد و ورود/ثبت‌نام خودکار
    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            'code' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:100'],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
        ]);

        if (! $this->otpService->verify($data['mobile'], $data['code'])) {
            return response()->json(['message' => 'کد تایید نامعتبر یا منقضی شده است.'], 422);
        }

        $user = User::firstOrCreate(
            ['mobile' => $data['mobile']],
            [
                'name' => $data['name'] ?? 'کاربر رواق',
                'role' => 'user',
                'referral_code' => Str::upper(Str::random(8)),
                'referred_by' => $data['referral_code']
                    ? User::where('referral_code', $data['referral_code'])->value('id')
                    : null,
                'mobile_verified_at' => now(),
            ]
        );

        // اطمینان از داشتن کیف پول
        Wallet::firstOrCreate(['user_id' => $user->id]);

        $token = $user->createToken('ravagh-app')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('wallet'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'خروج با موفقیت انجام شد.']);
    }

    // درخواست تبدیل کاربر عادی به غرفه‌دار (نیازمند تایید مدیر در پنل ادمین)
    public function requestShopOwner(Request $request)
    {
        $request->user()->update(['role' => 'shop_owner']);

        return response()->json(['message' => 'نقش شما به غرفه‌دار تغییر یافت.']);
    }
}
