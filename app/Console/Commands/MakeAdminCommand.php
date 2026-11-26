<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Maakt een bestaande gebruiker beheerder van het admin-dashboard (of haalt dat weg).
 */
class MakeAdminCommand extends Command
{
    /** @var string */
    protected $signature = 'upmove:admin {login : E-mailadres of gebruikersnaam} {--remove : Beheerdersrechten weghalen}';

    /** @var string */
    protected $description = 'Maak een gebruiker beheerder van het admin-dashboard';

    public function handle(): int
    {
        $login = mb_strtolower(trim((string) $this->argument('login')));

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$login])
            ->orWhereRaw('LOWER(username) = ?', [$login])
            ->first();

        if ($user === null) {
            $this->error("Geen gebruiker gevonden met e-mailadres of gebruikersnaam \"{$login}\".");

            return self::FAILURE;
        }

        $makeAdmin = ! $this->option('remove');
        $user->forceFill(['is_admin' => $makeAdmin])->save();

        $this->info($makeAdmin
            ? "{$user->username} is nu beheerder. Open het dashboard via /admin."
            : "{$user->username} is geen beheerder meer.");

        return self::SUCCESS;
    }
}
