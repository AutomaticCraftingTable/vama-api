<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;

Route::middleware("auth:sanctum")->get("/user", fn (Request $request): JsonResponse => new JsonResponse($request->user()));

Route::middleware(['auth:sanctum', 'checkRole:admin,superadmin'])->get('/test/role', function () {
    return response()->json(['message' => 'Access granted']);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::post('register', [AuthController::class, 'register']);

    Route::get('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/account', [UserController::class, 'updatePassword']);

    Route::delete('/account', [UserController::class, 'destroyAccount']);
});



Route::middleware(['auth:sanctum', 'canAccessContent'])->group(function () {
    Route::middleware(['checkRole:admin,superadmin'])->group(function () {
        Route::post('/account/{id}/ban', [UserController::class, 'banUser']);

        Route::post('/account/{id}/unban', [UserController::class, 'unbanUser']);

        Route::post('/account/{user}/role', [UserController::class, 'changeUserRole']);

        Route::delete('/account/{id}', [UserController::class, 'destroyUserAccount']);
    });

    Route::post('/profile', [ProfileController::class, 'store']);

    Route::put('/profile', [ProfileController::class, 'update']);

    Route::delete('/profile', [ProfileController::class, 'destroy']);
});

