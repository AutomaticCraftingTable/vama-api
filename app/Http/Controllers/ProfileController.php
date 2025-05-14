<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nickname' => 'required|string|max:255|unique:profiles,nickname',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
        ]);

        if ($request->user()->profile) {
            return response()->json(['message' => 'Profile already exists.'], 409);
        }

        $profile = $request->user()->profile()->create($request->only(['nickname', 'description', 'logo']));

        return response()->json(['profile' => $profile], 201);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $profile->update($request->only(['nickname', 'description', 'logo']));

        return response()->json(['profile' => $profile]);
    }


    public function destroy(Request $request)
    {
        $profile = $request->user()->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $profile->delete();

        return response()->json(['message' => 'Profile deleted.']);
    }
}
