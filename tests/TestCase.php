<?php

namespace Tests;

use App\Auth\ApiSessionGuard;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Api\ApiToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

abstract class TestCase extends BaseTestCase
{
    /**
     * Inloggen in de app gaat via de API: naast de gebruiker krijgt de sessie ook een
     * echt API-token, zodat de schermen in de test via de API (in hetzelfde proces)
     * met de testdatabase praten.
     *
     * @param  string|null  $guard
     */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if ($user instanceof User && $user->exists && ($guard === null || $guard === 'web')) {
            $token = $user->createToken('test')->plainTextToken;
            $userData = (new UserResource($user->fresh()))->withPrivateFields()->toArray(new Request);

            $this->withSession([ApiToken::SESSION_KEY => $token, ApiSessionGuard::USER_KEY => $userData]);
        }

        return parent::actingAs($user, $guard);
    }
}
