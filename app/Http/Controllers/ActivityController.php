<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ActivityController extends Controller
{
    public function myActivity(): JsonResponse
    {
        $user = Auth::user();

        $activities = Activity::where('causer_id', $user->id)
            ->where('causer_type', get_class($user))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($activity) => $this->formatActivity($activity));

        return response()->json($activities);
    }

    public function allAdminActivities(): JsonResponse
    {
        $adminIds = User::whereIn('role', ['admin', 'superadmin'])->pluck('id');

        $activities = Activity::whereIn('causer_id', $adminIds)
            ->where('causer_type', User::class)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($activity) => $this->formatActivity($activity));

        return response()->json($activities);
    }


    private function formatActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'subject_id' => $activity->subject_id,
            'subject_type' => $activity->subject_type,
            'causer_id' => $activity->causer_id,
            'causer_type' => $activity->causer_type,
            'properties' => $activity->properties,
            'event' => $activity->event ?? 'N/A',
            'created_at' => $activity->created_at->toISOString(),
            'updated_at' => $activity->updated_at->toISOString(),
            'status' => 'success',
        ];
    }
}
