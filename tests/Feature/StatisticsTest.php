<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_page_shows_summary_chart_and_badges(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('statistics'))
            ->assertOk()
            ->assertSeeInOrder([
                'Activiteitsstatistieken',
                'Dagelijks gemiddelde',
                'Langste reeks',
                'Wekelijkse bewegingsminuten',
                'Behaalde badges',
                'Vergrendelde badges',
            ])
            ->assertSee('aria-current="page"', false);
    }

    public function test_navbar_links_to_statistics_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertSee('href="'.route('statistics').'"', false);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('statistics'))->assertRedirect(route('login'));
    }
}
