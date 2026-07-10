<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    // بنرهای فعال صفحه اصلی (بنر اسلایدر، بنر نواری، بنر داخل لیست)
    public function index(Request $request)
    {
        $query = Advertisement::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json($query->get());
    }
}
