<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a newly created comment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:500',
            'item_id' => 'required|exists:items,id',
        ], [
            'content.required' => 'Поле комментария обязательно для заполнения.',
            'content.string' => 'Комментарий должен быть строкой.',
            'content.max' => 'Комментарий не должен превышать 500 символов.',
            'item_id.required' => 'ID элемента обязателен.',
            'item_id.exists' => 'Выбранный элемент не существует.',
        ]);

        $comment = new Comment();
        $comment->content = $request->content;
        $comment->user_id = Auth::id();
        $comment->item_id = $request->item_id;
        $comment->save();

        return redirect()->back()->with('success', 'Комментарий успешно добавлен.');
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Comment $comment)
    {
        // Check if the user is the owner of the comment or an admin
        if ($comment->user_id !== Auth::id() && !Auth::user()->is_admin) {
            abort(403, 'You do not have permission to delete this comment.');
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Комментарий удален успешно.');
    }

    /**
     * Toggle friendship between users
     */
    public function toggleFriendship($userId)
    {
        $user = Auth::user();

        // Try to find the friend by ID first, then by username
        $friend = \App\Models\User::find($userId);
        if (!$friend) {
            $friend = \App\Models\User::where('username', $userId)->firstOrFail();
        }

        if ($user->id == $friend->id) {
            return redirect()->back()->with('error', 'Вы не можете добавить себя в друзья.');
        }

        // Check if the friendship already exists
        $existingFriendship = \DB::table('friendships')
            ->where([
                ['user_id', $user->id],
                ['friend_id', $friend->id]
            ])
            ->first();

        if ($existingFriendship) {
            // Remove the friendship
            \DB::table('friendships')
                ->where([
                    ['user_id', $user->id],
                    ['friend_id', $friend->id]
                ])
                ->delete();

            // Also remove the reciprocal friendship
            \DB::table('friendships')
                ->where([
                    ['user_id', $friend->id],
                    ['friend_id', $user->id]
                ])
                ->delete();

            return redirect()->back()->with('success', 'Друг удален успешно.');
        } else {
            // Create the friendship relationship
            \DB::table('friendships')->insert([
                'user_id' => $user->id,
                'friend_id' => $friend->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Also create the reciprocal friendship (both users become friends with each other)
            \DB::table('friendships')->insert([
                'user_id' => $friend->id,
                'friend_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Друг добавлен успешно.');
        }
    }
}
