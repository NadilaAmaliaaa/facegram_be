<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getUsers() //get unfollowed users
    {
        $user = Auth::user();

        // Ambil semua ID yang sudah di-follow
        $followedIds = Follow::where('follower_id', $user->id)
            ->pluck('following_id')
            ->toArray();

        // Tambahkan juga ID user login agar tidak muncul di list
        $excludedIds = array_merge($followedIds, [$user->id]);

        // Ambil semua user yang belum di-follow & bukan diri sendiri
        $users = User::whereNotIn('id', $excludedIds)->get();

        return response()->json([
            'users' => $users
        ], 200);
    }
    public function getDetailedUsers($username){
                $authUser = Auth::user();

        $targetUser = User::where('username', $username)->first();

        if (!$targetUser) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Cek apakah ini adalah akun sendiri
        $isYourAccount = $authUser->id === $targetUser->id;

        // Cek status follow
        $follow = Follow::where('follower_id', $authUser->id)
                        ->where('following_id', $targetUser->id)
                        ->first();

        if ($isYourAccount) {
            $followStatus = 'following';
        } elseif (!$follow) {
            $followStatus = 'not-following';
        } elseif (!$follow->is_accepted) {
            $followStatus = 'requested';
        } else {
            $followStatus = 'following';
        }

        // Ambil count followers, following, posts
        $followersCount = Follow::where('following_id', $targetUser->id)->count();
        $followingCount = Follow::where('follower_id', $targetUser->id)->count();
        $postsQuery = $targetUser->posts()->with(['attachments'])->whereNull('deleted_at');
        $postsCount = $postsQuery->count();

        // Hanya tampilkan posts jika: akun sendiri, atau public, atau sudah di-follow
        $shouldShowPosts = $isYourAccount || !$targetUser->is_private || $followStatus === 'following';

        return response()->json([
            'id' => $targetUser->id,
            'full_name' => $targetUser->full_name,
            'username' => $targetUser->username,
            'bio' => $targetUser->bio,
            'is_private' => (int) $targetUser->is_private,
            'created_at' => $targetUser->created_at,
            'is_your_account' => $isYourAccount,
            'following_status' => $followStatus,
            'posts_count' => $postsCount,
            'followers_count' => $followersCount,
            'following_count' => $followingCount,
            'posts' => $shouldShowPosts
                ? $postsQuery->get()->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'caption' => $post->caption,
                        'created_at' => $post->created_at,
                        'deleted_at' => $post->deleted_at,
                        'attachments' => $post->attachments->map(function ($a) {
                            return [
                                'id' => $a->id,
                                'storage_path' => $a->storage_path
                            ];
                        })
                    ];
                })
                : []
        ], 200);
    }
}
