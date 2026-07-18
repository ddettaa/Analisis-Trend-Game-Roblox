<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LandingTest extends TestCase
{
    public function test_landing_shows_hero_and_stats(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [['genreL1' => 'Simulation', 'game_count' => 190, 'avg_visits' => 1, 'avg_playing' => 1, 'avg_rating' => 92]], 'share' => ['Simulation' => 100.0]], 200),
        ]);
        $this->get('/')->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Lihat Dashboard')
            ->assertSee('781')
            ->assertSee('Decode what Roblox plays.')
            ->assertSee('data-page="landing"', false)
            ->assertSee('data-ui="ascii-field"', false)
            ->assertSee('Simulation');
    }

    public function test_landing_survives_api_down(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $this->get('/')->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Lihat Dashboard');
    }

    public function test_landing_handles_empty_ranking(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 2, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 0], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
        ]);

        $this->get('/')->assertOk()
            ->assertSee('Decode what Roblox plays.')
            ->assertSee('Belum terbaca');
    }
}
