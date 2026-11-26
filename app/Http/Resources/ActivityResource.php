<?php

namespace App\Http\Resources;

use App\Models\Activity;
use App\Models\ActivityComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Een activiteit met maker, likes en reacties. Verwacht een query met
 * `Activity::forFeed($viewer)` (counts, liked_by_viewer, comments.user).
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->imageUrl(),
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => [
                'id' => $this->user_id,
                'username' => $this->user->username ?? null,
            ],
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'liked_by_viewer' => (bool) ($this->liked_by_viewer ?? false),
            'comments' => $this->whenLoaded('comments', fn () => $this->comments->map(fn (ActivityComment $comment): array => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'user' => ['id' => $comment->user_id, 'username' => $comment->user->username ?? null],
                'can_delete' => $comment->user_id === $viewerId,
            ])->values()),
        ];
    }
}
