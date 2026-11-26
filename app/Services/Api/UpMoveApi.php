<?php

namespace App\Services\Api;

use App\Models\Activity;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Alle API-aanroepen van de app op één plek. Geeft modellen terug (via ApiHydrator)
 * zodat controllers en views hetzelfde blijven werken als met de database.
 * Fouten komen als ApiException met een Nederlandse melding.
 */
class UpMoveApi
{
    public function __construct(
        private readonly ApiClient $client,
        private readonly ApiHydrator $hydrator,
    ) {}

    // ===== Inloggen =====

    /**
     * @return array{token: string, user: array<string, mixed>}
     */
    public function login(string $login, string $password, string $deviceName): array
    {
        $response = $this->client->post('login', ['login' => $login, 'password' => $password, 'device_name' => $deviceName]);

        return ['token' => (string) $response['token'], 'user' => (array) $response['user']];
    }

    /**
     * @param  array<string, mixed>  $data  email, username, full_name, password, password_confirmation, birth_date, device_name
     * @return array{token: string, user: array<string, mixed>}
     */
    public function register(array $data): array
    {
        $response = $this->client->post('register', $data);

        return ['token' => (string) $response['token'], 'user' => (array) $response['user']];
    }

    public function logout(): void
    {
        $this->client->post('logout');
    }

    // ===== Eigen account =====

    /**
     * @return array{user: array<string, mixed>, rank: int}
     */
    public function me(): array
    {
        $response = $this->client->get('me');

        return ['user' => (array) $response['user'], 'rank' => (int) $response['rank']];
    }

    /**
     * @return array{message: string, user: array<string, mixed>}
     */
    public function updateProfile(string $fullName, string $username, string $email): array
    {
        $response = $this->client->put('me/profile', ['full_name' => $fullName, 'username' => $username, 'email' => $email]);

        return ['message' => (string) $response['message'], 'user' => (array) $response['user']];
    }

    /**
     * @return array{message: string, user: array<string, mixed>}
     */
    public function updateAbout(string $aboutMe): array
    {
        $response = $this->client->put('me/about', ['about_me' => $aboutMe]);

        return ['message' => (string) $response['message'], 'user' => (array) $response['user']];
    }

    public function updatePassword(string $current, string $new, string $confirmation): string
    {
        return (string) $this->client->put('me/password', [
            'current_password' => $current,
            'new_password' => $new,
            'new_password_confirmation' => $confirmation,
        ])['message'];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public function updateSettings(array $settings): array
    {
        return (array) $this->client->put('me/settings', $settings)['settings'];
    }

    public function deleteAccount(): void
    {
        $this->client->delete('me', ['confirm_delete' => true]);
    }

    // ===== Home en challenges =====

    /**
     * Alles voor het home-scherm in één verzoek.
     *
     * @return array{user: array<string, mixed>, rank: int, state: array{challenge: ?Challenge, completed: bool, no_different_available: bool, changes_remaining: int, next_change_at: int}, feed: Collection<int, Activity>, feedTotal: int, rankings: Collection<int, User>, challenges: Collection<int, Challenge>}
     */
    public function home(): array
    {
        $response = $this->client->get('home');

        return [
            'user' => (array) $response['user'],
            'rank' => (int) $response['rank'],
            'state' => $this->challengeState((array) $response['challenge']),
            'feed' => new Collection(array_map(fn (array $item): Activity => $this->hydrator->activity($item), (array) ($response['feed']['data'] ?? []))),
            'feedTotal' => (int) ($response['feed']['total'] ?? 0),
            'rankings' => new Collection(array_map(fn (array $item): User => $this->hydrator->user($item), (array) $response['rankings'])),
            'challenges' => $this->hydrator->challenges((array) $response['challenges']),
        ];
    }

    public function challenge(int $id): Challenge
    {
        return $this->hydrator->challenge((array) $this->client->get("challenges/{$id}")['data']);
    }

    public function nextChallenge(): string
    {
        return (string) $this->client->post('challenges/next')['message'];
    }

    public function completeChallenge(): string
    {
        return (string) $this->client->post('challenges/complete')['message'];
    }

    // ===== Profielen =====

    /**
     * @return array{user: User, rank: int}
     */
    public function profile(int $userId): array
    {
        $response = $this->client->get("users/{$userId}");

        return ['user' => $this->hydrator->user((array) $response['user']), 'rank' => (int) $response['rank']];
    }

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function userActivities(int $userId, int $page, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        return $this->hydrator->activityPage(
            $this->client->get("users/{$userId}/activities", ['page' => $page, 'per_page' => $perPage]),
            $pageName,
        );
    }

    // ===== Activiteiten =====

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function feed(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->hydrator->activityPage($this->client->get('activities', ['page' => $page, 'per_page' => $perPage]));
    }

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function myActivities(int $page, int $perPage): LengthAwarePaginator
    {
        return $this->hydrator->activityPage($this->client->get('me/activities', ['page' => $page, 'per_page' => $perPage]));
    }

    /**
     * @param  array{path: string, name: string}|null  $image
     */
    public function storeActivity(string $title, string $description, ?array $image): string
    {
        $files = $image !== null ? ['image' => $image] : [];

        return (string) $this->client->post('activities', ['title' => $title, 'description' => $description], $files)['message'];
    }

    public function activity(int $id): Activity
    {
        return $this->hydrator->activity((array) $this->client->get("activities/{$id}")['data']);
    }

    public function toggleLike(int $activityId): Activity
    {
        return $this->hydrator->activity((array) $this->client->post("activities/{$activityId}/like")['data']);
    }

    public function comment(int $activityId, string $body): Activity
    {
        return $this->hydrator->activity((array) $this->client->post("activities/{$activityId}/comments", ['body' => $body])['data']);
    }

    public function deleteComment(int $commentId): Activity
    {
        return $this->hydrator->activity((array) $this->client->delete("comments/{$commentId}")['data']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{challenge: ?Challenge, completed: bool, no_different_available: bool, changes_remaining: int, next_change_at: int}
     */
    private function challengeState(array $data): array
    {
        return [
            'challenge' => is_array($data['challenge'] ?? null) ? $this->hydrator->challenge($data['challenge']) : null,
            'completed' => (bool) ($data['completed'] ?? false),
            'no_different_available' => (bool) ($data['no_different_available'] ?? false),
            'changes_remaining' => (int) ($data['changes_remaining'] ?? 0),
            'next_change_at' => (int) ($data['next_change_at'] ?? 0),
        ];
    }
}
