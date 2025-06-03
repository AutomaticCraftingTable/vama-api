<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Report;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLoggerService;

class ReportController extends Controller
{
    protected ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

    private function report(Request $request, $target, string $type)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        if (!$target) {
            return response()->json(['error' => 'Target not found.'], 404);
        }

        $report = Report::create([
            'causer' => Auth::id(),
            'target_type' => $type,
            'target_id' => $type === 'profile' ? $target->nickname : $target->id,
            'content' => $request->input('content'),
        ]);

        $this->logger->log(
            subject: $report,
            description: ucfirst($type) . ' reported',
            causer: $request->user(),
            logName: 'reports'
        );

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


        $this->logger->log(
            subject: null,
            description: "Deleted $deleted report(s) on $type #$id",
            causer: $user,
            logName: 'reports'
        );

        return response()->json([
            'message' => "$deleted report(s) on $type #$id deleted successfully.",
        ]);
    }
}
