<?php

namespace App\Services\Api;

use App\Models\Activity;
use App\Models\ActivityComment;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Zet JSON van de API om naar dezelfde modellen die de views al gebruiken
 * (alleen in het geheugen: ze worden nooit opgeslagen in een database).
 */
class ApiHydrator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function user(array $data): User
    {
        $attributes = array_intersect_key($data, array_flip([
            'id', 'username', 'full_name', 'about_me', 'xp', 'email', 'birth_date', 'is_admin', 'created_at',
        ]));

        // De 'array'-cast verwacht JSON, zoals die uit de database komt.
        if (array_key_exists('settings', $data)) {
            $attributes['settings'] = $data['settings'] === null ? null : json_encode($data['settings']);
        }

        return $this->model(new User, $attributes);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function challenge(array $data): Challenge
    {
        return $this->model(new Challenge, array_intersect_key($data, array_flip([
            'id', 'title', 'description_extra', 'img', 'kcal_burned', 'difficulty', 'xp_reward',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return Collection<int, Challenge>
     */
    public function challenges(array $items): Collection
    {
        return new Collection(array_map(fn (array $item): Challenge => $this->challenge($item), $items));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function activity(array $data): Activity
    {
        $activity = $this->model(new Activity, [
            'id' => $data['id'],
            'user_id' => $data['user']['id'] ?? null,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'created_at' => $data['created_at'] ?? null,
            'likes_count' => (int) ($data['likes_count'] ?? 0),
            'comments_count' => (int) ($data['comments_count'] ?? 0),
            'liked_by_viewer' => (bool) ($data['liked_by_viewer'] ?? false),
        ]);

        // De afbeelding staat op de server; Activity::imageUrl() geeft deze URL terug.
        $activity->setRemoteImageUrl(is_string($data['image_url'] ?? null) ? $data['image_url'] : null);
        $activity->setRelation('user', $this->user((array) ($data['user'] ?? [])));

        $comments = array_map(function (array $comment): ActivityComment {
            $model = $this->model(new ActivityComment, [
                'id' => $comment['id'],
                'user_id' => $comment['user']['id'] ?? null,
                'body' => $comment['body'] ?? '',
                'created_at' => $comment['created_at'] ?? null,
            ]);
            $model->setRelation('user', $this->user((array) ($comment['user'] ?? [])));

            return $model;
        }, (array) ($data['comments'] ?? []));

        $activity->setRelation('comments', new Collection($comments));

        return $activity;
    }

    /**
     * Een gepagineerd API-antwoord (Laravel-resourcecollectie met `data` en `meta`)
     * als paginator, zodat de bestaande paginering in de views blijft werken.
     *
     * @param  array<string, mixed>  $response
     * @return LengthAwarePaginator<int, Activity>
     */
    public function activityPage(array $response, string $pageName = 'page'): LengthAwarePaginator
    {
        $meta = (array) ($response['meta'] ?? []);
        $items = array_map(fn (array $item): Activity => $this->activity($item), (array) ($response['data'] ?? []));

        return new LengthAwarePaginator(
            $items,
            (int) ($meta['total'] ?? count($items)),
            max(1, (int) ($meta['per_page'] ?? 5)),
            max(1, (int) ($meta['current_page'] ?? 1)),
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => $pageName],
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    private function model(Model $model, array $attributes): Model
    {
        // newFromBuilder: geldt als "bestaand" model (exists = true), zonder databasevraag.
        return $model->newFromBuilder($attributes);
    }
}
