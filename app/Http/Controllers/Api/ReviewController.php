<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private const TYPES = [
        'product' => Product::class,
        'service' => Service::class,
        'shop' => Shop::class,
    ];

    public function index(Request $request, string $type, int $id)
    {
        $modelClass = self::TYPES[$type] ?? abort(404);
        $model = $modelClass::findOrFail($id);

        return response()->json($model->reviews()->with('user:id,name,avatar')->latest()->paginate(20));
    }

    public function store(Request $request, string $type, int $id)
    {
        $modelClass = self::TYPES[$type] ?? abort(404);
        $model = $modelClass::findOrFail($id);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = $model->reviews()->create([
            'user_id' => $request->user()->id,
            ...$data,
        ]);

        return response()->json($review, 201);
    }
}
