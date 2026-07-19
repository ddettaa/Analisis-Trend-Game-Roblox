@extends('layouts.app')

@section('content')
    @php
        $sortedShares = collect($ranking['share'])->sortDesc();
        $shareChart = $sortedShares->take(4);

        if ($sortedShares->count() > 4) {
            $shareChart->put('Other', $sortedShares->skip(4)->sum());
        }

        $shareChartLabels = $shareChart->keys()->values()->all();
        $shareChartSeries = $shareChart->values()->all();
    @endphp

    <section data-workspace="ringkasan" class="space-y-5">
        <x-analytics.page-heading
            eyebrow="Market pulse / Live snapshot"
            title="Market overview"
            description="Baca komposisi pasar Roblox, pemain aktif, dan kualitas rata-rata setiap genre dalam satu workspace."
        />

        <div data-ui="kpi-grid" class="grid gap-3 sm:grid-cols-3">
            <x-analytics.metric-card class="min-h-28" label="Total Game" :value="$snapshot['game_count'] ?? '—'" />
            <x-analytics.metric-card class="min-h-28" label="Jumlah Genre" :value="count($ranking['ranking'])" />
            <x-analytics.metric-card class="min-h-28" label="Rating Rata-rata" :value="count($ranking['ranking']) ? round(collect($ranking['ranking'])->avg('avg_rating'), 1) : '—'" />
        </div>

        <div class="grid min-w-0 gap-4 xl:grid-cols-8">
            <x-ui.card
                variant="sectioned"
                class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none xl:col-span-5"
                data-ui="overview-chart-card"
            >
                <x-ui.card-header class="gap-1 border-b px-4 py-3">
                    <p class="saas-label">Demand / Active players</p>
                    <h2 class="text-sm font-semibold tracking-[-0.01em]">Rata-rata Pemain Aktif per Genre</h2>
                </x-ui.card-header>
                <x-ui.card-content class="p-4">
                    @if (count($ranking['ranking']))
                        <div id="rankingchart" class="min-w-0" aria-hidden="true"></div>
                        <div class="sr-only">
                            <h3>Data rata-rata pemain aktif per genre</h3>
                            <ul>
                                @foreach ($ranking['ranking'] as $row)
                                    <li>{{ $row['genreL1'] }}: {{ round($row['avg_playing']) }} pemain aktif rata-rata</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">Belum ada data ranking.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card
                variant="sectioned"
                class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none xl:col-span-3"
                data-ui="overview-chart-card"
            >
                <x-ui.card-header class="gap-1 border-b px-4 py-3">
                    <p class="saas-label">Supply / Share</p>
                    <h2 class="text-sm font-semibold tracking-[-0.01em]">Komposisi Genre (%)</h2>
                </x-ui.card-header>
                <x-ui.card-content class="p-4">
                    @if (count($ranking['share']))
                        <div id="sharechart" class="min-w-0" aria-hidden="true"></div>
                        <div class="sr-only">
                            <h3>Data komposisi genre</h3>
                            <ul>
                                @foreach ($ranking['share'] as $genre => $share)
                                    <li>{{ $genre }}: {{ $share }}%</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">Belum ada data komposisi genre.</p>
                    @endif
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card
            variant="sectioned"
            class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none"
            data-ui="overview-table-card"
        >
            <x-ui.card-header class="gap-1 border-b px-4 py-3">
                <p class="saas-label">Market leaderboard</p>
                <h2 id="ranking-genre-title" class="text-sm font-semibold tracking-[-0.01em]">Ranking Genre</h2>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                @if (count($ranking['ranking']))
                    <x-ui.table aria-labelledby="ranking-genre-title" class="text-xs sm:text-sm">
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head class="h-9 px-4">Genre</x-ui.table-head>
                                <x-ui.table-head class="h-9">Game</x-ui.table-head>
                                <x-ui.table-head class="h-9">Avg Playing</x-ui.table-head>
                                <x-ui.table-head class="h-9 pr-4">Avg Rating</x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @foreach ($ranking['ranking'] as $row)
                                <x-ui.table-row>
                                    <th scope="row" class="px-4 py-2 text-left align-middle whitespace-nowrap font-medium">{{ $row['genreL1'] }}</th>
                                    <x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell>
                                    <x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell>
                                    <x-ui.table-cell class="pr-4">{{ round($row['avg_rating'], 1) }}</x-ui.table-cell>
                                </x-ui.table-row>
                            @endforeach
                        </x-ui.table-body>
                    </x-ui.table>
                @else
                    <p class="px-4 py-5 text-sm text-muted-foreground">Belum ada data ranking.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ranking = @json($ranking['ranking']);
            const shareChartLabels = @json($shareChartLabels);
            const shareChartSeries = @json($shareChartSeries);
            const rankingTarget = document.querySelector('#rankingchart');
            const shareTarget = document.querySelector('#sharechart');
            const compactCategoryLabel = (value, maxLength) => (
                value.length > maxLength ? value.slice(0, maxLength - 1) + '…' : value
            );

            if (rankingTarget && ranking.length) {
                registerChart(rankingTarget, {
                    chart: { type: 'bar', height: 320, toolbar: { show: false } },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
                    series: [{ name: 'Avg Playing', data: ranking.map(row => Math.round(row.avg_playing)) }],
                    xaxis: { categories: ranking.map(row => row.genreL1) },
                    dataLabels: { enabled: false },
                    responsive: [{
                        breakpoint: 640,
                        options: {
                            chart: { height: Math.max(320, ranking.length * 36) },
                            plotOptions: { bar: { horizontal: true, barHeight: '62%' } },
                            xaxis: { categories: ranking.map(row => row.genreL1) },
                            yaxis: {
                                labels: {
                                    maxWidth: 120,
                                    formatter: value => compactCategoryLabel(value, 18),
                                },
                            },
                        },
                    }],
                });
            }

            if (shareTarget && shareChartLabels.length) {
                registerChart(shareTarget, {
                    chart: { type: 'donut', height: 320, toolbar: { show: false } },
                    series: shareChartSeries,
                    labels: shareChartLabels,
                    dataLabels: { enabled: false },
                    legend: { position: 'bottom' },
                    responsive: [{
                        breakpoint: 640,
                        options: {
                            chart: { height: 280 },
                            legend: { position: 'bottom', fontSize: '11px', itemMargin: { horizontal: 6, vertical: 2 } },
                            plotOptions: { pie: { donut: { size: '64%' } } },
                        },
                    }],
                });
            }
        });
    </script>
@endsection
