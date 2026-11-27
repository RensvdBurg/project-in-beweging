<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function registration(array $overrides = []): array
    {
        return $overrides + [
            'email' => 'sanne@example.com',
            'username' => 'sanne',
            'full_name' => 'Sanne Jansen',
            'password' => 'geheim123',
            'password_confirmation' => 'geheim123',
            'birth_date' => '2006-05-01',
            'device_name' => 'Pixel 8',
        ];
    }

    public function test_register_returns_token_and_user(): void
    {
        $response = $this->postJson(route('api.v1.register'), $this->registration())
            ->assertCreated()
            ->assertJsonPath('user.username', 'sanne')
            ->assertJsonPath('user.email', 'sanne@example.com')
            ->assertJsonPath('user.is_admin', false);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 8']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'register']);
    }

    public function test_register_validates_like_the_app(): void
    {
        User::factory()->create(['username' => 'Sanne']);

        $this->postJson(route('api.v1.register'), $this->registration(['password_confirmation' => 'anders123', 'birth_date' => '2999-01-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password_confirmation', 'birth_date'])
            ->assertJsonPath('errors.username.0', 'Dit e-mailadres of deze gebruikersnaam is al in gebruik.');
    }

    public function test_register_cannot_make_an_admin(): void
    {
        $this->postJson(route('api.v1.register'), $this->registration(['is_admin' => true]))->assertCreated();

        $this->assertFalse(User::query()->where('username', 'sanne')->firstOrFail()->is_admin);
    }

    public function test_login_with_username_or_email_ignoring_case(): void
    {
        User::factory()->create(['username' => 'sanne', 'email' => 'sanne@example.com']);

        $this->postJson(route('api.v1.login'), ['login' => 'SANNE', 'password' => 'password'])->assertOk()->assertJsonStructure(['token', 'user']);
        $this->postJson(route('api.v1.login'), ['login' => 'Sanne@Example.com', 'password' => 'password'])->assertOk();
    }

    public function test_wrong_password_is_rejected_and_logged(): void
    {
        User::factory()->create(['username' => 'sanne']);

        $this->postJson(route('api.v1.login'), ['login' => 'sanne', 'password' => 'fout'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', 'E-mailadres/gebruikersnaam of wachtwoord is onjuist.');

        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed']);
    }

    public function test_login_is_limited_to_five_attempts_per_account(): void
    {
        User::factory()->create(['username' => 'sanne']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('api.v1.login'), ['login' => 'sanne', 'password' => 'fout']);
        }

        $this->postJson(route('api.v1.login'), ['login' => 'sanne', 'password' => 'password'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_protected_routes_need_a_token(): void
    {
        $this->getJson(route('api.v1.me'))->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $phone = $user->createToken('telefoon')->plainTextToken;
        $user->createToken('tablet');

        $this->withToken($phone)->postJson(route('api.v1.logout'))->assertOk();

        $this->assertSame(['tablet'], $user->tokens()->pluck('name')->all());
    }
}
