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
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Buka Dashboard')
            ->assertSee('781')
            ->assertSee('Kami membaca bagaimana tren menjadi peluang.')
            ->assertSee('Data adalah sebuah praktik.')
            ->assertSee('Ranking')
            ->assertSee('Saturasi')
            ->assertSee('Momentum')
            ->assertSee('Simulation')
            ->assertSee('data-page="landing"', false)
            ->assertSee('id="analisis"', false)
            ->assertSee('id="sinyal"', false)
            ->assertSee('id="metode"', false)
            ->assertSee('id="catatan"', false)
            ->assertSee('data-ui="signal-stream"', false)
            ->assertSee('data-ui="paper-planes"', false)
            ->assertSee('data-ui="target-rings"', false)
            ->assertSee('data-ui="genre-ranking-list"', false)
            ->assertSee('animations: { enabled: false }', false)
            ->assertSee('try {', false)
            ->assertDontSee('data-ui="ascii-field"', false);

        $this->assertSame(1, substr_count($response->getContent(), 'href="/"'));
    }

    public function test_landing_survives_api_down(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $this->get('/')->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Kami membaca bagaimana tren menjadi peluang.');
    }

    public function test_landing_handles_empty_ranking(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 2, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 0], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Kami membaca bagaimana tren menjadi peluang.')
            ->assertSee('Belum terbaca');

        $response->assertSeeInOrder([
            'data-slot="alert-title"',
            'Belum terbaca',
            'Ranking genre belum tersedia',
        ], false);
    }

    public function test_landing_reports_ranking_endpoint_failure(): void
    {
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/api/snapshot')) {
                return Http::response(['snapshot_id' => 3, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 42], 200);
            }

            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });

        $this->get('/')->assertOk()
            ->assertSee('Kami membaca bagaimana tren menjadi peluang.')
            ->assertSee('Buka Dashboard')
            ->assertSee('Server analisis tidak aktif.');
    }
}
