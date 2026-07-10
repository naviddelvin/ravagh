<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class WithdrawRequestController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->withdrawRequests()->latest()->paginate(20)
        );
    }

    // فقط غرفه‌داران/ارائه‌دهندگان خدمات مجاز به درخواست تسویه‌اند (بند ۱۳ سند)
    public function store(Request $request)
    {
        abort_if($request->user()->role === 'user', 403, 'کاربران عادی امکان درخواست تسویه ندارند.');

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:10000'],
            'bank_info' => ['required', 'array'],
            'bank_info.sheba' => ['required', 'string'],
            'bank_info.owner_name' => ['required', 'string'],
        ]);

        $wallet = $request->user()->wallet()->firstOrCreate([]);
        abort_if($wallet->balance < $data['amount'], 422, 'موجودی کیف پول کافی نیست.');

        // مبلغ رزرو می‌شود تا تایید/رد توسط مدیر
        $wallet->debit($data['amount'], 'withdraw', 'درخواست تسویه حساب');

        $withdraw = $request->user()->withdrawRequests()->create([
            'amount' => $data['amount'],
            'bank_info' => $data['bank_info'],
            'status' => 'pending',
        ]);

        return response()->json($withdraw, 201);
    }
}
