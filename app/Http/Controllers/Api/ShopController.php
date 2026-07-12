<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Shop::query()->where('status', 'active')->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->string('q') . '%');
        }

        return response()->json($query->paginate(20));
    }

    public function mine(Request $request)
    {
        return response()->json($request->user()->shops()->latest()->get());
    }

    public function show(Shop $shop)
    {
        return response()->json(
            $shop->load(['category', 'products' => fn ($q) => $q->where('is_active', true),
                'services' => fn ($q) => $q->where('is_active', true),
                'reviews.user'])
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['required', 'in:product,service'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'working_hours' => ['nullable', 'array'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        $data['status'] = 'pending';
        $data['trial_ends_at'] = now()->addMonths(3);
        $data['commission_percent'] = (int) Setting::get('default_commission_percent', 10);

        $shop = Shop::create($data);

        $request->user()->update(['role' => 'shop_owner']);

        return response()->json($shop, 201);
    }

    public function update(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403, 'شما مالک این غرفه نیستید.');

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string'],
            'gallery' => ['nullable', 'array'],
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'working_hours' => ['nullable', 'array'],
        ]);

        $shop->update($data);

        return response()->json($shop);
    }

    public function report(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403);

        return response()->json([
            'type' => $shop->type,
            'total_orders' => $shop->orders()->count(),
            'paid_orders' => $shop->orders()->where('is_paid', true)->count(),
            'total_revenue' => $shop->orders()->where('is_paid', true)->sum('total_amount')
                + $shop->bookings()->where('is_paid', true)->sum('total_amount'),
            'products_count' => $shop->products()->count(),
            'services_count' => $shop->services()->count(),
            'active_stories' => $shop->stories()->where('status', 'active')->count(),
            'is_in_trial' => $shop->isInTrial(),
            'trial_ends_at' => $shop->trial_ends_at,
            'commission_percent' => $shop->effectiveCommissionPercent(),
            'loyalty_tier' => $shop->loyalty_tier,
            'is_verified' => $shop->isVerified(),
        ]);
    }
}
