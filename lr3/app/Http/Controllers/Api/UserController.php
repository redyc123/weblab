<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        $users = User::where('id', '!=', $user->id) // Exclude current user from list
                    ->paginate(10);

        // Add friendship status to each user
        $users->getCollection()->transform(function ($userItem) use ($user) {
            $is_friend = $user->following()->where('friend_id', $userItem->id)->exists();
            $userItem->setAttribute('is_friend', $is_friend);
            return $userItem;
        });

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users)
        ]);
    }

    /**
     * Display the specified user and their items.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $authUser = Auth::user();
        
        // Check if the authenticated user can view this user's profile
        // Any authenticated user can view another user's profile
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        // Check if users are friends
        $is_friend = $authUser->following()->where('friend_id', $user->id)->exists();
        
        // Get the user's items
        $items = Item::where('user_id', $user->id)
                     ->with(['user', 'comments'])
                     ->orderBy('created_at', 'desc')
                     ->paginate(10);

        $userData = new UserResource($user);
        $userData->additional(['is_friend' => $is_friend, 'items' => $items]);

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    /**
     * Toggle friendship status with the specified user.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function toggleFriendship(Request $request, User $user): JsonResponse
    {
        $authUser = Auth::user();
        
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);
        }

        if ($authUser->id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot add yourself as a friend.'
            ], 400);
        }

        // Check if friendship already exists
        $existingFriendship = \DB::table('friendships')
            ->where([
                ['user_id', $authUser->id],
                ['friend_id', $user->id]
            ])
            ->first();

        if ($existingFriendship) {
            // Remove friendship
            \DB::table('friendships')
                ->where([
                    ['user_id', $authUser->id],
                    ['friend_id', $user->id]
                ])
                ->delete();

            // Also remove the reverse friendship
            \DB::table('friendships')
                ->where([
                    ['user_id', $user->id],
                    ['friend_id', $authUser->id]
                ])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Friend successfully removed.',
                'is_friend' => false
            ]);
        } else {
            // Create friendship
            \DB::table('friendships')->insert([
                'user_id' => $authUser->id,
                'friend_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create reverse friendship (mutual friendship)
            \DB::table('friendships')->insert([
                'user_id' => $user->id,
                'friend_id' => $authUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Friend successfully added.',
                'is_friend' => true
            ]);
        }
    }
}