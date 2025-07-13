<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    public function follow($username)
    {
        $follower = Auth::user();

        // Temukan user berdasarkan username
        $targetUser = User::where('username', $username)->first();

        if (!$targetUser) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Tidak boleh follow diri sendiri
        if ($follower->id === $targetUser->id) {
            return response()->json([
                'message' => 'You are not allowed to follow yourself'
            ], 422);
        }

        // Cek apakah sudah pernah follow
        $existing = Follow::where('follower_id', $follower->id)
                          ->where('following_id', $targetUser->id)
                          ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You are already followed',
                'status' => $existing->is_accepted ? 'following' : 'requested'
            ], 422);
        }

        $isAccepted = !$targetUser->is_private;

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $targetUser->id,
            'is_accepted' => $isAccepted,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Follow success',
            'status' => $isAccepted ? 'following' : 'requested',
        ], 200);
    }
    public function unfollow($username){
        $follower = Auth::user();

        // Temukan user target berdasarkan username
        $targetUser = User::where('username', $username)->first();

        if (!$targetUser) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Cegah unfollow diri sendiri (opsional, bisa dilewatkan)
        if ($follower->id === $targetUser->id) {
            return response()->json([
                'message' => 'You cannot unfollow yourself'
            ], 422);
        }

        // Cari relasi follow
        $follow = Follow::where('follower_id', $follower->id)
                        ->where('following_id', $targetUser->id)
                        ->first();

        if (!$follow) {
            return response()->json([
                'message' => 'You are not following the user'
            ], 422);
        }

        // Hapus follow
        $follow->delete();

        return response()->json([
            'message' => 'Successfully unfollowing ' . $targetUser->username,
        ], 200);
    }
    public function getFollowing()
    {
        $user = Auth::user();

        $following = Follow::with('following')
            ->where('follower_id', $user->id)
            ->get()
            ->map(function ($follow) {
                $followedUser = $follow->following;
                return [
                    'id' => $followedUser->id,
                    'full_name' => $followedUser->full_name,
                    'username' => $followedUser->username,
                    'bio' => $followedUser->bio,
                    'is_private' => (int) $followedUser->is_private,
                    'created_at' => $followedUser->created_at,
                    'is_requested' => !$follow->is_accepted,
                ];
            });

        return response()->json([
            'following' => $following,
        ], 200);
    }
     public function acceptFollowRequest($username)
    {
        $me = Auth::user(); // user yang sedang login (target)
        $requester = User::where('username', $username)->first(); // user yang mengajukan follow

        if (!$requester) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Cek apakah user tersebut mengikuti kamu (follower_id = requester, following_id = me)
        $follow = Follow::where('follower_id', $requester->id)
                        ->where('following_id', $me->id)
                        ->first();

        if (!$follow) {
            return response()->json([
                'message' => 'The user is not following you'
            ], 422);
        }

        if ($follow->is_accepted) {
            return response()->json([
                'message' => 'Follow request is already accepted'
            ], 422);
        }

        // Terima permintaan follow
        $follow->update([
            'is_accepted' => true
        ]);

        return response()->json([
            'message' => $requester->username . ' follow request accepted'
        ], 200);
    }
}
