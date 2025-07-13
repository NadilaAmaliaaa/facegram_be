<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    //POST
    Route::post('/posts', [PostController::class, 'create']);
    Route::delete('/posts/{id}', [PostController::class, 'delete']);
    Route::get('/posts', [PostController::class, 'getPosts']);

    //FOLLOW
    Route::post('/{username}/follow', [FollowController::class, 'follow']);
    Route::post('/{username}/unfollow', [FollowController::class, 'unfollow']);
    Route::get('/following', [FollowController::class, 'getFollowing']);
    Route::put('/{username}/accept', [FollowController::class, 'acceptFollowRequest']);
    Route::get('/{username}/followers', [FollowController::class, 'getFollowers']);

    //USER
    Route::get('/users', [UserController::class, 'getUsers']);
    Route::get('/users/{username}', [UserController::class, 'getDetailedUsers']);
});
