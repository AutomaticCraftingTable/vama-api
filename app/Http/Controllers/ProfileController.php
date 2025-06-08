<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLoggerService;

class ProfileController extends Controller
{
    protected ActivityLoggerService $logger;

    public function __construct(ActivityLoggerService $logger)
    {
        $this->logger = $logger;
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $profile->loadCount('followers');
        $profile->load(['articles' => [
            'profile:nickname,user_id,logo',
            'likes',
            'comments.user.profile:nickname,logo',
        ]]);

        $articles = $profile->articles->map(function ($article) {
            return [
                'id' => $article->id,
                'author' => [
                    'nickname' => $article->profile->nickname,
                    'account_id' => $article->profile->user_id,
                    'logo' => $article->profile->logo,
                    'followers' => $article->profile->followers_count ?? 0,
                ],
                'title' => $article->title,
                'content' => $article->content,
                'tags' => $article->tags,
                'likes' => $article->likes->count(),
                'comments' => $article->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'causer' => $comment->user->profile->nickname ?? 'unknown',
                        'article_id' => $comment->article_id,
                        'content' => $comment->content,
                        'banned_at' => $comment->banned_at,
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                        'logo' => $comment->user->profile->logo ?? null,
                    ];
                }),
                'thumbnail' => $article->thumbnail,
                'banned_at' => $article->banned_at,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ];
        });

        return response()->json([
            'nickname' => $profile->nickname,
            'account_id' => $user->id,
            'description' => $profile->description,
            'logo' => $profile->logo,
            'followers' => $profile->followers_count,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
            'articles' => $articles,
            'role' => $user->role,
            'state' => 'hasProfile',
            'token' => $request->bearerToken(),
            'profile' => [
                'nickname' => $profile->nickname,
                'account_id' => $user->id,
                'description' => $profile->description,
                'logo' => $profile->logo,
                'followers' => $profile->followers_count,
                'created_at' => $profile->created_at,
                'updated_at' => $profile->updated_at,
            ],
        ]);
    }




    public function show(string $nickname)
    {
        $profile = Profile::where('nickname', $nickname)
            ->withCount('followers')
            ->with([
                'articles' => function ($q) {
                    $q->withCount('likes')
                      ->with([
                          'profile:nickname,user_id,logo',
                          'comments.user.profile:nickname,logo',
                      ]);
                },
            ])
            ->firstOrFail();

        $articles = $profile->articles->map(function ($article) {
            return [
                'id' => $article->id,
                'author' => [
                    'nickname' => $article->profile->nickname,
                    'account_id' => $article->profile->user_id,
                    'logo' => $article->profile->logo,
                    'followers' => $article->profile->followers_count ?? 0,
                ],
                'title' => $article->title,
                'content' => $article->content,
                'tags' => $article->tags,
                'likes' => $article->likes_count,
                'comments' => $article->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'causer' => $comment->user->profile->nickname ?? 'unknown',
                        'article_id' => $comment->article_id,
                        'content' => $comment->content,
                        'banned_at' => $comment->banned_at,
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                        'logo' => $comment->user->profile->logo ?? null,
                    ];
                }),
                'thumbnail' => $article->thumbnail,
                'banned_at' => $article->banned_at,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ];
        });

        return response()->json([
            'nickname' => $profile->nickname,
            'account_id' => $profile->user_id,
            'description' => $profile->description,
            'logo' => $profile->logo,
            'followers' => $profile->followers_count,
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
            'articles' => $articles,
        ]);
    }




    public function store(Request $request)
    {
        $request->validate([
            'nickname' => 'required|string|max:255|unique:profiles,nickname',
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
        ]);

        if ($request->user()->profile) {
            return response()->json(['message' => 'Profile already exists.'], 409);
        }

        $profile = $request->user()->profile()->create($request->only(['nickname', 'description', 'logo']));

        $this->logger->log(
            subject: $profile,
            description: 'Profile created',
            causer: $request->user(),
            logName: 'profiles'
        );

        return response()->json(['profile' => $profile], 201);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $profile->update($request->only(['description', 'logo']));

        $this->logger->log(
            subject: $profile,
            description: 'Profile updated',
            causer: $user,
            logName: 'profiles'
        );

        return response()->json(['profile' => $profile]);
    }


    public function destroy(Request $request)
    {
        $profile = $request->user()->profile;

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $profile->delete();

        $this->logger->log(
            subject: $profile,
            description: 'Profile deleted',
            causer: $request->user(),
            logName: 'profiles'
        );

        return response()->json(['message' => 'Profile deleted.']);
    }

    public function subscribe(Request $request, $nickname)
    {
        $authorProfile = Profile::where('nickname', $nickname)->firstOrFail();
        $causerProfile = Profile::where('user_id', Auth::id())->firstOrFail();

        if ($causerProfile->nickname === $authorProfile->nickname) {
            return response()->json(['message' => 'You cannot subscribe to yourself.'], 400);
        }

        $exists = Subscription::where('causer', $causerProfile->nickname)
            ->where('author', $authorProfile->nickname)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already subscribed.'], 409);
        }

        $subscription = Subscription::create([
            'causer' => $causerProfile->nickname,
            'author' => $authorProfile->nickname,
        ]);

        $this->logger->log(
            subject: $subscription,
            description: 'Subscribed to profile',
            causer: $request->user(),
            logName: 'subscriptions'
        );

        return response()->json(['message' => 'Subscribed successfully.'], 201);
    }

    public function unsubscribe(Request $request, $nickname)
    {
        $authorProfile = Profile::where('nickname', $nickname)->firstOrFail();
        $causerProfile = Profile::where('user_id', Auth::id())->firstOrFail();

        $subscription = Subscription::where('causer', $causerProfile->nickname)
            ->where('author', $authorProfile->nickname)
            ->first();

        if (! $subscription) {
            return response()->json(['message' => 'Subscription not found.'], 404);
        }

        $subscription->delete();

        $this->logger->log(
            subject: $subscription,
            description: 'Unsubscribed from profile',
            causer: $request->user(),
            logName: 'subscriptions'
        );

        return response()->json(['message' => 'Unsubscribed successfully.']);
    }
}
