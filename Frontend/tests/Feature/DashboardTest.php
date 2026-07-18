<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private function fakeAll(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [['genreL1' => 'Adventure', 'game_count' => 10, 'avg_visits' => 1, 'avg_playing' => 1, 'avg_rating' => 90]], 'share' => ['Adventure' => 100.0]], 200),
            '*/api/genre/saturation' => Http::response(['data' => [['genreL1' => 'Adventure', 'game_count' => 10, 'avg_playing' => 1, 'status' => 'healthy']]], 200),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 200),
        ]);
    }

    public function test_ringkasan_page_ok(): void
    {
        $this->fakeAll();
        $this->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Adventure')
            ->assertSee('ROBLOX.TRENDS')
            ->assertSee('data-ui="dashboard-nav"', false)
            ->assertSee('data-page="ringkasan"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_saturasi_page_ok(): void
    {
        $this->fakeAll();
        $this->get('/dashboard/saturasi')->assertStatus(200);
    }

    public function test_viral_page_ok(): void
    {
        $this->fakeAll();
        $this->get('/dashboard/viral')->assertStatus(200);
    }

    public function test_shows_banner_when_unavailable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $this->get('/dashboard')->assertStatus(200)->assertSee('uvicorn');
    }

    public function test_shows_banner_when_error(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['detail' => 'boom'], 500),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 500),
            '*/api/genre/saturation' => Http::response(['data' => []], 500),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 500),
        ]);
        $this->get('/dashboard')->assertStatus(200)->assertSee('Server analisis mengembalikan error');
    }
}
