<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::all()->pluck('value', 'key'));
    }

    // مثال کلیدها: commission_percent, story_reward_weights, min_withdraw_amount
    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            Setting::set($key, is_array($value) ? json_encode($value) : $value);
        }

        return response()->json(['message' => 'تنظیمات بروزرسانی شد.']);
    }
}
