<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;
use Illuminate\Validation\ValidationException;

class ArticleController extends Controller
{
    public function showArticle($id)
    {
        $article = Article::with(['profile', 'comments.profile'])->find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $authorProfile = $article->profile;
        $comments = $article->comments->map(function ($comment) {
            $commentProfile = $comment->profile;

            return [
                'id' => $comment->id,
                'causer' => $comment->causer,
                'article_id' => $comment->article_id,
                'content' => $comment->content,
                'banned_at' => optional($comment->banned_at)?->format('Y-m-d\TH:i:s.v\Z'),
                'created_at' => $comment->created_at->format('Y-m-d\TH:i:s.v\Z'),
                'updated_at' => $comment->updated_at->format('Y-m-d\TH:i:s.v\Z'),
                'logo' => optional($commentProfile)->logo ?? 'string',
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


    public function createArticle(Request $request, string $nickname)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|string',
        ]);

        $user = Auth::user();

        $profile = \App\Models\Profile::where('nickname', $nickname)->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($profile->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized to post under this profile'], 403);
        }

        $article = Article::create([
            'author' => $profile->nickname,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'tags' => $validated['tags'] ?? null,
        ]);

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

        return response()->json(['success' => 'Article deleted.']);
    }
}
