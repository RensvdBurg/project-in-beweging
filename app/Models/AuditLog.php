<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén regel in het logboek van het admin-dashboard.
 *
 * @property string $action
 * @property string $description
 */
#[Fillable(['user_id', 'action', 'description', 'ip_address'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Soorten logregels met hun Nederlandse naam (filter op de logpagina).
     *
     * @var array<string, string>
     */
    public const ACTIONS = [
        'login' => 'Ingelogd',
        'login_failed' => 'Mislukte login',
        'logout' => 'Uitgelogd',
        'register' => 'Geregistreerd',
        'account_deleted' => 'Account verwijderd',
        'challenge_completed' => 'Challenge voltooid',
        'activity_created' => 'Activiteit geplaatst',
        'admin_user_created' => 'Admin: gebruiker aangemaakt',
        'admin_user_updated' => 'Admin: gebruiker bewerkt',
        'admin_user_deleted' => 'Admin: gebruiker verwijderd',
        'admin_challenge_created' => 'Admin: challenge toegevoegd',
        'admin_challenge_updated' => 'Admin: challenge bewerkt',
        'admin_challenge_deleted' => 'Admin: challenge verwijderd',
        'admin_activity_deleted' => 'Admin: activiteit verwijderd',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /**
     * Schrijft een logregel. Het IP-adres komt uit het huidige request (leeg in de console).
     */
    public static function record(string $action, string $description, ?User $user = null): self
    {
        return self::query()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => mb_substr($description, 0, 500),
            'ip_address' => app()->runningInConsole() && ! app()->runningUnitTests() ? null : request()->ip(),
        ]);
    }
}
