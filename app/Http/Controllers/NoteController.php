<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use App\Models\Note;

class NoteController extends Controller
{
    public function createNote(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        $article = Article::find($id);

        if (! $article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $note = Note::create([
            'content' => $validated['content'],
            'causer' => $profile->nickname,
            'article_id' => $article->id,
        ]);

        return response()->json($note, 201);
    }

    public function deleteNote($id)
    {
        $user = Auth::user();
        $note = Note::find($id);

        if (! $note) {
            return response()->json(['message' => 'Note not found'], 404);
        }

        $isOwner = $user->profile && $user->profile->nickname === $note->causer;
        $isPrivileged = in_array($user->role, ['admin', 'superadmin']);

        if (! $isOwner && ! $isPrivileged) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $note->delete();

        return response()->json(['message' => 'Note deleted'], 200);
    }
}
