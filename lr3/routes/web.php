<?php

use App\Http\Controllers\ItemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('items.index');
});

Route::middleware(['auth'])->group(function () {
    // Routes for admin functions - define these BEFORE the resource route
    // to prevent route model binding conflicts
    Route::get('/items/trashed', [ItemController::class, 'trashed'])->name('items.trashed');
    Route::delete('/items/{id}/force', [ItemController::class, 'forceDestroy'])->name('items.forceDestroy');
    // Also add a POST route for force delete (for forms that don't support method spoofing)
    Route::post('/items/{id}/force', [ItemController::class, 'forceDestroy'])->name('items.forceDestroy.post');
    Route::post('/items/{id}/restore', [ItemController::class, 'restore'])->name('items.restore');

    // Feed route for user's friends' items
    Route::get('/feed', [ItemController::class, 'feed'])->name('items.feed');

    // Now define the resource route after specific routes
    Route::resource('items', ItemController::class);

    // Comments routes
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Friendship routes
    Route::post('/users/{user}/toggle-friendship', [UserController::class, 'toggleFriendship'])->name('users.toggle-friendship');

    Route::get('/users', [ItemController::class, 'usersIndex'])->name('users.index');
    Route::get('/users/browse', [ItemController::class, 'browseUsers'])->name('users.browse');
    Route::get('/users/{user}', [ItemController::class, 'userItems'])->name('users.items');

    // Profile route for API tokens
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile/token', [ProfileController::class, 'createToken'])->name('profile.token.create');
    Route::delete('/profile/token/{tokenId}', [ProfileController::class, 'deleteToken'])->name('profile.token.delete');

    // Temporary test route
    Route::get('/test-trashed', function () {
        return 'Test trashed route works!';
    });

    // Test route for trashed items with different method name
    Route::get('/items/trashed-test', [ItemController::class, 'trashed'])->name('items.trashed.test');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/test-columns', function () {
    return Schema::getColumnListing('items');
});

require __DIR__.'/auth.php';
