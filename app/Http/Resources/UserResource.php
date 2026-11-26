<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Een gebruiker in de API. Privégegevens (e-mail, geboortedatum, instellingen,
 * challenge-status) alleen voor de gebruiker zelf.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    private bool $includePrivate = false;

    /**
     * Ook de privégegevens meesturen, bijvoorbeeld direct na inloggen
     * (dan is er nog geen ingelogde gebruiker op het verzoek).
     */
    public function withPrivateFields(): static
    {
        $this->includePrivate = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSelf = $this->includePrivate || $request->user()?->id === $this->id;

        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'initials' => $this->initials(),
            'about_me' => (string) $this->about_me,
            'xp' => (int) $this->xp,
            'created_at' => $this->created_at?->toIso8601String(),
        ];

        if ($isSelf) {
            $data['email'] = $this->email;
            $data['birth_date'] = $this->birth_date?->toDateString();
            $data['is_admin'] = (bool) $this->is_admin;
            // null = nog nooit opgeslagen (de app gebruikt dan zijn eigen standaardwaarden).
            $data['settings'] = $this->settings !== null ? $this->preferences() : null;
        }

        return $data;
    }
}
