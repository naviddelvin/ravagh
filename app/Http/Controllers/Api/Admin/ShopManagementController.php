<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;

class ShopManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Shop::query()->with('owner:id,name,mobile');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->paginate(30));
    }

    // تایید یا رد یا تعلیق غرفه
    public function updateStatus(Request $request, Shop $shop)
    {
        $data = $request->validate(['status' => ['required', 'in:pending,active,suspended']]);

        $shop->update($data);

        return response()->json($shop);
    }

    // اعطا یا لغو نشان «تأیید شده توسط رواق» پس از بررسی مدارک
    public function toggleVerified(Shop $shop)
    {
        $shop->update(['verified_at' => $shop->verified_at ? null : now()]);

        return response()->json($shop);
    }
}
