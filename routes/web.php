<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/auth/google/init', [AuthController::class, 'initGoogleLogin']);
Route::get('/auth/google/wait/{state}', [AuthController::class, 'checkGoogleLogin']);
Route::get('/auth/callback/google', [AuthController::class, 'handleGoogleCallback']);
