<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    private function report(Request $request, $target, string $type)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        if (!$target) {
            return response()->json(['error' => 'Target not found.'], 404);
        }

        Report::create([
            'causer' => Auth::id(),
            'target_type' => $type,
            'target_id' => $type === 'profile' ? $target->nickname : $target->id,
            'content' => $request->input('content'),
        ]);

        return response()->json(['message' => ucfirst($type) . ' reported successfully.'], 201);
    }

    public function reportArticle(Request $request, $id)
    {
        $article = Article::findOrFail($id);
        return $this->report($request, $article, 'article');
    }

    public function reportComment(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        return $this->report($request, $comment, 'comment');
    }

    public function reportProfile(Request $request, $nickname)
    {
        $profile = Profile::where('nickname', $nickname)->firstOrFail();
        return $this->report($request, $profile, 'profile');
    }

    public function deleteReports($type, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $deleted = Report::where('target_type', $type)
            ->where('target_id', $id)
            ->delete();

        return response()->json([
            'message' => "$deleted report(s) on $type #$id deleted successfully.",
        ]);
    }
}
