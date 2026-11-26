<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Services\PhoneNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('settings.account', [
            'accountUser' => $request->user(),
            'statusMessage' => session('status_message', ''),
            'statusType' => session('status_type', 'success'),
            'passwordFormOpen' => (bool) session('password_form_open', false),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            // Zelfde regels als bij registreren. Uniek zonder op hoofdletters te letten
            // (SQLite vergelijkt standaard hoofdlettergevoelig), zodat "Bram" en "bram"
            // niet allebei kunnen bestaan.
            'username' => ['bail', 'required', 'string', 'regex:/\A[A-Za-z0-9_.-]{3,30}\z/', function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                $taken = User::query()
                    ->whereKeyNot($user->id)
                    ->whereRaw('LOWER(username) = ?', [mb_strtolower(trim((string) $value))])
                    ->exists();

                if ($taken) {
                    $fail('Deze gebruikersnaam is al in gebruik.');
                }
            }],
            'email' => ['bail', 'required', 'string', 'email', 'max:254', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.*' => 'Vul een naam van maximaal 150 tekens in.',
            'username.required' => 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).',
            'username.string' => 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).',
            'username.regex' => 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'email.*' => 'Vul een geldig e-mailadres in.',
        ]);

        if ($validator->fails()) {
            return $this->back($validator->errors()->first(), 'error')->withInput();
        }

        $user->update([
            'full_name' => trim($request->string('name')),
            'username' => trim($request->string('username')),
            'email' => trim($request->string('email')),
        ]);

        return $this->back('Je profiel is bijgewerkt.');
    }

    /**
     * "Over mij": zichtbaar op het profiel (via de ranglijst of een activiteit). Mag leeg zijn.
     */
    public function updateAboutMe(Request $request): RedirectResponse
    {
        $aboutMe = trim((string) $request->input('about_me', ''));

        if (mb_strlen($aboutMe) > 500) {
            return $this->back('Je stukje over jezelf mag maximaal 500 tekens bevatten.', 'error')
                ->withInput($request->only('about_me'));
        }

        $request->user()->forceFill(['about_me' => $aboutMe])->save();

        return $this->back('Je stukje over jezelf is opgeslagen.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $newPassword = (string) $request->input('new_password', '');

        $message = match (true) {
            ! Hash::check((string) $request->input('current_password', ''), $user->password) => 'Je huidige wachtwoord is onjuist.',
            strlen($newPassword) < 8 || strlen($newPassword) > 72 => 'Je nieuwe wachtwoord moet minimaal 8 en maximaal 72 letters bevatten.',
            $newPassword !== (string) $request->input('confirm_password', '') => 'De nieuwe wachtwoorden komen niet overeen.',
            default => null,
        };

        if ($message !== null) {
            return $this->back($message, 'error')->with('password_form_open', true);
        }

        $user->update(['password' => $newPassword]);

        return $this->back('Je wachtwoord is gewijzigd.')->with('password_form_open', true);
    }

    public function destroy(Request $request, PhoneNotifications $notifications): RedirectResponse
    {
        if ($request->input('confirm_delete') !== '1') {
            return $this->back('Bevestig dat je je account permanent wilt verwijderen.', 'error');
        }

        $user = $request->user();

        $notifications->clear();
        Auth::logout();
        $user->delete();

        // Afbeeldingen van eigen activiteiten opruimen (de rijen verdwijnen via cascade).
        Storage::disk(Activity::IMAGE_DISK)->deleteDirectory("activities/{$user->id}");

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function back(string $message, string $type = 'success'): RedirectResponse
    {
        return redirect()->route('account')->with([
            'status_message' => $message,
            'status_type' => $type,
        ]);
    }
}
