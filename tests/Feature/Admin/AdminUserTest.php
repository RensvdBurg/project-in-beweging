<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['username' => 'beheerder']);
        $this->actingAs($this->admin);
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'full_name' => 'Bram de Vries',
            'username' => 'bram',
            'email' => 'bram@example.com',
            'password' => 'geheim123',
            'birth_date' => '2005-04-12',
            'xp' => 40,
            'about_me' => 'Hallo',
            'is_admin' => '0',
        ];
    }

    public function test_index_lists_and_searches_users(): void
    {
        User::factory()->create(['username' => 'sanne', 'full_name' => 'Sanne Jansen']);
        User::factory()->create(['username' => 'piet', 'full_name' => 'Piet Klaassen']);

        $this->get(route('admin.users.index'))->assertSee('sanne')->assertSee('piet');
        $this->get(route('admin.users.index', ['q' => 'JANSEN']))->assertSee('sanne')->assertDontSee('piet');
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->post(route('admin.users.store'), $this->validData())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('admin_status');

        $user = User::query()->where('username', 'bram')->firstOrFail();
        $this->assertTrue(Hash::check('geheim123', $user->password));
        $this->assertSame(40, $user->xp);
        $this->assertSame('2005-04-12', $user->birth_date->toDateString());
        $this->assertFalse($user->is_admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_user_created', 'user_id' => $this->admin->id]);
    }

    public function test_create_validates_unique_username_ignoring_case_and_requires_password(): void
    {
        User::factory()->create(['username' => 'Bram']);

        $this->post(route('admin.users.store'), $this->validData(['password' => '']))
            ->assertSessionHasErrors(['username', 'password']);
    }

    public function test_admin_can_edit_a_user_without_changing_the_password(): void
    {
        $user = User::factory()->create(['username' => 'oud']);
        $oldHash = $user->password;

        $this->put(route('admin.users.update', $user), $this->validData(['password' => '', 'is_admin' => '1']))
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('bram', $user->username);
        $this->assertSame($oldHash, $user->password);
        $this->assertTrue($user->is_admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_user_updated']);
    }

    public function test_admin_can_keep_own_username_and_cannot_remove_own_admin_rights(): void
    {
        $this->put(route('admin.users.update', $this->admin), $this->validData([
            'username' => 'beheerder', 'email' => $this->admin->email, 'password' => '', 'is_admin' => '0',
        ]))->assertSessionHasNoErrors();

        $this->assertTrue($this->admin->fresh()->is_admin);
    }

    public function test_admin_can_delete_a_user_with_their_images(): void
    {
        Storage::fake(Activity::IMAGE_DISK);
        $user = User::factory()->create(['username' => 'weg']);
        $path = UploadedFile::fake()->image('foto.jpg')->store("activities/{$user->id}", Activity::IMAGE_DISK);
        $user->activities()->create(['title' => 'Wandelen', 'description' => 'Rondje', 'image_path' => $path]);

        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('activities', ['user_id' => $user->id]);
        Storage::disk(Activity::IMAGE_DISK)->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_user_deleted']);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $this->delete(route('admin.users.destroy', $this->admin))
            ->assertSessionHas('admin_status_type', 'error');

        $this->assertModelExists($this->admin);
    }
}
