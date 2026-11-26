<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_and_account_pages_render(): void
    {
        $user = User::factory()->create(['full_name' => 'Sanne de Vries']);

        $this->actingAs($user)->get(route('settings'))->assertOk()->assertSee('Instellingen');
        $this->get(route('account'))->assertOk()->assertSee('Sanne de Vries')->assertSee('>SD<', false);
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.profile'), ['name' => 'Nieuwe Naam', 'username' => 'nieuwe_naam', 'email' => 'nieuw@example.com'])
            ->assertRedirect(route('account'))
            ->assertSessionHas('status_message', 'Je profiel is bijgewerkt.');

        $this->assertSame('Nieuwe Naam', $user->fresh()->full_name);
        $this->assertSame('nieuwe_naam', $user->fresh()->username);
        $this->assertSame('nieuw@example.com', $user->fresh()->email);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'bezet@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.profile'), ['name' => 'X', 'username' => $user->username, 'email' => 'bezet@example.com'])
            ->assertSessionHas('status_message', 'Dit e-mailadres is al in gebruik.');
    }

    public function test_account_page_shows_username_field(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);

        $this->actingAs($user)->get(route('account'))
            ->assertSee('Gebruikersnaam')
            ->assertSee('name="username"', false)
            ->assertSee('value="sanne"', false);
    }

    public function test_username_can_keep_own_name_in_other_case(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);

        $this->actingAs($user)
            ->put(route('account.profile'), ['name' => 'Sanne', 'username' => 'Sanne', 'email' => $user->email])
            ->assertSessionHas('status_message', 'Je profiel is bijgewerkt.');

        $this->assertSame('Sanne', $user->fresh()->username);
    }

    public function test_username_must_be_unique_ignoring_case(): void
    {
        User::factory()->create(['username' => 'bram']);
        $user = User::factory()->create(['username' => 'sanne']);

        $this->actingAs($user)
            ->put(route('account.profile'), ['name' => 'X', 'username' => 'BRAM', 'email' => $user->email])
            ->assertSessionHas('status_message', 'Deze gebruikersnaam is al in gebruik.');

        $this->assertSame('sanne', $user->fresh()->username);
    }

    public function test_invalid_username_is_rejected(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);

        foreach (['ab', 'met spatie', str_repeat('a', 31), ''] as $username) {
            $this->actingAs($user)
                ->put(route('account.profile'), ['name' => 'X', 'username' => $username, 'email' => $user->email])
                ->assertSessionHas('status_message', 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).');
        }

        $this->assertSame('sanne', $user->fresh()->username);
    }

    public function test_user_can_log_in_with_new_username(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);

        $this->actingAs($user)
            ->put(route('account.profile'), ['name' => 'X', 'username' => 'sanne2', 'email' => $user->email]);

        $this->post(route('logout'));

        $this->post(route('login.store'), ['email' => 'sanne2', 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'fout',
                'new_password' => 'nieuwwachtwoord',
                'confirm_password' => 'nieuwwachtwoord',
            ])
            ->assertSessionHas('status_message', 'Je huidige wachtwoord is onjuist.')
            ->assertSessionHas('password_form_open', true);

        $this->put(route('account.password'), [
            'current_password' => 'password',
            'new_password' => 'nieuwwachtwoord',
            'confirm_password' => 'nieuwwachtwoord',
        ])->assertSessionHas('status_message', 'Je wachtwoord is gewijzigd.');

        $this->assertTrue(Hash::check('nieuwwachtwoord', $user->fresh()->password));
    }

    public function test_account_deletion_requires_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('account.destroy'))
            ->assertSessionHas('status_message', 'Bevestig dat je je account permanent wilt verwijderen.');
        $this->assertModelExists($user);

        $this->delete(route('account.destroy'), ['confirm_delete' => '1'])
            ->assertRedirect(route('login'));
        $this->assertModelMissing($user);
        $this->assertGuest();
    }

    public function test_about_me_can_be_saved_and_is_shown_on_profile(): void
    {
        $user = User::factory()->create(['full_name' => 'Sanne']);

        $this->actingAs($user)->get(route('account'))
            ->assertSeeInOrder(['Over mij', 'Vertel iets over jezelf', 'Over mij opslaan', 'Profiel']);

        $this->put(route('account.about'), ['about_me' => "  Ik fiets graag.\nEn ik wandel.  "])
            ->assertRedirect(route('account'))
            ->assertSessionHas('status_message', 'Je stukje over jezelf is opgeslagen.');

        $this->assertSame("Ik fiets graag.\nEn ik wandel.", $user->fresh()->about_me);

        $this->get(route('account'))->assertSee("Ik fiets graag.\nEn ik wandel.</textarea>", false);

        $this->actingAs(User::factory()->create())
            ->get(route('profile.show', $user))
            ->assertSeeInOrder(['Over mij', 'Ik fiets graag.']);
    }

    public function test_about_me_can_be_emptied(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['about_me' => 'Oude tekst'])->save();

        $this->actingAs($user)->put(route('account.about'), ['about_me' => '']);

        $this->assertSame('', $user->fresh()->about_me);
    }

    public function test_about_me_longer_than_500_characters_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.about'), ['about_me' => str_repeat('é', 501)])
            ->assertSessionHas('status_message', 'Je stukje over jezelf mag maximaal 500 tekens bevatten.')
            ->assertSessionHasInput('about_me');

        $this->assertSame('', $user->fresh()->about_me);

        // Precies 500 tekens (ook met speciale tekens) mag wel.
        $this->put(route('account.about'), ['about_me' => str_repeat('é', 500)])
            ->assertSessionHas('status_message', 'Je stukje over jezelf is opgeslagen.');
    }
}
