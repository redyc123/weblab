<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for Items
Route::apiResource('items', \App\Http\Controllers\Api\ItemController::class)->middleware('auth:api');

// API routes for Comments
Route::apiResource('comments', \App\Http\Controllers\Api\CommentController::class)->middleware('auth:api');

// API routes for Users
Route::apiResource('users', \App\Http\Controllers\Api\UserController::class)->middleware('auth:api');
Route::post('/users/{user}/toggle-friendship', [\App\Http\Controllers\Api\UserController::class, 'toggleFriendship'])->middleware('auth:api');
