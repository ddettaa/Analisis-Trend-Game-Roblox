<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private function assertMetricCardValue(string $html, string $label, string $value): void
    {
        $this->assertMatchesRegularExpression(
            '/<div\b(?=[^>]*\bdata-ui="metric-card")[^>]*>.*?<p\b[^>]*>\s*'
                .preg_quote($label, '/')
                .'\s*<\/p>.*?<p\b[^>]*>\s*'
                .preg_quote($value, '/')
                .'\s*<\/p>/s',
            $html
        );
    }

    /**
     * @param  list<string>  $expectedCells
     */
    private function assertTableRowValues(string $html, string $expectedHeader, array $expectedCells): void
    {
        preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/s', $html, $rows);

        $rowHtml = null;
        foreach ($rows[1] as $candidate) {
            if (! preg_match('/<th\b(?=[^>]*\bscope="row")[^>]*>(.*?)<\/th>/s', $candidate, $header)) {
                continue;
            }

            $normalizedHeader = trim(html_entity_decode(strip_tags($header[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($normalizedHeader === $expectedHeader) {
                $rowHtml = $candidate;
                break;
            }
        }

        $this->assertNotNull($rowHtml, 'Missing table row for '.$expectedHeader);

        preg_match_all('/<td\b.*?">(.*?)<\/td>/s', $rowHtml, $cells);
        $actualCells = array_map(
            fn (string $cell): string => trim(html_entity_decode(strip_tags($cell), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $cells[1]
        );

        $this->assertSame($expectedCells, $actualCells, 'Unexpected cells for '.$expectedHeader);
    }

    private function fakeAll(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response([
                'ranking' => [[
                    'genreL1' => 'Adventure',
                    'game_count' => 37,
                    'avg_visits' => 345678,
                    'avg_playing' => 2468,
                    'avg_rating' => 87.4,
                ]],
                'share' => [
                    'Adventure' => 30.0,
                    'Simulation' => 20.0,
                    'Roleplay' => 16.0,
                    'Sports' => 14.0,
                    'Obby' => 12.0,
                    'Horror' => 8.0,
                ],
            ], 200),
            '*/api/genre/saturation' => Http::response([
                'data' => [
                    ['genreL1' => 'Builder & Tycoon', 'game_count' => 43, 'avg_playing' => 1250, 'status' => 'oversaturated'],
                    ['genreL1' => '<Fresh RPG>', 'game_count' => 7, 'avg_playing' => 8765, 'status' => 'emerging'],
                    ['genreL1' => 'Adventure', 'game_count' => 19, 'avg_playing' => 4321, 'status' => 'healthy'],
                ],
            ], 200),
            '*/api/viral-muda*' => Http::response([
                'max_umur' => 90,
                'data' => [[
                    'name' => '<Momentum & Co>',
                    'playing' => 1200,
                    'umur_hari' => 30,
                    'playing_per_hari' => 40,
                    'genreL1' => 'Action & Adventure',
                ]],
            ], 200),
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
                href="/override"
                as="a"
                x-data="unsafeState"
                @click="unsafeClick()"
                class="caller-class"
            />
        BLADE);

        $this->assertStringContainsString('data-ui="theme-toggle"', $html);
        $this->assertMatchesRegularExpression('/^\s*<button\b(?=[^>]*\bdata-ui="theme-toggle")[^>]*>/s', $html);
        $this->assertSame(1, substr_count($html, 'type="button"'));
        $this->assertStringContainsString('aria-label="Ganti tema warna"', $html);
        $this->assertStringContainsString('title="Ganti tema warna"', $html);
        $this->assertStringContainsString('$store.theme.toggle()', $html);
        $this->assertStringContainsString('size-10', $html);
        $this->assertStringContainsString('caller-class', $html);
        $this->assertStringNotContainsString('unsafeState', $html);
        $this->assertStringNotContainsString('unsafeClick', $html);
        $this->assertStringNotContainsString('Override theme', $html);
        $this->assertStringNotContainsString('Override title', $html);
        $this->assertStringNotContainsString('href="/override"', $html);
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
            ->assertSee('Market overview')
            ->assertSee('Baca komposisi pasar Roblox, pemain aktif, dan kualitas rata-rata setiap genre dalam satu workspace.')
            ->assertSee('data-workspace="ringkasan"', false)
            ->assertSee('data-ui="kpi-grid"', false)
            ->assertSeeInOrder(['Total Game', 'Jumlah Genre', 'Rating Rata-rata', 'Ranking Genre'])
            ->assertSee('data-shell="saas-dashboard"', false)
            ->assertSee('data-ui="command-bar"', false)
            ->assertSee('data-ui="icon-rail"', false)
            ->assertSee('Roblox Signals')
            ->assertSee('data-ui="dashboard-nav"', false)
            ->assertSee('data-ui="mobile-dashboard-nav"', false)
            ->assertSee('padding-bottom: env(safe-area-inset-bottom)', false)
            ->assertSee('data-page="ringkasan"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('scope="row"', false);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/<h1\\b[^>]*>\\s*Market overview\\s*<\\/h1>/s', $html);
        $this->assertSame(2, substr_count($html, 'data-ui="overview-chart-card"'));
        $this->assertSame(1, substr_count($html, 'data-ui="overview-table-card"'));
        $this->assertSame(2, substr_count($html, 'registerChart('));
        $this->assertSame(2, substr_count($html, 'breakpoint: 640'));
        $this->assertStringNotContainsString('new ApexCharts', $html);
        $this->assertMatchesRegularExpression('/<h2\\b[^>]*>\\s*Rata-rata Pemain Aktif per Genre\\s*<\\/h2>/s', $html);
        $this->assertMatchesRegularExpression('/<h2\\b[^>]*>\\s*Komposisi Genre \\(%\\)\\s*<\\/h2>/s', $html);
        $this->assertMatchesRegularExpression('/<h2\\b[^>]*>\\s*Ranking Genre\\s*<\\/h2>/s', $html);
        $this->assertMetricCardValue($html, 'Total Game', '781');
        $this->assertMetricCardValue($html, 'Jumlah Genre', '1');
        $this->assertMetricCardValue($html, 'Rating Rata-rata', '87.4');
        $this->assertTableRowValues($html, 'Adventure', ['37', '2468', '87.4']);
        $this->assertStringContainsString('chart: { height: Math.max(320, ranking.length * 36) }', $html);
        $this->assertStringContainsString('maxWidth: 120', $html);
        $this->assertStringContainsString(
            'const shareChartLabels = ["Adventure","Simulation","Roleplay","Sports","Other"];',
            $html
        );
        $this->assertStringContainsString('const shareChartSeries = [30,20,16,14,20];', $html);
        $this->assertStringContainsString('<li>Obby: 12%</li>', $html);
        $this->assertStringContainsString('<li>Horror: 8%</li>', $html);
        $this->assertStringContainsString(
            "legend: { position: 'bottom', fontSize: '11px', itemMargin: { horizontal: 6, vertical: 2 } }",
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bdata-page="ringkasan")(?=[^>]*\baria-current="page")[^>]*>/s',
            $html
        );
    }

    public function test_saturasi_page_ok(): void
    {
        $this->fakeAll();
        $response = $this->get('/dashboard/saturasi');

        $response
            ->assertOk()
            ->assertSee('Market saturation')
            ->assertSee('Bandingkan jumlah game dan pemain aktif untuk melihat genre yang padat, sehat, atau baru tumbuh.')
            ->assertSee('data-workspace="saturasi"', false)
            ->assertSee('data-ui="kpi-grid"', false)
            ->assertSee('data-ui="saturation-chart-card"', false)
            ->assertSee('data-ui="saturation-table-card"', false)
            ->assertSeeInOrder(['Genre terpantau', 'Oversaturated', 'Emerging'])
            ->assertSee('Builder &amp; Tycoon', false)
            ->assertSee('&lt;Fresh RPG&gt;', false)
            ->assertSee('Adventure')
            ->assertSee('scope="row"', false);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<h1\b[^>]*>\s*Market saturation\s*<\/h1>/s', $html);
        $this->assertSame(1, substr_count($html, 'data-ui="saturation-chart-card"'));
        $this->assertSame(1, substr_count($html, 'data-ui="saturation-table-card"'));
        $this->assertSame(1, substr_count($html, 'registerChart('));
        $this->assertSame(1, substr_count($html, 'breakpoint: 640'));
        $this->assertStringNotContainsString('new ApexCharts', $html);
        $this->assertStringContainsString('chart: { height: Math.max(320, saturation.length * 36) }', $html);
        $this->assertStringContainsString('maxWidth: 120', $html);
        $this->assertMatchesRegularExpression(
            '/<div\b(?=[^>]*\brole="group")(?=[^>]*\baria-labelledby="saturation-status-legend")[^>]*>'
                .'.*?<span\b(?=[^>]*\bid="saturation-status-legend")[^>]*>\s*Status\s*<\/span>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bdata-page="saturasi")(?=[^>]*\baria-current="page")[^>]*>/s',
            $html
        );
        $this->assertMetricCardValue($html, 'Genre terpantau', '3');
        $this->assertMetricCardValue($html, 'Oversaturated', '1');
        $this->assertMetricCardValue($html, 'Emerging', '1');
        $this->assertTableRowValues($html, 'Builder & Tycoon', ['43', '1250', 'oversaturated']);
        $this->assertTableRowValues($html, '<Fresh RPG>', ['7', '8765', 'emerging']);
        $this->assertTableRowValues($html, 'Adventure', ['19', '4321', 'healthy']);
    }

    public function test_overview_empty_data_preserves_fallbacks_and_skips_chart_mounts(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
            '*/api/genre/saturation' => Http::response(['data' => []], 200),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 200),
        ]);

        $html = $this->get('/dashboard')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'Belum ada data ranking.'));
        $this->assertSame(1, substr_count($html, 'Belum ada data komposisi genre.'));
        $this->assertStringNotContainsString('id="rankingchart"', $html);
        $this->assertStringNotContainsString('id="sharechart"', $html);
        $this->assertMetricCardValue($html, 'Jumlah Genre', '0');
        $this->assertMetricCardValue($html, 'Rating Rata-rata', '—');
    }

    public function test_saturation_empty_data_preserves_fallbacks_and_skips_chart_mount(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
            '*/api/genre/saturation' => Http::response(['data' => []], 200),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 200),
        ]);

        $html = $this->get('/dashboard/saturasi')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'Belum ada data saturasi.'));
        $this->assertStringNotContainsString('id="satchart"', $html);
        $this->assertMetricCardValue($html, 'Genre terpantau', '0');
        $this->assertMetricCardValue($html, 'Oversaturated', '0');
        $this->assertMetricCardValue($html, 'Emerging', '0');
    }

    public function test_viral_page_ok(): void
    {
        $this->fakeAll();
        $response = $this->get('/dashboard/viral');

        $response
            ->assertOk()
            ->assertSee('Early momentum')
            ->assertSee('Game viral muda (umur &lt; 90 hari) diurutkan berdasarkan pertumbuhan pemain per hari.', false)
            ->assertSee('data-workspace="viral"', false)
            ->assertSee('data-ui="kpi-grid"', false)
            ->assertSee('data-ui="viral-chart-card"', false)
            ->assertSee('data-ui="viral-table-card"', false)
            ->assertSee('umur &lt; 90 hari', false)
            ->assertSee('&lt;Momentum &amp; Co&gt;', false)
            ->assertSee('1200')
            ->assertSee('30')
            ->assertSee('40')
            ->assertSee('Action &amp; Adventure', false)
            ->assertSee('scope="row"', false);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<h1\b[^>]*>\s*Early momentum\s*<\/h1>/s', $html);
        $this->assertMatchesRegularExpression(
            '/<span\b(?=[^>]*\bdata-ui="workspace-context")[^>]*>\s*Early momentum\s*<\/span>/s',
            $html
        );
        $this->assertSame(2, substr_count($html, 'Early momentum'));
        $this->assertStringNotContainsString('Young momentum', $html);
        $this->assertSame(1, substr_count($html, 'data-ui="viral-chart-card"'));
        $this->assertSame(1, substr_count($html, 'data-ui="viral-table-card"'));
        $this->assertSame(1, substr_count($html, 'registerChart('));
        $this->assertSame(1, substr_count($html, 'breakpoint: 640'));
        $this->assertStringContainsString('chart: { height: Math.max(360, viral.length * 36) }', $html);
        $this->assertStringContainsString('maxWidth: 104', $html);
        $this->assertStringNotContainsString('new ApexCharts', $html);
        $this->assertMatchesRegularExpression(
            '/<a\b(?=[^>]*\bdata-page="viral")(?=[^>]*\baria-current="page")[^>]*>/s',
            $html
        );
    }

    public function test_viral_chart_equivalent_is_capped_at_top_fifteen_while_table_keeps_all_rows(): void
    {
        $viralRows = array_map(
            fn (int $index): array => [
                'name' => sprintf('Momentum signal %02d with a deliberately long game name', $index),
                'playing' => 1000 + $index,
                'umur_hari' => $index,
                'playing_per_hari' => 200 - $index,
                'genreL1' => 'Genre '.$index,
            ],
            range(1, 16)
        );

        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
            '*/api/genre/saturation' => Http::response(['data' => []], 200),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => $viralRows], 200),
        ]);

        $html = $this->get('/dashboard/viral')->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match(
                '/<div\b(?=[^>]*\bdata-ui="viral-chart-equivalent")[^>]*>(.*?)<\/div>/s',
                $html,
                $equivalent
            )
        );

        $previousPosition = -1;
        foreach (array_slice($viralRows, 0, 15) as $row) {
            $position = strpos($equivalent[1], $row['name']);
            $this->assertNotFalse($position);
            $this->assertGreaterThan($previousPosition, $position);
            $previousPosition = $position;
        }

        $this->assertStringNotContainsString($viralRows[15]['name'], $equivalent[1]);
        $this->assertTableRowValues(
            $html,
            $viralRows[15]['name'],
            ['1016', '16', '184', 'Genre 16']
        );
    }

    public function test_viral_page_keeps_chart_and_table_empty_fallbacks(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
            '*/api/genre/saturation' => Http::response(['data' => []], 200),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 200),
        ]);

        $response = $this->get('/dashboard/viral')->assertOk();
        $html = $response->getContent();

        $this->assertSame(2, substr_count($html, 'Belum ada game viral muda.'));
        $this->assertStringNotContainsString('id="viralchart"', $html);
        $this->assertSame(1, substr_count($html, 'registerChart('));
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
