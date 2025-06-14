<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\LikeReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLoggerService;

class LikeReactionController extends Controller
{
    protected ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

    public function like($id)
    {
        $user = Auth::user();

        $article = Article::find($id);
        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $alreadyLiked = LikeReaction::where('causer', $user->id)
            ->where('article_id', $id)
            ->exists();

        if ($alreadyLiked) {
            return response()->json(['message' => 'Article already liked'], 409);
        }

        $like = LikeReaction::create([
            'causer' => $user->id,
            'article_id' => $id,
        ]);

        $this->logger->log(
            subject: $article,
            description: 'Article liked',
            causer: $user,
            logName: 'likes'
        );

        return response()->json([
            'message' => 'Article liked successfully',
            'like' => $like,
        ], 201);
    }

    public function unlike($id)
    {
        $user = Auth::user();

        $like = LikeReaction::where('causer', $user->id)
            ->where('article_id', $id)
            ->first();

        if (! $like) {
            return response()->json(['message' => 'Like not found'], 404);
        }

        $article = Article::find($id);
        $like->delete();

        $this->logger->log(
            subject: $article,
            description: 'Article unliked',
            causer: $user,
            logName: 'likes'
        );

        return response()->json(['message' => 'Like removed successfully'], 200);
    }
}
