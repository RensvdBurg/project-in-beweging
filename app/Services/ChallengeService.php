<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Challenge;
use App\Models\ChallengeCompletion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bevat alle regels rond de "challenge van het moment":
 *
 * - de tijd is verdeeld in vaste blokken van 90 minuten;
 * - per blok krijgt de gebruiker een willekeurige challenge (niet dezelfde als de vorige);
 * - de gebruiker mag per dag maximaal twee keer direct wisselen;
 * - een challenge levert per blok maar één keer XP op.
 *
 * De gekozen challenge en het aantal wissels staan per gebruiker in de database
 * (users.current_challenge_*, users.challenge_changes_*). Vroeger stonden ze in de
 * sessie, waardoor uitloggen en weer inloggen een nieuwe challenge en weer 2 wissels gaf.
 */
class ChallengeService
{
    public const INTERVAL_SECONDS = 90 * 60;

    public const DAILY_CHANGE_LIMIT = 2;

    public function currentInterval(): int
    {
        return intdiv(now()->getTimestamp(), self::INTERVAL_SECONDS);
    }

    /**
     * Unix-tijdstempel waarop het volgende blok begint.
     */
    public function nextChangeAt(): int
    {
        return ($this->currentInterval() + 1) * self::INTERVAL_SECONDS;
    }

    public function changesRemaining(User $user): int
    {
        return self::DAILY_CHANGE_LIMIT - min(self::DAILY_CHANGE_LIMIT, $this->changesUsedToday($user));
    }

    /**
     * Bepaalt (en onthoudt) de challenge voor het huidige blok.
     *
     * @return array{challenge: ?Challenge, completed: bool, noDifferentAvailable: bool}
     */
    public function current(User $user): array
    {
        $interval = $this->currentInterval();
        $challenges = Challenge::query()->orderBy('id')->get();
        $selected = null;
        $noDifferentAvailable = false;

        if ($challenges->isNotEmpty()) {
            if ((int) $user->current_challenge_interval === $interval) {
                $selected = $challenges->firstWhere('id', (int) $user->current_challenge_id);
            }

            if ($selected === null) {
                $previousId = (int) $user->current_challenge_id;
                $options = $challenges->reject(fn (Challenge $challenge): bool => $challenge->id === $previousId);

                if ($options->isEmpty()) {
                    $noDifferentAvailable = true;
                } else {
                    $selected = $options->random();
                    $this->remember($user, $selected->id, $interval);
                }
            }
        }

        $completed = $selected !== null && ChallengeCompletion::query()
            ->where('user_id', $user->id)
            ->where('challenge_id', $selected->id)
            ->where('challenge_interval', $interval)
            ->exists();

        return [
            'challenge' => $selected,
            'completed' => $completed,
            'noDifferentAvailable' => $noDifferentAvailable,
        ];
    }

    /**
     * Laadt direct een andere challenge en geeft de melding voor de gebruiker terug.
     */
    public function next(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            // Opnieuw ophalen binnen de transactie: twee snelle klikken tellen zo allebei mee.
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $used = $this->changesUsedToday($user);

            if ($used >= self::DAILY_CHANGE_LIMIT) {
                return 'Je hebt je '.self::DAILY_CHANGE_LIMIT.' directe opdrachtwissels voor vandaag al gebruikt.';
            }

            $challengeIds = Challenge::query()->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if ($challengeIds === []) {
                return 'Er zijn momenteel geen opdrachten beschikbaar.';
            }

            $previousId = (int) $user->current_challenge_id;

            if (count($challengeIds) === 1 && $challengeIds[0] === $previousId) {
                return 'Er is maar één opdracht beschikbaar, dus er is geen andere opdracht om te laden.';
            }

            $options = $challengeIds;
            if (count($options) > 1 && $previousId !== 0) {
                $options = array_values(array_filter($options, fn (int $id): bool => $id !== $previousId));
            }

            $used++;

            $user->forceFill([
                'current_challenge_id' => $options[random_int(0, count($options) - 1)],
                'current_challenge_interval' => $this->currentInterval(),
                'challenge_changes_date' => now()->toDateString(),
                'challenge_changes_count' => $used,
            ])->save();

            $remaining = self::DAILY_CHANGE_LIMIT - $used;

            return $remaining === 0
                ? 'Nieuwe opdracht geladen. Je hebt vandaag geen directe opdrachtwissels meer.'
                : "Nieuwe opdracht geladen. Je kunt dit vandaag nog {$remaining} keer doen.";
        });
    }

    /**
     * Markeert de huidige challenge als voltooid, kent XP toe en geeft de melding terug.
     */
    public function complete(User $user): string
    {
        $user->refresh();
        $interval = $this->currentInterval();

        if ((int) $user->current_challenge_interval !== $interval || (int) $user->current_challenge_id < 1) {
            return 'Deze opdracht is niet meer actief. Ververs de pagina en probeer het opnieuw.';
        }

        $challenge = Challenge::query()->find((int) $user->current_challenge_id);

        if ($challenge === null || $challenge->xp_reward < 1) {
            return 'Deze opdracht is niet beschikbaar voor voltooiing.';
        }

        try {
            DB::transaction(function () use ($user, $challenge, $interval): void {
                ChallengeCompletion::query()->create([
                    'user_id' => $user->id,
                    'challenge_id' => $challenge->id,
                    'challenge_interval' => $interval,
                ]);

                $user->increment('xp', $challenge->xp_reward);
            });
        } catch (UniqueConstraintViolationException) {
            return 'Je hebt deze opdracht al voltooid.';
        } catch (QueryException $exception) {
            Log::error('Opdracht voltooien/databasefout: '.$exception->getMessage());

            return 'De opdracht kon niet worden voltooid. Probeer het later opnieuw.';
        }

        AuditLog::record('challenge_completed', "{$user->username} heeft \"{$challenge->title}\" voltooid (+{$challenge->xp_reward} XP).", $user);

        return "Opdracht voltooid! Je hebt {$challenge->xp_reward} XP verdiend.";
    }

    /**
     * Verwijdert een challenge (admin-dashboard). Voltooiingen verdwijnen via cascade;
     * de verdiende XP blijft staan. Wie deze challenge nu open heeft, krijgt bij het
     * volgende bezoek een nieuwe.
     */
    public function delete(Challenge $challenge): void
    {
        DB::transaction(function () use ($challenge): void {
            User::query()->where('current_challenge_id', $challenge->id)->update(['current_challenge_id' => null]);
            $challenge->delete();
        });
    }

    /**
     * Aantal directe wissels vandaag; een nieuwe dag begint weer bij 0.
     */
    private function changesUsedToday(User $user): int
    {
        $date = $user->challenge_changes_date;

        if ($date === null || substr((string) $date, 0, 10) !== now()->toDateString()) {
            return 0;
        }

        return max(0, (int) $user->challenge_changes_count);
    }

    private function remember(User $user, int $challengeId, int $interval): void
    {
        $user->forceFill([
            'current_challenge_id' => $challengeId,
            'current_challenge_interval' => $interval,
        ])->save();
    }
}
