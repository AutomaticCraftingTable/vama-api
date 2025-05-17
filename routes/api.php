<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeReactionController;
use App\Http\Controllers\NoteController;

Route::middleware("auth:sanctum")->get("/user", fn (Request $request): JsonResponse => new JsonResponse($request->user()));

Route::get('/article/{id}', [ArticleController::class, 'showArticle']);

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::post('register', [AuthController::class, 'register']);

    Route::get('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/account', [UserController::class, 'updatePassword']);

    Route::delete('/account', [UserController::class, 'destroyAccount']);

    Route::delete('/profile', [ProfileController::class, 'destroy']);

    Route::delete('/article/{id}', [ArticleController::class, 'destroyArticle']);

    Route::delete('/comment/{id}', [CommentController::class, 'destroyComment']);
});



Route::middleware(['auth:sanctum', 'canAccessContent'])->group(function () {
    Route::middleware(['checkRole:admin,superadmin'])->group(function () {
        Route::delete('/account/{id}', [UserController::class, 'destroyUserAccount']);

        Route::post('/account/{id}/ban', [UserController::class, 'banUser']);

        Route::delete('/account/{id}/ban', [UserController::class, 'unbanUser']);

        Route::post('/article/{id}/ban', [ArticleController::class, 'banArticle']);

        Route::delete('/article/{id}/ban', [ArticleController::class, 'unbanArticle']);

        Route::post('/comment/{id}/ban', [CommentController::class, 'banComment']);

        Route::delete('/comment/{id}/ban', [CommentController::class, 'unbanComment']);

        Route::post('/account/{user}/role', [UserController::class, 'changeUserRole']);
    });

    Route::middleware(['checkRole:admin,superadmin,moderator'])->group(function () {
        Route::post('/article/{id}/note', [NoteController::class, 'createNote']);

        Route::delete('/note/{id}', [NoteController::class, 'deleteNote']);
    });

    Route::post('/profile', [ProfileController::class, 'store']);

    Route::put('/profile', [ProfileController::class, 'update']);

    Route::post('/article/{id}', [ArticleController::class, 'createArticle']);

    Route::post('/article/{id}/comment', [CommentController::class, 'createComment']);

    Route::post('/article/{id}/like', [LikeReactionController::class, 'like']);

    Route::delete('/article/{id}/like', [LikeReactionController::class, 'unlike']);

    Route::post('/profile/{nickname}/subscribe', [ProfileController::class, 'subscribe']);

    Route::delete('/profile/{nickname}/subscribe', [ProfileController::class, 'unsubscribe']);
});
