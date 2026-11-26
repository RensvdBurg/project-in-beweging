<?php

namespace App\Http\Resources;

use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Challenge
 */
class ChallengeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description_extra' => $this->description_extra,
            // `img` = pad onder public/ of een URL; de app heeft dezelfde afbeeldingen in public/.
            'img' => $this->img,
            'image_url' => $this->imageUrl(),
            'kcal_burned' => (int) $this->kcal_burned,
            'difficulty' => $this->difficulty,
            'xp_reward' => (int) $this->xp_reward,
        ];
    }
}
