<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Note;
use App\Models\Comment;
use App\Models\Article;
use App\Models\Profile;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ListController extends Controller
{
    public function moderators(): JsonResponse
    {
        $moderators = User::where('role', 'moderator')
            ->with(['profile.notes'])
            ->get()
            ->map(function ($moderator) {
                return [
                    'id' => $moderator->id,
                    'email' => $moderator->email,
                    'notes' => $moderator->profile?->notes->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'content' => $note->content,
                            'causer' => $note->causer,
                            'article_id' => $note->article_id,
                            'created_at' => $note->created_at->toISOString(),
                            'updated_at' => $note->updated_at->toISOString(),
                        ];
                    }) ?? [],
                ];
            });

        return response()->json(['moders' => $moderators]);
    }

    public function notes(): JsonResponse
    {
        $notes = Note::with(['article', 'profile'])->get();

        return response()->json(['notes' => $notes], 200);
    }

    public function reportedArticles(): JsonResponse
    {
        $reports = \App\Models\Report::where('target_type', 'article')->get();

        $articleIds = $reports->pluck('target_id')->unique();

        $articles = \App\Models\Article::whereIn('id', $articleIds)
            ->with(['profile' => function ($q) {
                $q->withCount('followers');
            }, 'likes'])
            ->get();

        $result = $articles->map(function ($article) use ($reports) {
            $report = $reports->firstWhere('target_id', $article->id);

            $reporter = \App\Models\User::find($report->causer);

            return [
                'id' => $article->id,
                'author' => [
                    'nickname' => $article->author,
                    'account_id' => $article->profile?->user_id ?? null,
                    'logo' => $article->profile?->logo,
                    'followers' => $article->profile?->followers_count ?? 0,
                ],
                'title' => $article->title,
                'content' => $article->content,
                'tags' => $article->tags,
                'likes' => $article->likes->count(),
                'thumbnail' => null,
                'reporter' => $reporter ? [
                    'id' => $reporter->id,
                    'email' => $reporter->email,
                    'role' => $reporter->role,
                ] : null,
            ];
        });

        return response()->json(['articles' => $result]);
    }


    public function reportedProfiles(): JsonResponse
    {
        $reportedNicknames = \App\Models\Report::where('target_type', 'profile')
            ->pluck('target_id')
            ->unique();

        $profiles = \App\Models\Profile::whereIn('nickname', $reportedNicknames)
            ->withCount('followers')
            ->get()
            ->map(function ($profile) {
                return [
                    'nickname' => $profile->nickname,
                    'account_id' => $profile->user_id,
                    'description' => $profile->description,
                    'logo' => $profile->logo,
                    'followers' => $profile->followers_count,
                    'created_at' => $profile->created_at->toISOString(),
                    'updated_at' => $profile->updated_at->toISOString(),
                ];
            });

        return response()->json(['profiles' => $profiles]);
    }


    public function reportedComments(): JsonResponse
    {
        $reportedCommentIds = Report::where('target_type', 'comment')
            ->pluck('target_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $comments = Comment::with(['user.profile' => function ($query) {
            $query->withCount('followers');
        }])
            ->whereIn('id', $reportedCommentIds)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'causer' => [
                        'nickname' => $comment->user->profile->nickname ?? null,
                        'account_id' => $comment->user->id,
                        'description' => $comment->user->profile->description ?? null,
                        'logo' => $comment->user->profile->logo ?? null,
                        'followers' => $comment->user->profile->followers_count ?? 0,
                        'created_at' => $comment->user->created_at,
                        'updated_at' => $comment->user->updated_at,
                    ],
                    'article_id' => $comment->article_id,
                    'content' => $comment->content,
                ];
            });

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function profiles(): JsonResponse
    {
        $profiles = Profile::withCount('followers')
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($profile) {
                $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', Profile::class)
                    ->where('subject_id', $profile->id)
                    ->latest()
                    ->get()
                    ->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'log_name' => $activity->log_name,
                            'description' => $activity->description,
                            'subject_id' => $activity->subject_id,
                            'subject_type' => $activity->subject_type,
                            'causer_id' => $activity->causer_id,
                            'causer_type' => $activity->causer_type,
                            'properties' => $activity->properties ?? [],
                            'event' => $activity->event ?? '',
                            'created_at' => $activity->created_at,
                            'updated_at' => $activity->updated_at,
                            'status' => 'success',
                        ];
                    });

                return [
                    'nickname' => $profile->nickname,
                    'account_id' => $profile->user_id,
                    'description' => $profile->description,
                    'logo' => $profile->logo,
                    'followers' => $profile->followers_count,
                    'created_at' => $profile->created_at,
                    'updated_at' => $profile->updated_at,
                    'activities' => $activities,
                ];
            });

        return response()->json([
            'state' => 'allProfiles',
            'profiles' => $profiles,
        ]);
    }
}
