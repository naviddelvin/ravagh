<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // لیست دسته‌بندی‌ها به تفکیک نوع (shop | product | service)
    public function index(Request $request)
    {
        $query = Category::query()->where('is_active', true)->orderBy('sort_order');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        return response()->json(
            $query->with('children')->get()
        );
    }

    // فقط مدیر سیستم مجاز به مدیریت دسته‌بندی است (بند ۱۷ سند)
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'alpha_dash', 'unique:categories,slug'],
            'type' => ['required', 'in:shop,product,service'],
            'icon' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = Category::create($data);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'alpha_dash', 'unique:categories,slug,' . $category->id],
            'type' => ['sometimes', 'in:shop,product,service'],
            'icon' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'دسته‌بندی حذف شد.']);
    }
}
