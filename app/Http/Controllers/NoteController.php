<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Article;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLoggerService;

class NoteController extends Controller
{
    protected ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

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

        $this->logger->log(
            subject: $note,
            description: 'Note created',
            causer: $user,
            logName: 'notes'
        );

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

        $this->logger->log(
            subject: $note,
            description: 'Note deleted',
            causer: $user,
            logName: 'notes'
        );

        return response()->json(['message' => 'Note deleted'], 200);
    }
}
