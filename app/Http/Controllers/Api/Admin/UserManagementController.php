<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('wallet');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $request->string('q') . '%')
                ->orWhere('mobile', 'like', '%' . $request->string('q') . '%'));
        }

        return response()->json($query->latest()->paginate(30));
    }

    public function toggleActive(Request $request, User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        return response()->json($user);
    }
}
