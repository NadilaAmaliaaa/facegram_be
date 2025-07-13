<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'caption' => 'required|string',
            'attachments' => 'required|array',
            'attachments.*' => 'image|mimes:jpg,jpeg,webp,png,gif|max:2048',
        ]);

        $user = Auth::user();

        $post = Post::create([
            'caption' => $request->caption,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        foreach ($request->file('attachments') as $image) {
            $path = $image->store('post_attachments', 'public');

            PostAttachment::create([
                'post_id' => $post->id,
                'storage_path' => $path,
            ]);
        }

        return response()->json([
            'message' => 'Post created successfully',
            'post' => [
                'id' => $post->id,
                'caption' => $post->caption,
                'user_id' => $post->user_id,
                'attachments' => $post->attachments()->pluck('storage_path'),
                'created_at' => $post->created_at,
            ]
        ], 201);
    }
    public function delete($id)
    {
        $user = Auth::user();

        $post = Post::with(['attachments'])->find($id);

        // Post tidak ditemukan
        if (!$post) {
            return response()->json([
                'message' => 'Post not found',
            ], 404);
        }

        // Bukan milik user login
        if ($post->user_id !== $user->id) {
            return response()->json([
                'message' => 'Forbidden access',
            ], 403);
        }

        // Hapus file dari storage dan soft-delete attachment
        foreach ($post->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->storage_path);
            $attachment->delete(); // soft delete
        }

        // Soft delete post
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ], 200);
    }
    public function getPosts(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:0',
            'size' => 'nullable|integer|min:1',
        ], [
            'page.min' => 'The page field must be at least 0.',
            'size.integer' => 'The size field must be a number.',
            'size.min' => 'The size field must be at least 1.',
        ]);

        $page = $validated['page'] ?? 0;
        $size = $validated['size'] ?? 10;

        $posts = Post::with(['user', 'attachments'])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->skip($page * $size)
            ->take($size)
            ->get();

        return response()->json([
            'page' => $page,
            'size' => $size,
            'posts' => $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'caption' => $post->caption,
                    'created_at' => $post->created_at,
                    'deleted_at' => $post->deleted_at,
                    'user' => [
                        'id' => $post->user->id,
                        'full_name' => $post->user->full_name,
                        'username' => $post->user->username,
                        'bio' => $post->user->bio,
                        'is_private' => (int) $post->user->is_private,
                        'created_at' => $post->user->created_at,
                    ],
                    'attachments' => $post->attachments->map(fn($a) => [
                        'id' => $a->id,
                        'storage_path' => $a->storage_path,
                    ]),
                ];
            }),
        ]);
    }
}

