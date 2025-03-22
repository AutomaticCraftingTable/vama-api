<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::middleware("auth:sanctum")->get("/user", fn (Request $request): JsonResponse => new JsonResponse($request->user()));

Route::prefix('auth')->group(function () {
Route::post('login', [AuthController::class, 'login']);

Route::post('register', [AuthController::class, 'register']);

Route::get('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});;
