<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Note;
use App\Models\Comment;
use App\Models\Article;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ListController extends Controller
{
    public function moderators(): JsonResponse
    {
        $moderators = User::where('role', 'moderator')
            ->with('profile')
            ->get();

        return response()->json([
            'moders' => $moderators,
        ]);
    }

    public function notes(): JsonResponse
    {
        $notes = Note::with(['article', 'profile'])->get();

        return response()->json(['notes' => $notes], 200);
    }

    public function reportedArticles(): JsonResponse
    {
        $articles = Article::whereIn('id', function ($query) {
            $query->select(DB::raw('CAST(target_id AS BIGINT)'))
                ->from('reports')
                ->where('target_type', Article::class);
        })
            ->with([
            'author:id,nickname,account_id,logo',
            'comments',
            'comments.profile:id,nickname,logo',
        ])
            ->get();

        return response()->json([
            'articles' => $articles,
        ]);
    }

    public function reportedProfiles(): JsonResponse
    {
        $profiles = Profile::whereIn('nickname', function ($query) {
            $query->select('target_id')
                ->from('reports')
                ->where('target_type', Profile::class);
        })
            ->withCount('followers')
            ->get();

        return response()->json([
            'profiles' => $profiles,
        ]);
    }

    public function reportedComments(): JsonResponse
    {
        $comments = Comment::whereIn('id', function ($query) {
            $query->select(DB::raw('CAST(target_id AS BIGINT)'))
                ->from('reports')
                ->where('target_type', Comment::class);
        })
            ->with(['article:id,title', 'profile:id,nickname,logo'])
            ->get();

        return response()->json([
            'comments' => $comments,
        ]);
    }

public function profiles()
{
    $profiles = \App\Models\Profile::withCount('followers')
        ->with('user') 
        ->latest()
        ->get()
        ->map(function ($profile) {
            $activities = \Spatie\Activitylog\Models\Activity::where('subject_type', \App\Models\Profile::class)
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
