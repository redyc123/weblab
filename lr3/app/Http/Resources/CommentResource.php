<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = Auth::user();
        $is_friend = false;

        // Check if the user is friend of the comment owner
        if ($user && $this->user_id) {
            $is_friend = $user->following()->where('friend_id', $this->user_id)->exists();
        }

        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_friend' => $is_friend,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
            ] : null,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'title' => $this->item->title,
                'description' => $this->item->description,
                'price' => $this->item->price,
                'created_at' => $this->item->created_at,
            ] : null,
        ];
    }
}