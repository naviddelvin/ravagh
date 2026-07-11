<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\Shop;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function store(Request $request, Shop $shop)
    {
        Follow::firstOrCreate([
            'user_id' => $request->user()->id,
            'shop_id' => $shop->id,
        ]);

        return response()->json(['message' => 'غرفه دنبال شد.', 'following' => true]);
    }

    public function destroy(Request $request, Shop $shop)
    {
        Follow::where('user_id', $request->user()->id)->where('shop_id', $shop->id)->delete();

        return response()->json(['message' => 'دنبال کردن لغو شد.', 'following' => false]);
    }

    public function check(Request $request, Shop $shop)
    {
        $following = Follow::where('user_id', $request->user()->id)->where('shop_id', $shop->id)->exists();

        return response()->json(['following' => $following]);
    }

    public function followersCount(Shop $shop)
    {
        return response()->json(['followers_count' => $shop->followers()->count()]);
    }
}
