@extends('layouts.app')

@section('content')
    <section data-workspace="ringkasan" class="space-y-8">
        <x-analytics.page-heading
            eyebrow="Market pulse / Live snapshot"
            title="Genre intelligence."
            description="Pantau komposisi genre Roblox, pemain aktif, dan kualitas untuk membaca peluang pasar dengan lebih jelas."
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-analytics.metric-card label="Total Game" :value="$snapshot['game_count'] ?? '—'" />
            <x-analytics.metric-card label="Jumlah Genre" :value="count($ranking['ranking'])" />
            <x-analytics.metric-card label="Rating Rata-rata" :value="count($ranking['ranking']) ? round(collect($ranking['ranking'])->avg('avg_rating'), 1) : '—'" />
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-ui.card variant="sectioned">
                <x-ui.card-header>
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">Demand / Active players</p>
                    <x-ui.card-title as="h2">Rata-rata Pemain Aktif per Genre</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content>
                    <div id="rankingchart" aria-hidden="true"></div>
                    <div class="sr-only">
                        <h3>Data rata-rata pemain aktif per genre</h3>
                        <ul>
                            @foreach ($ranking['ranking'] as $row)
                                <li>{{ $row['genreL1'] }}: {{ round($row['avg_playing']) }} pemain aktif rata-rata</li>
                            @endforeach
                        </ul>
                    </div>
                </x-ui.card-content>
            </x-ui.card>

            <x-ui.card variant="sectioned">
                <x-ui.card-header>
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">Supply / Share</p>
                    <x-ui.card-title as="h2">Komposisi Genre (%)</x-ui.card-title>
                </x-ui.card-header>
                <x-ui.card-content>
                    <div id="sharechart" aria-hidden="true"></div>
                    <div class="sr-only">
                        <h3>Data komposisi genre</h3>
                        <ul>
                            @foreach ($ranking['share'] as $genre => $share)
                                <li>{{ $genre }}: {{ $share }}%</li>
                            @endforeach
                        </ul>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        <x-ui.card variant="sectioned">
            <x-ui.card-header>
                <p class="text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">Market leaderboard</p>
                <x-ui.card-title as="h2">Ranking Genre</x-ui.card-title>
            </x-ui.card-header>
            <x-ui.card-content>
                <x-ui.table>
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
                                <x-ui.table-cell class="font-medium">{{ $row['genreL1'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ round($row['avg_rating'], 1) }}</x-ui.table-cell>
                            </x-ui.table-row>
                        @endforeach
                    </x-ui.table-body>
                </x-ui.table>
            </x-ui.card-content>
        </x-ui.card>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ranking = @json($ranking['ranking']);
            const share = @json($ranking['share']);

            registerChart(document.querySelector('#rankingchart'), {
                chart: { type: 'bar', height: 340, toolbar: { show: false } },
                plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
                series: [{ name: 'Avg Playing', data: ranking.map(row => Math.round(row.avg_playing)) }],
                xaxis: { categories: ranking.map(row => row.genreL1) },
                dataLabels: { enabled: false },
            });

            registerChart(document.querySelector('#sharechart'), {
                chart: { type: 'donut', height: 340, toolbar: { show: false } },
                series: Object.values(share),
                labels: Object.keys(share),
                dataLabels: { enabled: false },
                legend: { position: 'bottom' },
            });
        });
    </script>
@endsection
