<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $cart = $request->user()->cart()->firstOrCreate([]);

        return response()->json($cart->load('items.product'));
    }

    public function addItem(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        abort_if($product->stock < $data['quantity'], 422, 'موجودی کافی نیست.');

        $cart = $request->user()->cart()->firstOrCreate([]);

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $item->increment('quantity', $data['quantity']);
        } else {
            $item = $cart->items()->create($data);
        }

        return response()->json($cart->load('items.product'));
    }

    public function updateItem(Request $request, int $itemId)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1']]);

        $cart = $request->user()->cart()->firstOrFail();
        $item = $cart->items()->findOrFail($itemId);
        $item->update($data);

        return response()->json($cart->load('items.product'));
    }

    public function removeItem(Request $request, int $itemId)
    {
        $cart = $request->user()->cart()->firstOrFail();
        $cart->items()->where('id', $itemId)->delete();

        return response()->json($cart->load('items.product'));
    }
}
