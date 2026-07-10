<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class WithdrawManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawRequest::query()->with('user:id,name,mobile');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->paginate(30));
    }

    // تایید نهایی و پرداخت (خارج از سیستم، توسط حسابداری انجام و اینجا فقط ثبت می‌شود)
    public function approve(Request $request, WithdrawRequest $withdrawRequest)
    {
        abort_if($withdrawRequest->status !== 'pending', 422, 'این درخواست قبلاً بررسی شده است.');

        $withdrawRequest->update([
            'status' => 'paid',
            'processed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        return response()->json($withdrawRequest);
    }

    // رد درخواست — مبلغ رزرو شده به کیف پول کاربر بازمی‌گردد
    public function reject(Request $request, WithdrawRequest $withdrawRequest)
    {
        abort_if($withdrawRequest->status !== 'pending', 422, 'این درخواست قبلاً بررسی شده است.');

        $data = $request->validate(['admin_note' => ['required', 'string']]);

        $wallet = $withdrawRequest->user->wallet()->firstOrCreate([]);
        $wallet->credit($withdrawRequest->amount, 'refund', 'بازگشت مبلغ درخواست تسویه ردشده', $withdrawRequest);

        $withdrawRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'admin_note' => $data['admin_note'],
        ]);

        return response()->json($withdrawRequest);
    }
}
