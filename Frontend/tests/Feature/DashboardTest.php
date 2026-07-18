<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
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

    public function test_component_contract_stable_markers_cannot_be_overridden(): void
    {
        $components = [
            'brand-mark' => '<x-analytics.brand-mark data-ui="override" class="caller-class" />',
            'theme-toggle' => '<x-analytics.theme-toggle data-ui="override" class="caller-class" />',
            'dashboard-nav' => '<x-analytics.dashboard-nav data-ui="override" class="caller-class" />',
            'metric-card' => '<x-analytics.metric-card label="Games" value="10" data-ui="override" class="caller-class" />',
            'page-heading' => '<x-analytics.page-heading eyebrow="Market" title="Overview" data-ui="override" class="caller-class" />',
            'status-panel' => '<x-analytics.status-panel status="unavailable" data-ui="override" class="caller-class" />',
        ];

        foreach ($components as $marker => $template) {
            $html = Blade::render($template);

            $this->assertSame(1, substr_count($html, 'data-ui="'.$marker.'"'), $marker);
            $this->assertStringNotContainsString('data-ui="override"', $html, $marker);
            $this->assertStringContainsString('caller-class', $html, $marker);
        }
    }

    public function test_component_contract_brand_reserves_link_and_accessible_name(): void
    {
        $html = Blade::render('<x-analytics.brand-mark href="/override" aria-label="Override brand" />');

        $this->assertSame(1, substr_count($html, 'href="/"'));
        $this->assertSame(1, substr_count($html, 'aria-label="Roblox Signals — beranda"'));
        $this->assertStringNotContainsString('/override', $html);
        $this->assertStringNotContainsString('Override brand', $html);
    }

    public function test_component_contract_theme_toggle_reserves_behavior_and_accessibility(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-analytics.theme-toggle
                data-ui="override"
                aria-label="Override theme"
                title="Override title"
                variant="default"
                size="xs"
                x-data="unsafeState"
                @click="unsafeClick()"
                class="caller-class"
            />
        BLADE);

        $this->assertStringContainsString('data-ui="theme-toggle"', $html);
        $this->assertStringContainsString('aria-label="Ganti tema warna"', $html);
        $this->assertStringContainsString('title="Ganti tema warna"', $html);
        $this->assertStringContainsString('$store.theme.toggle()', $html);
        $this->assertStringContainsString('size-10', $html);
        $this->assertStringContainsString('caller-class', $html);
        $this->assertStringNotContainsString('unsafeState', $html);
        $this->assertStringNotContainsString('unsafeClick', $html);
        $this->assertStringNotContainsString('Override theme', $html);
        $this->assertStringNotContainsString('Override title', $html);
    }

    public function test_component_contract_dashboard_nav_reserves_accessible_name(): void
    {
        $html = Blade::render('<x-analytics.dashboard-nav aria-label="Override navigation" />');

        $this->assertSame(1, substr_count($html, 'aria-label="Navigasi dashboard"'));
        $this->assertStringNotContainsString('Override navigation', $html);
    }

    public function test_component_contract_page_heading_renders_named_action(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-analytics.page-heading eyebrow="Market" title="Overview">
                <x-slot:action><button type="button">Refresh data</button></x-slot:action>
            </x-analytics.page-heading>
        BLADE);

        $this->assertStringContainsString('<button type="button">Refresh data</button>', $html);
    }

    public function test_component_contract_status_panels_keep_distinct_semantic_tones(): void
    {
        $unavailable = Blade::render('<x-analytics.status-panel status="unavailable" tone="neutral" />');
        $noData = Blade::render('<x-analytics.status-panel status="no_data" tone="neutral" />');

        $this->assertStringContainsString('border-destructive/20', $unavailable);
        $this->assertStringContainsString('border-warning/20', $noData);
        $this->assertNotSame($unavailable, $noData);
    }

    public function test_component_contract_command_bar_has_accessible_mobile_targets(): void
    {
        $this->fakeAll();
        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bdata-ui="brand-mark")(?=[^>]*\bclass="[^"]*\bmin-h-11\b)[^>]*>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bhref="\/")(?=[^>]*\bclass="[^"]*\bmin-h-11\b)[^>]*>\s*Beranda\s*<\/a>/s',
            $html
        );
    }

    public function test_ringkasan_page_ok(): void
    {
        $this->fakeAll();
        $response = $this->get('/dashboard');

        $response
            ->assertStatus(200)
            ->assertSee('Adventure')
            ->assertSee('Genre intelligence')
            ->assertSee('Baca komposisi pasar Roblox, pemain aktif, dan kualitas rata-rata setiap genre dalam satu workspace.')
            ->assertSee('data-workspace="ringkasan"', false)
            ->assertSeeInOrder(['Total Game', 'Jumlah Genre', 'Rating Rata-rata', 'Ranking Genre'])
            ->assertSee('data-shell="saas-dashboard"', false)
            ->assertSee('data-ui="command-bar"', false)
            ->assertSee('data-ui="icon-rail"', false)
            ->assertSee('Market overview')
            ->assertSee('Roblox Signals')
            ->assertSee('data-ui="dashboard-nav"', false)
            ->assertSee('data-ui="mobile-dashboard-nav"', false)
            ->assertSee('padding-bottom: env(safe-area-inset-bottom)', false)
            ->assertSee('data-page="ringkasan"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('scope="row"', false);

        $html = $response->getContent();

        $this->assertGreaterThanOrEqual(3, substr_count($html, 'editorial-card'));
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'editorial-eyebrow'));
        $this->assertSame(2, substr_count($html, 'data-ui="overview-chart-card"'));
        $this->assertSame(1, substr_count($html, 'data-ui="overview-table-card"'));
        $this->assertMatchesRegularExpression('/<h2\\b[^>]*>\\s*Rata-rata Pemain Aktif per Genre\\s*<\\/h2>/s', $html);
        $this->assertMatchesRegularExpression('/<h2\\b[^>]*>\\s*Komposisi Genre \\(%\\)\\s*<\\/h2>/s', $html);
        $this->assertMatchesRegularExpression('/<h2\\b[^>]*>\\s*Ranking Genre\\s*<\\/h2>/s', $html);
    }

    public function test_saturasi_page_ok(): void
    {
        $this->fakeAll();
        $response = $this->get('/dashboard/saturasi');

        $response
            ->assertOk()
            ->assertSee('Market saturation.')
            ->assertSee('Bandingkan jumlah game dan pemain aktif untuk melihat genre yang padat, sehat, atau baru tumbuh.')
            ->assertSee('data-workspace="saturasi"', false)
            ->assertSee('healthy');

        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bdata-page="saturasi")(?=[^>]*\baria-current="page")[^>]*>/s',
            $response->getContent()
        );
    }

    public function test_viral_page_ok(): void
    {
        $this->fakeAll();
        $response = $this->get('/dashboard/viral');

        $response
            ->assertOk()
            ->assertSee('Young momentum.')
            ->assertSee('Game viral muda (umur &lt; 90 hari) diurutkan berdasarkan pertumbuhan pemain per hari.', false)
            ->assertSee('data-workspace="viral"', false)
            ->assertSee('umur &lt; 90 hari', false)
            ->assertSee('Belum ada game viral muda.');

        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bdata-page="viral")(?=[^>]*\baria-current="page")[^>]*>/s',
            $response->getContent()
        );
    }

    public function test_shows_banner_when_unavailable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $this->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('uvicorn')
            ->assertSee('Server analisis tidak aktif.');
    }

    public function test_shows_banner_when_error(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['detail' => 'boom'], 500),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 500),
            '*/api/genre/saturation' => Http::response(['data' => []], 500),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 500),
        ]);
        $this->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Server analisis mengembalikan error.')
            ->assertSee('Belum ada data ranking.');
    }
}
