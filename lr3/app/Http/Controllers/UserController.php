<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Toggle friendship status with the specified user.
     */
    public function toggleFriendship($username)
    {
        $user = Auth::user();
        $friend = User::where('username', $username)->firstOrFail();

        if ($user->id == $friend->id) {
            return redirect()->back()->with('error', 'Вы не можете добавить себя в друзья.');
        }

        // Check if friendship already exists
        $existingFriendship = \DB::table('friendships')
            ->where([
                ['user_id', $user->id],
                ['friend_id', $friend->id]
            ])
            ->first();

        if ($existingFriendship) {
            // Remove friendship
            \DB::table('friendships')
                ->where([
                    ['user_id', $user->id],
                    ['friend_id', $friend->id]
                ])
                ->delete();

            // Also remove the reverse friendship
            \DB::table('friendships')
                ->where([
                    ['user_id', $friend->id],
                    ['friend_id', $user->id]
                ])
                ->delete();

            return redirect()->back()->with('success', 'Друг успешно удален.');
        } else {
            // Create friendship
            \DB::table('friendships')->insert([
                'user_id' => $user->id,
                'friend_id' => $friend->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create reverse friendship (mutual friendship)
            \DB::table('friendships')->insert([
                'user_id' => $friend->id,
                'friend_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Друг успешно добавлен.');
        }
    }
}