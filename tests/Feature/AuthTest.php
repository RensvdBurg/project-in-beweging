<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Aanmelden')->assertSee('Registreren');
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $this->post(route('register'), $this->registration())
            ->assertRedirect(route('home'));

        $user = User::query()->where('username', 'sanne_01')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('2005-03-14', $user->birth_date->toDateString());
        $this->assertSame(0, $user->xp);
    }

    public function test_registration_shows_dutch_validation_messages(): void
    {
        $cases = [
            ['email' => 'geen-email', 'Vul een geldig e-mailadres in.'],
            ['username' => 'a!', 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).'],
            ['password' => 'kort', 'Kies een wachtwoord van minimaal 8 en maximaal 72 letters.'],
            ['confirmPassword' => 'anders123', 'Wachtwoorden komen niet overeen.'],
            ['day' => '31', 'month' => '2', 'Kies een geldige geboortedatum.'],
        ];

        foreach ($cases as $case) {
            $message = array_pop($case);

            $this->post(route('register'), array_merge($this->registration(), $case))
                ->assertRedirect(route('login'))
                ->assertSessionHas('auth_message', $message)
                ->assertSessionHas('auth_mode', 'register');
        }

        $this->assertGuest();
    }

    public function test_duplicate_email_or_username_is_rejected(): void
    {
        User::factory()->create(['email' => 'sanne@example.com', 'username' => 'bestaand']);

        $this->post(route('register'), $this->registration())
            ->assertSessionHas('auth_message', 'Dit e-mailadres of deze gebruikersnaam is al in gebruik.');
    }

    public function test_user_can_login_with_email_or_username(): void
    {
        $user = User::factory()->create(['email' => 'sanne@example.com', 'username' => 'sanne']);

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => 'sanne@example.com', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_message(): void
    {
        User::factory()->create(['username' => 'sanne']);

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'fout-wachtwoord'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('auth_message', 'E-mailadres/gebruikersnaam of wachtwoord is onjuist.');

        $this->assertGuest();
    }

    public function test_login_page_shows_both_forms_with_labels(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeInOrder(['Welkom terug', 'E-mailadres of gebruikersnaam', 'Wachtwoord', 'Aanmelden', 'Account aanmaken'])
            ->assertSeeInOrder(['Registreren', 'Volledige naam', 'Gebruikersnaam', 'E-mailadres', 'Geboortedatum', 'Wachtwoord herhalen'])
            ->assertSee('data-password-toggle="loginPassword"', false);
    }

    public function test_login_ignores_case_of_username_and_email(): void
    {
        $user = User::factory()->create(['email' => 'sanne@example.com', 'username' => 'sanne']);

        $this->post(route('login.store'), ['email' => 'SANNE', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));

        $this->post(route('login.store'), ['email' => 'Sanne@Example.com', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_empty_login_shows_message(): void
    {
        $this->post(route('login.store'), ['email' => '', 'password' => ''])
            ->assertSessionHas('auth_message', 'Vul je e-mailadres of gebruikersnaam en je wachtwoord in.');
    }

    public function test_too_many_failed_logins_are_blocked_for_fifteen_minutes(): void
    {
        User::factory()->create(['username' => 'sanne']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'fout'])
                ->assertSessionHas('auth_message', 'E-mailadres/gebruikersnaam of wachtwoord is onjuist.');
        }

        // Ook het goede wachtwoord werkt nu even niet.
        $this->post(route('login.store'), ['email' => 'Sanne', 'password' => 'password'])
            ->assertSessionHas('auth_message', 'Te veel inlogpogingen. Probeer het over 15 minuten opnieuw.');
        $this->assertGuest();

        $this->travel(16)->minutes();

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        User::factory()->create(['username' => 'sanne']);

        foreach (range(1, 4) as $attempt) {
            $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'fout']);
        }

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'password'])->assertRedirect(route('home'));
        $this->post(route('logout'));

        foreach (range(1, 4) as $attempt) {
            $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'fout'])
                ->assertSessionHas('auth_message', 'E-mailadres/gebruikersnaam of wachtwoord is onjuist.');
        }
    }

    public function test_registration_rejects_username_and_email_in_other_case(): void
    {
        User::factory()->create(['email' => 'bestaand@example.com', 'username' => 'sanne_01']);

        $this->post(route('register'), array_merge($this->registration(), ['username' => 'SANNE_01']))
            ->assertSessionHas('auth_message', 'Dit e-mailadres of deze gebruikersnaam is al in gebruik.')
            // Het bezette veld wordt leeggemaakt, de rest blijft staan.
            ->assertSessionHasInput('fullName', 'Sanne de Vries')
            ->assertSessionMissing('_old_input.username');

        $this->post(route('register'), array_merge($this->registration(), ['email' => 'Bestaand@Example.com']))
            ->assertSessionHas('auth_message', 'Dit e-mailadres of deze gebruikersnaam is al in gebruik.');

        $this->assertGuest();
    }

    /**
     * @return array<string, string>
     */
    private function registration(): array
    {
        return [
            'email' => 'sanne@example.com',
            'username' => 'sanne_01',
            'fullName' => 'Sanne de Vries',
            'password' => 'geheim123',
            'confirmPassword' => 'geheim123',
            'day' => '14',
            'month' => '3',
            'year' => '2005',
        ];
    }
}
