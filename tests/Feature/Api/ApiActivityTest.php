<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_with_image_can_be_posted_and_everyone_sees_it(): void
    {
        Storage::fake(Activity::IMAGE_DISK);
        $sanne = User::factory()->create(['username' => 'sanne']);
        Sanctum::actingAs($sanne);

        $this->post(route('api.v1.activities.store'), [
            'title' => 'Fietsen', 'description' => 'Naar school', 'image' => UploadedFile::fake()->image('fiets.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('activity.user.username', 'sanne');

        $activity = Activity::query()->firstOrFail();
        Storage::disk(Activity::IMAGE_DISK)->assertExists($activity->image_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity_created']);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson(route('api.v1.activities.index'))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Fietsen')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_title_and_description_are_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.activities.store'), [])
            ->assertJsonValidationErrors(['title', 'description'])
            ->assertJsonPath('errors.title.0', 'Vul een titel in.');
    }

    public function test_like_toggles_and_comments_can_be_added_and_removed_by_writer_only(): void
    {
        $activity = User::factory()->create()->activities()->create(['title' => 'Zwemmen', 'description' => 'Baantjes']);
        $me = User::factory()->create(['username' => 'ik']);
        Sanctum::actingAs($me);

        $this->postJson(route('api.v1.activities.like', $activity))->assertJsonPath('data.liked_by_viewer', true)->assertJsonPath('data.likes_count', 1);
        $this->postJson(route('api.v1.activities.like', $activity))->assertJsonPath('data.liked_by_viewer', false)->assertJsonPath('data.likes_count', 0);

        $this->postJson(route('api.v1.activities.comment', $activity), ['body' => '   '])->assertJsonValidationErrors('body');
        $comment = $this->postJson(route('api.v1.activities.comment', $activity), ['body' => 'Goed bezig!'])
            ->assertCreated()
            ->assertJsonPath('data.comments.0.body', 'Goed bezig!')
            ->assertJsonPath('data.comments.0.can_delete', true)
            ->json('data.comments.0.id');

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson(route('api.v1.comments.destroy', $comment))->assertForbidden();

        Sanctum::actingAs($me);
        $this->deleteJson(route('api.v1.comments.destroy', $comment))->assertOk()->assertJsonPath('data.comments_count', 0);
    }

    public function test_own_and_user_activities_are_paginated(): void
    {
        $me = User::factory()->create();
        foreach (range(1, 7) as $i) {
            $me->activities()->create(['title' => "Activiteit {$i}", 'description' => 'x']);
        }
        Sanctum::actingAs($me);

        $this->getJson(route('api.v1.me.activities'))->assertJsonCount(5, 'data')->assertJsonPath('meta.last_page', 2);
        $this->getJson(route('api.v1.users.activities', ['user' => $me, 'page' => 2]))->assertJsonCount(2, 'data');
    }
}
