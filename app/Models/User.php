<?php

namespace App\Models;

use App\Support\UserSettings;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property array<string, mixed>|null $settings Opgeslagen instellingen (JSON, via de 'array'-cast).
 */
#[Fillable(['email', 'username', 'password', 'full_name', 'birth_date'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'xp' => 'integer',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /**
     * Instellingen van deze gebruiker, aangevuld met de standaardwaarden.
     *
     * @return array{frequency: int, dailyGoal: int, darkMode: bool, workOnly: bool, workStart: string, workEnd: string, workDays: list<int>, workSchedule: list<array{start: string, end: string}>}
     */
    public function preferences(): array
    {
        return UserSettings::normalize($this->settings);
    }

    /**
     * @return HasMany<Activity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * @return HasMany<ChallengeCompletion, $this>
     */
    public function challengeCompletions(): HasMany
    {
        return $this->hasMany(ChallengeCompletion::class);
    }

    /**
     * Ranglijstvolgorde: meeste XP eerst; bij gelijke XP op gebruikersnaam en daarna ID.
     *
     * LOWER() omdat SQLite standaard hoofdlettergevoelig sorteert, terwijl de oude
     * MySQL-database (utf8mb4_unicode_ci) dat niet deed. Gebruikersnamen zijn ASCII.
     *
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function leaderboard(Builder $query): void
    {
        $query->orderByDesc('xp')
            ->orderByRaw('LOWER(username) ASC')
            ->orderBy('id');
    }

    /**
     * Positie op de ranglijst (1 = meeste XP), met dezelfde volgorde als leaderboard().
     */
    public function leaderboardRank(): int
    {
        $username = mb_strtolower($this->username);

        return static::query()
            ->where(function (Builder $query) use ($username): void {
                $query->where('xp', '>', $this->xp)
                    ->orWhere(function (Builder $query) use ($username): void {
                        $query->where('xp', $this->xp)
                            ->where(function (Builder $query) use ($username): void {
                                $query->whereRaw('LOWER(username) < ?', [$username])
                                    ->orWhere(function (Builder $query) use ($username): void {
                                        $query->whereRaw('LOWER(username) = ?', [$username])
                                            ->where('id', '<', $this->id);
                                    });
                            });
                    });
            })
            ->count() + 1;
    }

    /**
     * Maximaal twee initialen voor de avatar (er is geen profielfoto).
     */
    public function initials(): string
    {
        $initials = '';

        foreach (preg_split('/\s+/', trim($this->full_name)) as $part) {
            if ($part !== '') {
                $initials .= mb_substr($part, 0, 1);
            }

            if (mb_strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? mb_strtoupper($initials) : '?';
    }
}
