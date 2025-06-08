<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLoggerService;

class ArticleController extends Controller
{
    protected ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

    public function showArticle($id)
    {
        $article = Article::with([
            'profile',
            'comments.user.profile',
        ])->find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $authorProfile = $article->profile;

        $comments = $article->comments->map(function ($comment) {
            $commentProfile = $comment->user?->profile;

            return [
                'id' => $comment->id,
                'causer' => $comment->causer,
                'article_id' => $comment->article_id,
                'content' => $comment->content,
                'banned_at' => optional($comment->banned_at)?->format('Y-m-d\TH:i:s.v\Z'),
                'created_at' => $comment->created_at->format('Y-m-d\TH:i:s.v\Z'),
                'updated_at' => $comment->updated_at->format('Y-m-d\TH:i:s.v\Z'),
                'logo' => $commentProfile?->logo ?? 'string',
                'likes' => $comment->likes ?? 0,
            ];
        });

        $user = Auth::user();
        $token = request()->bearerToken();
        $profile = $user?->profile;

        return response()->json([
            'id' => $article->id,
            'author' => [
                'nickname' => $authorProfile->nickname,
                'account_id' => $authorProfile->user_id,
                'logo' => $authorProfile->logo,
                'followers' => $authorProfile->followers()->count() ?? 0,
            ],
            'title' => $article->title,
            'content' => $article->content,
            'tags' => $article->tags,
            'likes' => $article->likes ?? 0,
            'comments' => $comments,
            'thumbnail' => $article->thumbnail,
            'banned_at' => optional($article->banned_at)?->format('Y-m-d\TH:i:s.v\Z'),
            'created_at' => $article->created_at->format('Y-m-d\TH:i:s.v\Z'),
            'updated_at' => $article->updated_at->format('Y-m-d\TH:i:s.v\Z'),
            'role' => $user?->role ?? 'guest',
            'state' => $profile ? 'hasProfile' : 'noProfile',
            'token' => $token ?? 'string',
            'profile' => $profile ? [
                'nickname' => $profile->nickname,
                'account_id' => $profile->user_id,
                'description' => $profile->description,
                'logo' => $profile->logo,
                'followers' => $profile->followers()->count() ?? 0,
                'created_at' => $profile->created_at->format('Y-m-d\TH:i:s.v\Z'),
                'updated_at' => $profile->updated_at->format('Y-m-d\TH:i:s.v\Z'),
            ] : null,
        ]);
    }


    public function createArticle(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|string',
        ]);

        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        $article = Article::create([
            'author' => $profile->nickname,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'tags' => $validated['tags'] ?? null,
        ]);

        $this->logger->log(
            $article,
            'Article created',
            ['title' => $article->title],
            $user,
            'articles'
        );

        return response()->json($article, 201);
    }

    public function banArticle(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        if ($article->banned_at !== null) {
            return response()->json(['error' => 'Article is already banned.'], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $article->banned_at = now();
        $article->save();

        DB::table('bans')->insert([
            'causer' => Auth::id(),
            'target_type' => 'article',
            'target_id' => $article->id,
            'content' => $request->input('reason'),
            'created_at' => now(),
        ]);

        $this->logger->log(
            $article,
            'Article banned',
            ['reason' => $request->input('reason')],
            Auth::user(),
            'articles'
        );

        return response()->json(['success' => 'Article has been banned.']);
    }

    public function unbanArticle(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        if ($article->banned_at === null) {
            return response()->json(['error' => 'Article is not banned.'], 400);
        }

        $article->banned_at = null;
        $article->save();

        DB::table('bans')
            ->where('target_type', 'article')
            ->where('target_id', $article->id)
            ->latest('created_at')
            ->limit(1)
            ->delete();

        $this->logger->log(
            $article,
            'Article unbanned',
            [],
            Auth::user(),
            'articles'
        );

        return response()->json(['success' => 'Article has been unbanned.']);
    }

    public function destroyArticle($id)
    {
        $user = Auth::user();
        $article = Article::findOrFail($id);

        $profile = Profile::where('user_id', $user->id)->first();

        $isAdmin = in_array($user->role, ['admin', 'superadmin']);
        $isAuthor = $profile && $profile->nickname === $article->author;

        if (!$isAdmin && !$isAuthor) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $article->delete();

        $this->logger->log(
            $article,
            'Article deleted',
            [],
            $user,
            'articles'
        );

        return response()->json(['success' => 'Article deleted.']);
    }
}
