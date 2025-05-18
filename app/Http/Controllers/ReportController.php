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
    private function report(Request $request, $id, $type)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $modelClass = match ($type) {
            'article' => \App\Models\Article::class,
            'comment' => \App\Models\Comment::class,
            'profile' => \App\Models\Profile::class,
            default => null,
        };

        if (!$modelClass) {
            return response()->json(['error' => 'Invalid report target.'], 400);
        }

        $target = $modelClass::findOrFail($id);

        Report::create([
            'causer' => Auth::id(),
            'target_type' => $type,
            'target_id' => $id,
            'content' => $request->input('content'),
        ]);

        return response()->json(['message' => ucfirst($type).' reported successfully.'], 201);
    }

    public function reportArticle(Request $request, $id)
    {
        return $this->report($request, $id, 'article');
    }

    public function reportComment(Request $request, $id)
    {
        return $this->report($request, $id, 'comment');
    }

    public function reportProfile(Request $request, $id)
    {
        return $this->report($request, $id, 'profile');
    }

    public function deleteReports($type, $id)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'superadmin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $deleted = Report::where('target_type', $type)->where('target_id', $id)->delete();

        return response()->json([
            'message' => "$deleted report(s) on $type #$id deleted successfully.",
        ]);
    }
}
