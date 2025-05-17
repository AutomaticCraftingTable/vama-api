<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;

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

    public function subscribe(Request $request, $nickname)
    {
        $authorProfile = Profile::where('nickname', $nickname)->firstOrFail();
        $causerProfile = Profile::where('user_id', Auth::id())->firstOrFail();

        if ($causerProfile->nickname === $authorProfile->nickname) {
            return response()->json(['message' => 'You cannot subscribe to yourself.'], 400);
        }

        $exists = Subscription::where('causer', $causerProfile->nickname)
            ->where('author', $authorProfile->nickname)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already subscribed.'], 409);
        }

        Subscription::create([
            'causer' => $causerProfile->nickname,
            'author' => $authorProfile->nickname,
        ]);

        return response()->json(['message' => 'Subscribed successfully.'], 201);
    }

    public function unsubscribe(Request $request, $nickname)
    {
        $authorProfile = Profile::where('nickname', $nickname)->firstOrFail();
        $causerProfile = Profile::where('user_id', Auth::id())->firstOrFail();

        $deleted = Subscription::where('causer', $causerProfile->nickname)
            ->where('author', $authorProfile->nickname)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Unsubscribed successfully.']);
        }

        return response()->json(['message' => 'Subscription not found.'], 404);
    }
}
