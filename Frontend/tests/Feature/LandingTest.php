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
            ->assertSee('class="sr-only"', false)
            ->assertSee('<div id="minichart" data-ui="genre-ranking-chart" class="landing-minichart" aria-hidden="true" inert></div>', false)
            ->assertSee('data-ui="editorial-footer"', false)
            ->assertSee('aria-label="Navigasi footer"', false)
            ->assertSee('data-ui="footer-year"', false)
            ->assertSee('data-ui="dashboard-cta"', false)
            ->assertSee('aria-label="Buka Dashboard"', false)
            ->assertSee('animations: { enabled: false }', false)
            ->assertSee('try {', false)
            ->assertDontSee('data-ui="ascii-field"', false);

        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'href="/"'));
        $this->assertSame(5, substr_count($content, 'data-ui="primary-nav-link"'));
        $this->assertSame(4, substr_count($content, 'data-ui="hero-line"'));
        $this->assertStringContainsString('<span data-ui="hero-line">Kami membaca</span>', $content);
        $this->assertStringContainsString('<span data-ui="hero-line">bagaimana</span>', $content);
        $this->assertStringContainsString('<span data-ui="hero-line">tren menjadi</span>', $content);
        $this->assertStringContainsString('<span data-ui="hero-line">peluang.</span>', $content);

        $response->assertSeeInOrder([
            '<a href="#analisis" data-ui="primary-nav-link">Ranking</a>',
            '<a href="#analisis" data-ui="primary-nav-link">Saturasi</a>',
            '<a href="#analisis" data-ui="primary-nav-link">Momentum</a>',
            '<a href="#metode" data-ui="primary-nav-link">Tentang</a>',
            '<a href="#catatan" data-ui="primary-nav-link">Catatan Data</a>',
        ], false);
    }

    public function test_landing_survives_api_down(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Kami membaca bagaimana tren menjadi peluang.')
            ->assertSee('Server analisis tidak aktif.')
            ->assertSee('data-ui="fallback-note"', false)
            ->assertSee('Definisikan pertanyaan', false)
            ->assertDontSee('0 genre')
            ->assertDontSee('registerChart(document.querySelector', false);

        $this->assertSame(4, substr_count($response->getContent(), 'data-ui="fallback-note"'));
    }

    public function test_landing_handles_missing_snapshot_with_editorial_notes(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['detail' => 'not found'], 404),
            '*/api/genre/ranking' => Http::response([
                'ranking' => [['genreL1' => 'Simulation', 'game_count' => 999, 'avg_visits' => 1, 'avg_playing' => 1, 'avg_rating' => 92]],
                'share' => ['Simulation' => 100.0],
            ], 200),
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Belum ada data snapshot.')
            ->assertSee('data-ui="fallback-note"', false)
            ->assertSee('Periksa kesegaran data')
            ->assertDontSee('0 genre')
            ->assertDontSee('999 game tercatat dalam snapshot ini.')
            ->assertDontSee('registerChart(document.querySelector', false);

        $this->assertSame(4, substr_count($response->getContent(), 'data-ui="fallback-note"'));
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
            ->assertSee('Belum terbaca')
            ->assertSee('data-ui="fallback-note"', false)
            ->assertSee('Hubungkan tiga lensa')
            ->assertDontSee('0 genre')
            ->assertDontSee('registerChart(document.querySelector', false);

        $response->assertSeeInOrder([
            'data-slot="alert-title"',
            'Belum terbaca',
            'Ranking genre belum tersedia',
        ], false);

        $this->assertSame(4, substr_count($response->getContent(), 'data-ui="fallback-note"'));
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
            ->assertSee('Server analisis tidak aktif.')
            ->assertSee('data-ui="fallback-note"', false)
            ->assertDontSee('0 genre')
            ->assertDontSee('registerChart(document.querySelector', false);
    }
}
