<?php

namespace App\Http\Requests\Admin;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Challenge toevoegen of bewerken in het admin-dashboard.
 */
class ChallengeRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description_extra' => ['nullable', 'string', 'max:5000'],
            'img' => ['bail', 'required', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail): void {
                $value = trim((string) $value);

                // Een https-URL, of een bestand in public/ (bijv. images/challenges/foto.jpg).
                if (Str::startsWith($value, ['http://', 'https://'])) {
                    if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                        $fail('Vul een geldige afbeeldings-URL in.');
                    }

                    return;
                }

                $path = ltrim($value, '/');

                if (str_contains($path, '..') || ! is_file(public_path($path))) {
                    $fail('Deze afbeelding bestaat niet in public/. Gebruik bijvoorbeeld images/challenges/foto.jpg of een https-URL.');
                }
            }],
            'kcal_burned' => ['required', 'integer', 'min:0', 'max:10000'],
            'difficulty' => ['required', 'string', 'max:50'],
            'xp_reward' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.*' => 'Vul een titel in van maximaal 255 tekens.',
            'description_extra.max' => 'De beschrijving mag maximaal 5000 tekens bevatten.',
            'img.required' => 'Vul een afbeelding in (pad in public/ of een https-URL).',
            'kcal_burned.*' => 'Vul een geldig aantal kcal in (0 tot 10.000).',
            'difficulty.*' => 'Vul een moeilijkheid in, bijvoorbeeld Beginner of Gevorderd.',
            'xp_reward.*' => 'Vul een XP-beloning in van 1 tot 10.000.',
        ];
    }

    /**
     * Gevalideerde gegevens, klaar om op te slaan.
     *
     * @return array{title: string, description_extra: ?string, img: string, kcal_burned: int, difficulty: string, xp_reward: int}
     */
    public function challengeData(): array
    {
        $img = trim($this->string('img'));

        return [
            'title' => trim($this->string('title')),
            'description_extra' => $this->filled('description_extra') ? trim($this->string('description_extra')) : null,
            'img' => Str::startsWith($img, ['http://', 'https://']) ? $img : ltrim($img, '/'),
            'kcal_burned' => $this->integer('kcal_burned'),
            'difficulty' => trim($this->string('difficulty')),
            'xp_reward' => $this->integer('xp_reward'),
        ];
    }
}
