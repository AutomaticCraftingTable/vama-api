<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Article;
use App\Services\ActivityLoggerService;

class CommentController extends Controller
{
    protected ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

    public function createComment(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        $article = Article::find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $comment = Comment::create([
            'causer' => $user->id,
            'article_id' => $article->id,
            'content' => $validated['content'],
        ]);

        $this->logger->log(
            subject: $comment,
            description: 'Comment created',
            causer: $user,
            logName: 'comments'
        );



        return response()->json($comment, 201);
    }

    public function banComment(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->banned_at !== null) {
            return response()->json(['error' => 'Comment is already banned.'], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $comment->banned_at = now();
        $comment->save();

        DB::table('bans')->insert([
            'causer' => Auth::id(),
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'content' => $request->input('reason'),
            'created_at' => now(),
        ]);

        $this->logger->log(
            subject: $comment,
            description: 'Comment banned',
            causer: Auth::user(),
            logName: 'comments'
        );


        return response()->json(['success' => 'Comment has been banned.']);
    }

    public function unbanComment($id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->banned_at === null) {
            return response()->json(['error' => 'Comment is not banned.'], 400);
        }

        $comment->banned_at = null;
        $comment->save();

        DB::table('bans')
            ->where('target_type', 'comment')
            ->where('target_id', $comment->id)
            ->latest('created_at')
            ->limit(1)
            ->delete();

        $this->logger->log(
            subject: $comment,
            description: 'Comment unbanned',
            causer: Auth::user(),
            logName: 'comments'
        );


        return response()->json(['success' => 'Comment has been unbanned.']);
    }

    public function destroyComment($id)
    {
        $user = Auth::user();
        $comment = Comment::findOrFail($id);

        $isAdmin = in_array($user->role, ['admin', 'superadmin']);
        $isAuthor = $comment->causer === $user->id;

        if (!$isAdmin && !$isAuthor) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $comment->delete();

        $this->logger->log(
            subject: $comment,
            description: 'Comment deleted',
            causer: $user,
            logName: 'comments'
        );


        return response()->json(['success' => 'Comment deleted.']);
    }
}
