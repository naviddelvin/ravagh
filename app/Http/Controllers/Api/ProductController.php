<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request, Shop $shop)
    {
        $query = $shop->products()->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        return response()->json($query->paginate(20));
    }

    public function show(Product $product)
    {
        return response()->json($product->load('shop', 'reviews.user'));
    }

    // فقط غرفه‌دار مالک غرفه می‌تواند محصول اضافه کند
    public function store(Request $request, Shop $shop)
    {
        abort_if($shop->user_id !== $request->user()->id, 403, 'شما مالک این غرفه نیستید.');

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'discount_price' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
        ]);

        $data['shop_id'] = $shop->id;
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->shop->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'discount_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'images' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Request $request, Product $product)
    {
        abort_if($product->shop->user_id !== $request->user()->id, 403);

        $product->delete();

        return response()->json(['message' => 'محصول حذف شد.']);
    }
}
