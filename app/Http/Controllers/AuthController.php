<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\ActivityLoggerService;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function register(Request $request, ActivityLoggerService $logger)
    {
        $fields = $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        $user = User::create([
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'role' => 'user',
            'banned_at' => null,
        ]);

        $logger->log(
            $user,
            'User registered',
            ['email' => $user->email],
            $user,
            'auth'
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(Request $request, ActivityLoggerService $logger)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 400);
        }

        $logger->log(
            $user,
            'User logged in',
            ['email' => $user->email],
            $user,
            'auth'
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request, ActivityLoggerService $logger)
    {
        $user = $request->user();

        $user->tokens->each(fn ($token) => $token->delete());

        $logger->log(
            $user,
            'User logged out',
            ['email' => $user->email],
            $user,
            'auth'
        );

        return [
            'message' => 'You are logged out.',
        ];
    }

    public function handleGoogleCallback(Request $request)
    {
        $state = $request->query('state');
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'email' => $googleUser->getEmail(),
                'password' => bcrypt(Str::random(24)),
                'google_id' => $googleUser->getId(),
                'role' => 'user',
                'banned_at' => null,
                'email_verified_at' => now(),
            ]);
        } elseif ($user->google_id === null) {
            Cache::put("auth_callback_pending:$state", ['error' => 'email_taken'], now()->addMinutes(20));
            return response()->json([
                'message' => 'This email is already registered. Please log in with email and password.',
            ], 409);
        }

        activity()
        ->causedBy($user)
        ->inLog('auth')
        ->withProperties(['method' => 'google'])
        ->log('User logged in via Google');

        $token = $user->createToken('auth_token')->plainTextToken;

        Cache::put("auth_callback_pending:$state", [
            'user' => $user,
            'token' => $token,
        ], now()->addMinutes(2));

        return view('callback');
    }

    public function initGoogleLogin()
    {
        $state = Str::uuid()->toString();

        Cache::put("auth_callback_pending:$state", null, now()->addMinutes(20));

        $url = Socialite::driver('google')
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'state' => $state,
            'redirect_url' => $url,
        ]);
    }

    public function checkGoogleLogin(string $state)
    {
        $data = Cache::get("auth_callback_pending:$state");

        if (!$data) {
            return response()->json(['status' => 'waiting'], 202);
        }

        if (isset($data['error'])) {
            return response()->json(['message' => $data['error']], 409);
        }

        return response()->json($data);
    }
}
