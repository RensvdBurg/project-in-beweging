<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Gebruiker aanmaken of bewerken in het admin-dashboard.
 * Dezelfde regels als bij registreren; het wachtwoord is bij bewerken optioneel.
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $editing = $this->route('user') instanceof User;

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'username' => ['bail', 'required', 'string', 'regex:/\A[A-Za-z0-9_.-]{3,30}\z/', $this->uniqueIgnoringCase('username')],
            'email' => ['bail', 'required', 'string', 'email', 'max:254', $this->uniqueIgnoringCase('email')],
            'password' => [$editing ? 'nullable' : 'required', function (string $attribute, mixed $value, Closure $fail): void {
                // Bytes tellen, net als bij registreren: bcrypt kapt af na 72 bytes.
                if (! is_string($value) || strlen($value) < 8 || strlen($value) > 72) {
                    $fail('Kies een wachtwoord van minimaal 8 en maximaal 72 letters.');
                }
            }],
            'birth_date' => ['required', 'date', 'before_or_equal:today', 'after:'.now()->subYears(110)->toDateString()],
            'xp' => ['required', 'integer', 'min:0', 'max:10000000'],
            'about_me' => ['nullable', 'string', 'max:500'],
            'is_admin' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.*' => 'Vul een naam van maximaal 150 tekens in.',
            'username.required' => 'Vul een gebruikersnaam in van 3 tot 30 tekens (letters, cijfers, punt, streepje of underscore).',
            'username.regex' => 'Vul een gebruikersnaam in van 3 tot 30 tekens (letters, cijfers, punt, streepje of underscore).',
            'email.required' => 'Vul een geldig e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.max' => 'Vul een geldig e-mailadres in.',
            'password.required' => 'Kies een wachtwoord van minimaal 8 en maximaal 72 letters.',
            'birth_date.*' => 'Kies een geldige geboortedatum.',
            'xp.*' => 'Vul een geldig aantal XP in (0 of meer).',
            'about_me.max' => '"Over mij" mag maximaal 500 tekens bevatten.',
        ];
    }

    /**
     * Uniek zonder op hoofdletters te letten (SQLite vergelijkt hoofdlettergevoelig);
     * bij bewerken telt de gebruiker zelf niet mee.
     */
    private function uniqueIgnoringCase(string $column): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($column): void {
            $current = $this->route('user');

            $taken = User::query()
                ->when($current instanceof User, fn ($query) => $query->whereKeyNot($current->id))
                ->whereRaw("LOWER({$column}) = ?", [mb_strtolower(trim((string) $value))])
                ->exists();

            if ($taken) {
                $fail($column === 'email' ? 'Dit e-mailadres is al in gebruik.' : 'Deze gebruikersnaam is al in gebruik.');
            }
        };
    }
}
