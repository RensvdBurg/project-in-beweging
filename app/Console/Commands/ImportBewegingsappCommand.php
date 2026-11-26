<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Support\UserSettings;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Kopieert gebruikers, voltooiingen, posts, reacties, likes en instellingen uit de
 * oude MySQL-database `bewegingsapp` naar de database van de app (`upmove`).
 *
 * De challenges zelf staan al in een migratie. Bestaande gebruikers (zelfde id,
 * e-mail of gebruikersnaam) worden overgeslagen en bestaande instellingen niet
 * overschreven, dus het commando kan veilig opnieuw draaien.
 */
class ImportBewegingsappCommand extends Command
{
    protected $signature = 'upmove:import-mysql
        {--images= : Map met de afbeeldingen van activity_posts}';

    protected $description = 'Importeer de data uit de oude MySQL-database bewegingsapp in de app-database';

    private const DEFAULT_IMAGE_DIR = 'C:/xampp/htdocs/project-in-beweging/uploads/activiteiten';

    public function handle(): int
    {
        $source = DB::connection('bewegingsapp');
        $imageDir = rtrim($this->option('images') ?: self::DEFAULT_IMAGE_DIR, '/\\');
        $counts = array_fill_keys(['gebruikers', 'voltooiingen', 'activiteiten', 'afbeeldingen', 'reacties', 'likes', 'instellingen'], 0);
        $skipped = [];

        DB::transaction(function () use ($source, $imageDir, &$counts, &$skipped): void {
            $userIds = $this->importUsers($source, $counts, $skipped);
            $this->importCompletions($source, $userIds, $counts, $skipped);
            $activityIds = $this->importActivities($source, $userIds, $imageDir, $counts, $skipped);
            $this->importCommentsAndLikes($source, $userIds, $activityIds, $counts);
            $this->importSettings($source, $counts);
        });

        $this->table(
            ['Onderdeel', 'Geïmporteerd'],
            collect($counts)->map(fn (int $n, string $k): array => [$k, $n])->values()->all(),
        );

        foreach ($skipped as $message) {
            $this->warn($message);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<string>  $skipped
     * @return array<int, true> geïmporteerde gebruikers-id's
     */
    private function importUsers(ConnectionInterface $source, array &$counts, array &$skipped): array
    {
        $imported = [];

        foreach ($source->table('users')->orderBy('id')->get() as $user) {
            $exists = DB::table('users')
                ->where('id', $user->id)
                ->orWhere('email', $user->email)
                ->orWhere('username', $user->username)
                ->exists();

            if ($exists) {
                $skipped[] = "Gebruiker {$user->username} (id {$user->id}) overgeslagen: id, e-mail of gebruikersnaam bestaat al.";

                continue;
            }

            // De bcrypt-hash ($2y$) werkt direct met Laravel; niet opnieuw hashen.
            DB::table('users')->insert([
                'id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'password' => $user->password_hash,
                'full_name' => $user->full_name,
                'about_me' => $user->about_me,
                'birth_date' => $user->birth_date,
                'xp' => max(0, (int) $user->xp),
                'created_at' => $user->created_at,
                'updated_at' => $user->created_at,
            ]);

            $imported[(int) $user->id] = true;
            $counts['gebruikers']++;
        }

        return $imported;
    }

    /**
     * @param  array<int, true>  $userIds
     * @param  array<string, int>  $counts
     * @param  list<string>  $skipped
     */
    private function importCompletions(ConnectionInterface $source, array $userIds, array &$counts, array &$skipped): void
    {
        // Alleen challenges die in de oude database nog bestaan (52–101); lagere id's
        // zijn daar verwijderd en zouden hier naar andere opdrachten wijzen.
        $challengeIds = array_flip($source->table('challenges')->pluck('id')->map(fn ($id): int => (int) $id)->all());
        $missing = 0;

        foreach ($source->table('challenge_completions')->get() as $completion) {
            if (! isset($userIds[(int) $completion->user_id])) {
                continue;
            }

            if (! isset($challengeIds[(int) $completion->challenge_id])) {
                $missing++;

                continue;
            }

            $counts['voltooiingen'] += DB::table('challenge_completions')->insertOrIgnore([
                'user_id' => $completion->user_id,
                'challenge_id' => $completion->challenge_id,
                'challenge_interval' => $completion->challenge_interval,
                'created_at' => $completion->completed_at,
                'updated_at' => $completion->completed_at,
            ]);
        }

        if ($missing > 0) {
            $skipped[] = "{$missing} voltooiingen overgeslagen: die challenge bestaat niet meer in bewegingsapp (de XP-totalen blijven behouden).";
        }
    }

    /**
     * @param  array<int, true>  $userIds
     * @param  array<string, int>  $counts
     * @param  list<string>  $skipped
     * @return array<int, int> oud post-id => nieuw activity-id
     */
    private function importActivities(ConnectionInterface $source, array $userIds, string $imageDir, array &$counts, array &$skipped): array
    {
        $map = [];
        $disk = Storage::disk(Activity::IMAGE_DISK);

        foreach ($source->table('activity_posts')->orderBy('id')->get() as $post) {
            if (! isset($userIds[(int) $post->user_id])) {
                continue;
            }

            $imagePath = null;
            $filename = basename((string) $post->image_filename);
            $file = $imageDir.DIRECTORY_SEPARATOR.$filename;

            if ($filename !== '' && is_file($file)) {
                $imagePath = "activities/{$post->user_id}/{$filename}";
                $disk->put($imagePath, file_get_contents($file));
                $counts['afbeeldingen']++;
            } elseif ($filename !== '') {
                $skipped[] = "Afbeelding {$filename} niet gevonden in {$imageDir}.";
            }

            $map[(int) $post->id] = DB::table('activities')->insertGetId([
                'user_id' => $post->user_id,
                'title' => $post->title,
                'description' => $post->description,
                'image_path' => $imagePath,
                'created_at' => $post->created_at,
                'updated_at' => $post->created_at,
            ]);
            $counts['activiteiten']++;
        }

        return $map;
    }

    /**
     * Zet `user_settings.settings_json` over naar users.settings, gekoppeld op
     * e-mailadres. Gebruikers die al instellingen hebben, worden niet overschreven.
     *
     * @param  array<string, int>  $counts
     */
    private function importSettings(ConnectionInterface $source, array &$counts): void
    {
        $rows = $source->table('user_settings')
            ->join('users', 'users.id', '=', 'user_settings.user_id')
            ->get(['users.email', 'user_settings.settings_json']);

        foreach ($rows as $row) {
            $saved = json_decode((string) $row->settings_json, true);

            if (! is_array($saved)) {
                continue;
            }

            $counts['instellingen'] += DB::table('users')
                ->where('email', $row->email)
                ->whereNull('settings')
                ->update(['settings' => json_encode(UserSettings::normalize($saved))]);
        }
    }

    /**
     * @param  array<int, true>  $userIds
     * @param  array<int, int>  $activityIds
     * @param  array<string, int>  $counts
     */
    private function importCommentsAndLikes(ConnectionInterface $source, array $userIds, array $activityIds, array &$counts): void
    {
        foreach ($source->table('activity_post_comments')->orderBy('id')->get() as $comment) {
            if (isset($activityIds[(int) $comment->post_id], $userIds[(int) $comment->user_id])) {
                DB::table('activity_comments')->insert([
                    'activity_id' => $activityIds[(int) $comment->post_id],
                    'user_id' => $comment->user_id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->created_at,
                ]);
                $counts['reacties']++;
            }
        }

        foreach ($source->table('activity_post_likes')->get() as $like) {
            if (isset($activityIds[(int) $like->post_id], $userIds[(int) $like->user_id])) {
                $counts['likes'] += DB::table('activity_likes')->insertOrIgnore([
                    'activity_id' => $activityIds[(int) $like->post_id],
                    'user_id' => $like->user_id,
                    'created_at' => $like->created_at,
                    'updated_at' => $like->created_at,
                ]);
            }
        }
    }
}
