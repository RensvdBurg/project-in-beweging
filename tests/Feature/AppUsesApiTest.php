<?php

namespace Tests\Feature;

use App\Auth\ApiSessionGuard;
use App\Services\Api\ApiToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Native\Mobile\Facades\SecureStorage;
use Tests\TestCase;

/**
 * De app (zoals op de telefoon): alle gegevens komen via HTTP van de API,
 * er wordt niets in een lokale database gelezen of geschreven.
 */
class AppUsesApiTest extends TestCase
{
    private const API = 'https://server.test/api/v1';

    protected function setUp(): void
    {
        parent::setUp();

        config(['upmove.api_in_process' => false, 'upmove.api_url' => self::API]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(array $overrides = []): array
    {
        return $overrides + [
            'id' => 7, 'username' => 'sanne', 'full_name' => 'Sanne Jansen', 'initials' => 'SJ', 'about_me' => '',
            'xp' => 120, 'created_at' => '2026-11-01T10:00:00+01:00', 'email' => 'sanne@example.com',
            'birth_date' => '2006-05-01', 'is_admin' => false, 'settings' => null,
        ];
    }

    private function loggedIn(): static
    {
        return $this->withSession([ApiToken::SESSION_KEY => 'test-token', ApiSessionGuard::USER_KEY => $this->userData()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function homeResponse(): array
    {
        $challenge = ['id' => 52, 'title' => 'Loop twee minuten op de gang', 'description_extra' => 'Rustig lopen.', 'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg', 'image_url' => 'https://server.test/images/x.jpg', 'kcal_burned' => 8, 'difficulty' => 'Beginner', 'xp_reward' => 10];

        return [
            'user' => $this->userData(),
            'rank' => 3,
            'challenge' => ['challenge' => $challenge, 'completed' => false, 'no_different_available' => false, 'changes_remaining' => 2, 'next_change_at' => now()->addHour()->getTimestamp(), 'xp' => 120],
            'feed' => ['data' => [[
                'id' => 1, 'title' => 'Fietsen naar school', 'description' => '12 km', 'image_url' => null, 'created_at' => '2026-11-27T08:00:00+01:00',
                'user' => ['id' => 8, 'username' => 'bram'], 'likes_count' => 2, 'comments_count' => 1, 'liked_by_viewer' => true,
                'comments' => [['id' => 3, 'body' => 'Goed bezig!', 'created_at' => '2026-11-27T09:00:00+01:00', 'user' => ['id' => 7, 'username' => 'sanne'], 'can_delete' => true]],
            ]], 'total' => 1],
            'rankings' => [$this->userData(['id' => 8, 'username' => 'bram', 'full_name' => 'Bram de Vries', 'xp' => 300])],
            'challenges' => [$challenge],
        ];
    }

    public function test_home_comes_from_the_api_without_any_database_query(): void
    {
        Http::fake([self::API.'/home' => Http::response($this->homeResponse())]);
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->loggedIn()->get(route('home'))
            ->assertOk()
            ->assertSee('Loop twee minuten op de gang')
            ->assertSee('Fietsen naar school')
            ->assertSee('Goed bezig!')
            ->assertSee('Bram de Vries');

        $this->assertSame(0, $queries, 'De app mag geen database gebruiken.');
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === self::API.'/home' && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_login_stores_the_token_from_the_api(): void
    {
        Http::fake([self::API.'/login' => Http::response(['token' => 'nieuw-token', 'user' => $this->userData()])]);

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'geheim123'])
            ->assertRedirect(route('home'))
            ->assertSessionHas(ApiToken::SESSION_KEY, 'nieuw-token');

        $this->assertAuthenticated();
        Http::assertSent(fn (ClientRequest $request): bool => $request['login'] === 'sanne' && $request['password'] === 'geheim123');
    }

    public function test_wrong_login_shows_the_message_from_the_api(): void
    {
        Http::fake([self::API.'/login' => Http::response(['message' => 'Onjuist', 'errors' => ['login' => ['E-mailadres/gebruikersnaam of wachtwoord is onjuist.']]], 422)]);

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'fout'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_message', 'E-mailadres/gebruikersnaam of wachtwoord is onjuist.');

        $this->assertGuest();
    }

    public function test_no_connection_shows_the_offline_page(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->loggedIn()->get(route('home'))
            ->assertStatus(503)
            ->assertSee('Geen verbinding')
            ->assertSee('Opnieuw proberen');
    }

    public function test_revoked_token_logs_out_and_goes_to_login(): void
    {
        Http::fake([self::API.'/home' => Http::response(['message' => 'Unauthenticated.'], 401)]);

        $this->loggedIn()->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing(ApiToken::SESSION_KEY)
            ->assertSessionHas('auth_message', 'Je sessie is verlopen. Log opnieuw in.');
    }

    public function test_phone_stays_logged_in_with_token_from_secure_storage(): void
    {
        config(['nativephp-internal.running' => true]);
        SecureStorage::shouldReceive('get')->with('upmove_api_token')->andReturn('telefoon-token');
        Http::fake([self::API.'/me' => Http::response(['user' => $this->userData(), 'rank' => 3])]);

        $this->get(route('statistics'))->assertOk();

        $this->assertAuthenticated();
        Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('Authorization', 'Bearer telefoon-token'));
    }

    public function test_app_data_migrations_are_skipped_on_the_phone(): void
    {
        config(['nativephp-internal.running' => true]);

        $users = require database_path('migrations/0001_01_01_000000_create_users_table.php');
        $challenges = require database_path('migrations/2026_11_19_150000_add_bewegingsapp_challenges.php');
        $cleanup = require database_path('migrations/2026_11_28_090000_drop_local_app_data_on_phone.php');

        $this->assertFalse($users->shouldRun());
        $this->assertFalse($challenges->shouldRun());
        $this->assertTrue($cleanup->shouldRun());

        config(['nativephp-internal.running' => false]);
        $this->assertTrue($users->shouldRun());
        $this->assertFalse($cleanup->shouldRun());
    }
}
