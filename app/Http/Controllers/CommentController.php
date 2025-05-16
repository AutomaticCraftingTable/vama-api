<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
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

        return response()->json(['success' => 'Comment deleted.']);
    }
}
