<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected array $roleHierarchy = [
        'user' => 0,
        'moderator' => 1,
        'admin' => 2,
        'superadmin' => 3,
    ];

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);


        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Invalid current password.'], 403);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function destroyAccount(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Invalid current password.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }


    public function destroyUserAccount(Request $request, $id)
    {
        $authUser = Auth::user();

        try {
            $targetUser = User::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $authRole = $this->roleHierarchy[$authUser->role];
        $targetRole = $this->roleHierarchy[$targetUser->role];

        if ($targetUser->role === 'superadmin') {
            return response()->json(['error' => 'You are not allowed to delete a superadmin account.'], 403);
        }

        if ($authRole <= $targetRole) {
            return response()->json(['error' => 'You are not allowed to delete this user.'], 403);
        }

        $targetUser->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }

    public function banUser(Request $request, $id)
    {
        $authUser = Auth::user();
        $targetUser = User::findOrFail($id);

        $authRole = $this->roleHierarchy[$authUser->role];
        $targetRole = $this->roleHierarchy[$targetUser->role];

        if ($authRole <= $targetRole) {
            return response()->json(['error' => 'You are not allowed to ban this user.'], 403);
        }

        if ($targetUser->banned_at !== null) {
            return response()->json(['error' => 'User is already banned.'], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $targetUser->banned_at = now();
        $targetUser->save();

        DB::table('bans')->insert([
            'causer' => $authUser->id,
            'target_type' => 'account',
            'target_id' => $targetUser->id,
            'content' => $request->input('reason'),
            'created_at' => now(),
        ]);

        return response()->json(['success' => 'User has been banned successfully.'], 200);
    }

    public function unbanUser(Request $request, $id)
    {
        $authUser = Auth::user();
        $targetUser = User::findOrFail($id);

        $authRole = $this->roleHierarchy[$authUser->role];
        $targetRole = $this->roleHierarchy[$targetUser->role];

        if ($authRole <= $targetRole) {
            return response()->json(['error' => 'You are not allowed to unban this user.'], 403);
        }

        if ($targetUser->banned_at === null) {
            return response()->json(['error' => 'User is not banned.'], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $targetUser->banned_at = null;
        $targetUser->save();

        DB::table('bans')->insert([
            'causer' => $authUser->id,
            'target_type' => 'account',
            'target_id' => $targetUser->id,
            'content' => 'User unbanned. Reason: ' . $request->input('reason'),
            'created_at' => now(),
        ]);

        return response()->json(['success' => 'User has been unbanned successfully.'], 200);
    }

    public function changeUserRole(Request $request, $id)
    {
        $authUser = Auth::user();
        $targetUser = User::findOrFail($id);

        $request->validate([
            'role' => 'required|string|in:user,moderator,admin,superadmin',
        ]);

        $newRole = $request->input('role');

        $authRoleLevel = $this->roleHierarchy[$authUser->role];
        $targetRoleLevel = $this->roleHierarchy[$targetUser->role];
        $newRoleLevel = $this->roleHierarchy[$newRole];

        if ($authRoleLevel <= $targetRoleLevel) {
            return response()->json(['error' => 'You are not allowed to change this user\'s role.'], 403);
        }

        if ($authUser->role === 'admin' && $newRoleLevel >= $this->roleHierarchy['admin']) {
            return response()->json(['error' => 'Admins can only assign roles up to moderator.'], 403);
        }

        $targetUser->role = $newRole;
        $targetUser->save();

        return response()->json(['message' => 'Role changed successfully.'], 200);
    }
}
