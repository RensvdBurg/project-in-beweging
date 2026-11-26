<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportBewegingsappTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_adds_the_bewegingsapp_challenges(): void
    {
        $challenge = Challenge::query()->findOrFail(53);

        $this->assertSame('Neem een trap in plaats van de lift', $challenge->title);
        $this->assertSame('Gebruik een gewone trap voor één verdieping en loop rustig, zonder te haasten.', $challenge->description_extra);
        $this->assertSame(50, Challenge::query()->whereBetween('id', [52, 101])->count());
    }

    public function test_challenge_images_are_stored_locally_for_offline_use(): void
    {
        foreach (Challenge::query()->pluck('img') as $img) {
            $this->assertStringNotContainsString('://', $img, 'challenge-afbeeldingen mogen geen externe URL zijn');
            $this->assertFileExists(public_path($img));
        }
    }

    public function test_command_imports_users_completions_activities_comments_and_likes(): void
    {
        Storage::fake(Activity::IMAGE_DISK);
        $imageDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'upmove-import-test';
        @mkdir($imageDir);
        file_put_contents($imageDir.DIRECTORY_SEPARATOR.'foto.webp', 'afbeelding');

        $this->fakeSource();
        User::factory()->create(['username' => 'bestaat']);

        $this->artisan('upmove:import-mysql', ['--images' => $imageDir])->assertSuccessful();

        $sanne = User::query()->findOrFail(20);
        $this->assertTrue(Hash::check('geheim123', $sanne->password));
        $this->assertSame('Hoi', $sanne->about_me);
        $this->assertSame(40, $sanne->xp);
        $this->assertFalse(User::query()->whereKey(21)->exists(), 'gebruiker met bestaande gebruikersnaam wordt overgeslagen');

        $this->assertDatabaseHas('challenge_completions', ['user_id' => 20, 'challenge_id' => 53]);
        $this->assertDatabaseMissing('challenge_completions', ['user_id' => 20, 'challenge_id' => 1]);

        $activity = $sanne->activities()->sole();
        $this->assertSame('activities/20/foto.webp', $activity->image_path);
        Storage::disk(Activity::IMAGE_DISK)->assertExists('activities/20/foto.webp');
        $this->assertDatabaseHas('activity_comments', ['activity_id' => $activity->id, 'body' => 'Goed bezig']);
        $this->assertDatabaseHas('activity_likes', ['activity_id' => $activity->id, 'user_id' => 20]);

        $settings = $sanne->fresh()->preferences();
        $this->assertSame(15, $settings['frequency']);
        $this->assertTrue($settings['darkMode']);
        $this->assertSame([1, 2, 3], $settings['workDays']);
        $this->assertSame(12, $settings['dailyGoal'], 'ontbrekende waarden krijgen de standaard');

        // Opnieuw draaien maakt geen dubbele rijen.
        $this->artisan('upmove:import-mysql', ['--images' => $imageDir])->assertSuccessful();
        $this->assertSame(1, $sanne->activities()->count());
    }

    private function fakeSource(): void
    {
        config(['database.connections.bewegingsapp' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('bewegingsapp');
        $schema = Schema::connection('bewegingsapp');

        $schema->create('users', function (Blueprint $t) {
            $t->id();
            $t->string('email');
            $t->string('username');
            $t->string('password_hash');
            $t->string('full_name');
            $t->string('about_me');
            $t->date('birth_date');
            $t->timestamp('created_at');
            $t->integer('xp');
        });
        $schema->create('challenges', fn (Blueprint $t) => $t->id());
        $schema->create('challenge_completions', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('challenge_id');
            $t->unsignedBigInteger('challenge_interval');
            $t->timestamp('completed_at');
        });
        $schema->create('activity_posts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('title');
            $t->string('description');
            $t->string('image_filename');
            $t->timestamp('created_at');
        });
        $schema->create('activity_post_comments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('post_id');
            $t->unsignedBigInteger('user_id');
            $t->string('body');
            $t->timestamp('created_at');
        });
        $schema->create('activity_post_likes', function (Blueprint $t) {
            $t->unsignedBigInteger('post_id');
            $t->unsignedBigInteger('user_id');
            $t->timestamp('created_at');
        });
        $schema->create('user_settings', function (Blueprint $t) {
            $t->unsignedBigInteger('user_id');
            $t->text('settings_json');
        });

        $source = DB::connection('bewegingsapp');
        $at = '2026-11-05 11:00:00';
        $user = ['password_hash' => Hash::make('geheim123'), 'full_name' => 'Sanne', 'about_me' => 'Hoi', 'birth_date' => '2000-01-01', 'created_at' => $at, 'xp' => 40];
        $source->table('users')->insert([
            ['id' => 20, 'email' => 'sanne@example.com', 'username' => 'sanne'] + $user,
            ['id' => 21, 'email' => 'ander@example.com', 'username' => 'bestaat'] + $user,
        ]);
        $source->table('challenges')->insert([['id' => 53]]);
        $source->table('challenge_completions')->insert([
            ['user_id' => 20, 'challenge_id' => 53, 'challenge_interval' => 332199, 'completed_at' => $at],
            ['user_id' => 20, 'challenge_id' => 1, 'challenge_interval' => 332199, 'completed_at' => $at],
        ]);
        $source->table('activity_posts')->insert(['id' => 9, 'user_id' => 20, 'title' => 'Run', 'description' => 'Ochtend', 'image_filename' => 'foto.webp', 'created_at' => $at]);
        $source->table('activity_post_comments')->insert(['post_id' => 9, 'user_id' => 20, 'body' => 'Goed bezig', 'created_at' => $at]);
        $source->table('activity_post_likes')->insert(['post_id' => 9, 'user_id' => 20, 'created_at' => $at]);
        $source->table('user_settings')->insert([
            'user_id' => 20,
            'settings_json' => '{"frequency":15,"darkMode":true,"workOnly":true,"workStart":"09:30","workEnd":"17:00","workDays":[1,2,3],"workSchedule":[]}',
        ]);
    }
}
