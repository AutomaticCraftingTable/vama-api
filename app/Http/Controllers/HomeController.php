<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Article;
use App\Models\LikeReaction;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        $articles = \App\Models\Article::with([
                'profile:user_id,nickname,logo,description,created_at,updated_at',
                'comments.user:id,nickname,logo',
            ])
            ->withCount('likes')
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($article) {
                $author = $article->profile;

                return [
                    'id' => $article->id,
                    'author' => [
                        'nickname' => $author->nickname ?? 'unknown',
                        'account_id' => $author->user_id ?? null,
                        'description' => $author->description ?? null,
                        'logo' => $author->logo ?? null,
                        'followers' => $author ? $author->followers()->count() : 0,
                        'created_at' => $author->created_at ?? null,
                        'updated_at' => $author->updated_at ?? null,
                    ],
                    'title' => $article->title,
                    'content' => $article->content,
                    'tags' => $article->tags,
                    'likes' => $article->likes_count,
                    'comments' => $article->comments->map(function ($comment) {
                        $user = $comment->user;
                        return [
                            'id' => $comment->id,
                            'causer' => $user->nickname ?? 'unknown',
                            'article_id' => $comment->article_id,
                            'content' => $comment->content,
                            'banned_at' => $comment->banned_at,
                            'created_at' => $comment->created_at,
                            'updated_at' => $comment->updated_at,
                            'logo' => $user->logo ?? null,
                        ];
                    }),
                    'thumbnail' => $article->thumbnail,
                    'banned_at' => $article->banned_at,
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ];
            });

        return response()->json([
            'state' => 'allArticles',
            'articles' => $articles,
        ]);
    }



    public function subscriptions(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $subscriptions = Subscription::where('causer', $profile->nickname)
            ->with('authorProfile')
            ->get()
            ->map(function ($subscription) {
                $author = $subscription->authorProfile;

                return [
                    'id' => $subscription->id,
                    'author' => [
                        'nickname' => $author->nickname,
                        'account_id' => $author->user_id,
                        'description' => $author->description,
                        'logo' => $author->logo,
                        'followers' => $author->followers()->count(),
                        'created_at' => $author->created_at,
                        'updated_at' => $author->updated_at,
                    ],
                    'created_at' => $subscription->created_at,
                ];
            });

        return response()->json([
            'role' => $user->role,
            'state' => 'hasProfile',
            'token' => $request->bearerToken(),
            'profile' => [
                'nickname' => $profile->nickname,
                'account_id' => $user->id,
                'description' => $profile->description,
                'logo' => $profile->logo,
                'followers' => $profile->followers()->count(),
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ],
            'subscriptions' => $subscriptions,
        ]);
    }

    public function likedArticles(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $likedArticleIds = LikeReaction::where('causer', $user->id)->pluck('article_id');

        $articles = Article::whereIn('id', $likedArticleIds)
            ->withCount('likes')
            ->with([
                'profile:nickname,user_id,logo',
                'comments',
            ])
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'author' => [
                        'nickname' => $article->profile->nickname,
                        'account_id' => $article->profile->user_id,
                        'logo' => $article->profile->logo,
                        'followers' => $article->profile->followers()->count(),
                    ],
                    'title' => $article->title,
                    'content' => $article->content,
                    'tags' => $article->tags,
                    'likes' => $article->likes_count,
                    'comments' => $article->comments->map(function ($comment) {
                        $commentProfile = \App\Models\Profile::where('user_id', $comment->causer)->first();

                        return [
                            'id' => $comment->id,
                            'causer' => $commentProfile?->nickname ?? 'unknown',
                            'article_id' => $comment->article_id,
                            'content' => $comment->content,
                            'banned_at' => $comment->banned_at,
                            'created_at' => $comment->created_at,
                            'updated_at' => $comment->updated_at,
                            'logo' => $commentProfile?->logo ?? null,
                        ];
                    }),
                    'thumbnail' => $article->thumbnail,
                    'banned_at' => $article->banned_at,
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ];
            });

        return response()->json([
            'articles' => $articles,
            'role' => $user->role,
            'state' => 'hasProfile',
            'token' => $request->bearerToken(),
            'profile' => [
                'nickname' => $profile->nickname,
                'account_id' => $user->id,
                'description' => $profile->description,
                'logo' => $profile->logo,
                'followers' => $profile->followers()->count(),
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ],
        ]);
    }


    public function search(Request $request)
    {
        $validated = $request->validate([
        'query' => 'required|string',
    ]);

        $query = $validated['query'];


        $articles = \App\Models\Article::with([
                'profile:user_id,nickname,logo,description,created_at,updated_at',
                'comments.user:id,nickname,logo',
            ])
            ->withCount('likes')
            ->where('title', 'ILIKE', "%{$query}%")
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($article) {
                $author = $article->profile;

                return [
                    'id' => $article->id,
                    'author' => [
                        'nickname' => $author->nickname,
                        'account_id' => $author->user_id,
                        'description' => $author->description ?? null,
                        'logo' => $author->logo,
                        'followers' => $author->followers()->count(),
                        'created_at' => $author->created_at,
                        'updated_at' => $author->updated_at,
                    ],
                    'title' => $article->title,
                    'content' => $article->content,
                    'tags' => $article->tags,
                    'likes' => $article->likes_count,
                    'comments' => $article->comments->map(function ($comment) {
                        $user = $comment->user;
                        return [
                            'id' => $comment->id,
                            'causer' => $user->nickname ?? 'unknown',
                            'article_id' => $comment->article_id,
                            'content' => $comment->content,
                            'banned_at' => $comment->banned_at,
                            'created_at' => $comment->created_at,
                            'updated_at' => $comment->updated_at,
                            'logo' => $user->logo ?? null,
                        ];
                    }),
                    'thumbnail' => $article->thumbnail,
                    'banned_at' => $article->banned_at,
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ];
            });

        return response()->json([
            'state' => 'searchResults',
            'query' => $query,
            'articles' => $articles,
        ]);
    }
}
