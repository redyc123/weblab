<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CommentResource;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $comments = Comment::with(['user', 'item'])->paginate(10);
        
        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string|max:500',
            'item_id' => 'required|exists:items,id',
        ]);

        $user = Auth::user();
        $item = Item::findOrFail($request->item_id);

        $comment = new Comment();
        $comment->content = $request->content;
        $comment->user_id = $user->id;
        $comment->item_id = $request->item_id;
        $comment->save();

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
            'message' => 'Comment created successfully.'
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Comment $comment
     * @return JsonResponse
     */
    public function show(Comment $comment): JsonResponse
    {
        $user = Auth::user();
        $is_friend = false;
        
        // Check if the user is friend of the comment owner
        if ($user) {
            $is_friend = $user->following()->where('friend_id', $comment->user_id)->exists();
        }
        
        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
            'is_friend' => $is_friend
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Comment $comment
     * @return JsonResponse
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $user = Auth::user();
        if ($comment->user_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this comment.'
            ], 403);
        }

        $data = $request->validate([
            'content' => 'required|string|max:500',
            'item_id' => 'required|exists:items,id',
        ]);

        $comment->update($data);

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
            'message' => 'Comment updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Comment $comment
     * @return JsonResponse
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $user = Auth::user();
        if ($comment->user_id !== $user->id && !$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this comment.'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully.'
        ]);
    }
}