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
