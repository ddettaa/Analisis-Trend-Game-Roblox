@extends('layouts.app')

@section('content')
    <section data-workspace="ringkasan" class="space-y-8">
        <x-analytics.page-heading
            eyebrow="Market pulse / Live snapshot"
            title="Genre intelligence."
            description="Baca komposisi pasar Roblox, pemain aktif, dan kualitas rata-rata setiap genre dalam satu workspace."
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-analytics.metric-card label="Total Game" :value="$snapshot['game_count'] ?? '—'" />
            <x-analytics.metric-card label="Jumlah Genre" :value="count($ranking['ranking'])" />
            <x-analytics.metric-card label="Rating Rata-rata" :value="count($ranking['ranking']) ? round(collect($ranking['ranking'])->avg('avg_rating'), 1) : '—'" />
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.card variant="sectioned" class="editorial-card" data-ui="overview-chart-card">
                <x-ui.card-header>
                    <p class="editorial-eyebrow">Demand / Active players</p>
                    <h2 class="leading-none font-semibold">Rata-rata Pemain Aktif per Genre</h2>
                </x-ui.card-header>
                <x-ui.card-content>
                    @if (count($ranking['ranking']))
                        <div id="rankingchart" aria-hidden="true"></div>
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

            <x-ui.card variant="sectioned" class="editorial-card" data-ui="overview-chart-card">
                <x-ui.card-header>
                    <p class="editorial-eyebrow">Supply / Share</p>
                    <h2 class="leading-none font-semibold">Komposisi Genre (%)</h2>
                </x-ui.card-header>
                <x-ui.card-content>
                    @if (count($ranking['share']))
                        <div id="sharechart" aria-hidden="true"></div>
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

        <x-ui.card variant="sectioned" class="editorial-card" data-ui="overview-table-card">
            <x-ui.card-header>
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">Market leaderboard</p>
                <h2 id="ranking-genre-title" class="leading-none font-semibold">Ranking Genre</h2>
            </x-ui.card-header>
            <x-ui.card-content>
                @if (count($ranking['ranking']))
                    <x-ui.table aria-labelledby="ranking-genre-title">
                        <x-ui.table-header>
                            <x-ui.table-row>
                                <x-ui.table-head>Genre</x-ui.table-head>
                                <x-ui.table-head>Game</x-ui.table-head>
                                <x-ui.table-head>Avg Playing</x-ui.table-head>
                                <x-ui.table-head>Avg Rating</x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @foreach ($ranking['ranking'] as $row)
                                <x-ui.table-row>
                                    <th scope="row" class="p-2 align-middle whitespace-nowrap font-medium">{{ $row['genreL1'] }}</th>
                                    <x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell>
                                    <x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell>
                                    <x-ui.table-cell>{{ round($row['avg_rating'], 1) }}</x-ui.table-cell>
                                </x-ui.table-row>
                            @endforeach
                        </x-ui.table-body>
                    </x-ui.table>
                @else
                    <p class="text-sm text-muted-foreground">Belum ada data ranking.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ranking = @json($ranking['ranking']);
            const share = @json($ranking['share']);

            if (ranking.length) {
                registerChart(document.querySelector('#rankingchart'), {
                    chart: { type: 'bar', height: 340, toolbar: { show: false } },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
                    series: [{ name: 'Avg Playing', data: ranking.map(row => Math.round(row.avg_playing)) }],
                    xaxis: { categories: ranking.map(row => row.genreL1) },
                    dataLabels: { enabled: false },
                });
            }

            if (Object.keys(share).length) {
                registerChart(document.querySelector('#sharechart'), {
                    chart: { type: 'donut', height: 340, toolbar: { show: false } },
                    series: Object.values(share),
                    labels: Object.keys(share),
                    dataLabels: { enabled: false },
                    legend: { position: 'bottom' },
                });
            }
        });
    </script>
@endsection
