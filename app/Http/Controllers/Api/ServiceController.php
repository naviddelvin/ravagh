<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request, Shop $shop)
    {
        return response()->json(
            $shop->services()->where('is_active', true)->paginate(20)
        );
    }

    public function show(Service $service)
    {
        return response()->json($service->load('shop', 'reviews.user'));
    }

    public function store(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403, 'شما مالک این غرفه نیستید.');

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
        ]);

        $data['shop_id'] = $shop->id;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    public function update(Request $request, Service $service)
    {
        abort_if($service->shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $service->update($data);

        return response()->json($service);
    }

    public function destroy(Request $request, Service $service)
    {
        abort_if($service->shop->user_id !== $request->user()->id, 403);

        $service->delete();

        return response()->json(['message' => 'خدمت حذف شد.']);
    }
}
