<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementManagementController extends Controller
{
    public function index()
    {
        return response()->json(Advertisement::latest()->paginate(30));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_id' => ['nullable', 'exists:shops,id'],
            'type' => ['required', 'in:banner_slider,banner_strip,list_banner,featured'],
            'image' => ['required', 'string'],
            'link' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $ad = Advertisement::create($data);

        return response()->json($ad, 201);
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $data = $request->validate([
            'type' => ['sometimes', 'in:banner_slider,banner_strip,list_banner,featured'],
            'image' => ['sometimes', 'string'],
            'link' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $advertisement->update($data);

        return response()->json($advertisement);
    }

    public function destroy(Advertisement $advertisement)
    {
        $advertisement->delete();

        return response()->json(['message' => 'تبلیغ حذف شد.']);
    }
}
