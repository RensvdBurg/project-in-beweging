<?php

namespace App\Services;

use App\Models\User;
use Ikromjon\LocalNotifications\Contracts\LocalNotificationsInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Zet de meldingen uit NotificationPlanner op de telefoon (plugin
 * ikromjon/nativephp-mobile-local-notifications). De telefoon toont ze ook
 * als de app dicht is.
 *
 * - Bij elke synchronisatie: alle oude UpMove-meldingen weg, dan het nieuwe plan.
 * - Overslaan als er niets veranderd is en er nog minstens een dag gepland staat
 *   (zodat home openen niet telkens tientallen native aanroepen kost).
 * - Alleen in de NativePHP-app; in de browser doet deze class niets.
 */
class PhoneNotifications
{
    private const CACHE_KEY = 'upmove:phone-notifications';

    /** Opnieuw plannen als het plan binnen zoveel seconden opraakt. */
    private const REFILL_BEFORE_SECONDS = 24 * 60 * 60;

    public function __construct(
        private readonly NotificationPlanner $planner,
        private readonly LocalNotificationsInterface $notifications,
    ) {}

    /**
     * Draait de app op een telefoon (of staat het in de config aan)?
     */
    public function isAvailable(): bool
    {
        $configured = config('upmove.phone_notifications');

        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOL);
        }

        // Op het toestel is nativephp_call() een C-extensie; op de computer een PHP-polyfill.
        return function_exists('nativephp_call') && (new \ReflectionFunction('nativephp_call'))->isInternal();
    }

    /**
     * Meldingen afstemmen op de instellingen van deze gebruiker.
     *
     * @param  bool  $force  ook plannen als er niets veranderd lijkt (na opslaan van instellingen)
     */
    public function sync(User $user, bool $force = false): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        $preferences = $user->preferences();
        $fingerprint = sha1(json_encode([
            $user->id,
            $preferences['workOnly'],
            $preferences['frequency'],
            $preferences['workDays'],
            $preferences['workSchedule'],
        ]));

        $state = Cache::get(self::CACHE_KEY);

        if (
            ! $force
            && is_array($state)
            && ($state['fingerprint'] ?? null) === $fingerprint
            && ($state['planned_until'] ?? 0) - now()->getTimestamp() > self::REFILL_BEFORE_SECONDS
        ) {
            return;
        }

        try {
            $this->notifications->cancelAll();

            $plan = $this->planner->plan($preferences, now());

            if ($plan !== [] && $this->ensurePermission()) {
                foreach ($plan as $item) {
                    $this->notifications->schedule([
                        'id' => $item['id'],
                        'title' => $item['title'],
                        'body' => $item['body'],
                        'at' => $item['at'],
                        'data' => ['upmove_kind' => $item['kind']],
                    ]);
                }
            }

            Cache::forever(self::CACHE_KEY, [
                'fingerprint' => $fingerprint,
                // Leeg plan (werk/school uit): pas weer plannen als er iets verandert.
                'planned_until' => $plan === [] ? PHP_INT_MAX : end($plan)['at'],
            ]);
        } catch (Throwable $exception) {
            // Een melding die niet lukt mag de app nooit laten vastlopen.
            Log::warning('Meldingen plannen mislukt: '.$exception->getMessage());
        }
    }

    /**
     * Alle UpMove-meldingen van dit toestel weg (bij uitloggen of account verwijderen),
     * zodat een volgende gebruiker op dezelfde telefoon ze niet krijgt.
     */
    public function clear(): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        try {
            $this->notifications->cancelAll();
        } catch (Throwable $exception) {
            Log::warning('Meldingen wissen mislukt: '.$exception->getMessage());
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Status voor de instellingenpagina: 'granted', 'denied', of null buiten de app / onbekend.
     */
    public function permissionStatus(): ?string
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $status = $this->notifications->checkPermission()['status'] ?? null;
        } catch (Throwable) {
            return null;
        }

        return is_string($status) ? $status : null;
    }

    /**
     * Toestemming vragen als die er nog niet is (de telefoon toont dan zelf de vraag).
     */
    private function ensurePermission(): bool
    {
        if (($this->notifications->checkPermission()['status'] ?? null) === 'granted') {
            return true;
        }

        $result = $this->notifications->requestPermission();

        // Op iOS komt het antwoord soms later ('pending'): dan toch plannen, de telefoon
        // toont ze zodra er toestemming is.
        return ($result['granted'] ?? false) === true || ($result['status'] ?? null) === 'pending';
    }
}
