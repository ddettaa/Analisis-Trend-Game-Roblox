@extends('layouts.app')

@section('content')
    @php
        $saturationRows = collect($saturation['data']);
        $oversaturatedCount = $saturationRows->where('status', 'oversaturated')->count();
        $emergingCount = $saturationRows->where('status', 'emerging')->count();
    @endphp

    <section data-workspace="saturasi" class="space-y-5">
        <x-analytics.page-heading
            eyebrow="Supply pressure / Genre map"
            title="Market saturation"
            description="Bandingkan jumlah game dan pemain aktif untuk melihat genre yang padat, sehat, atau baru tumbuh."
        />

        <div data-ui="kpi-grid" class="grid gap-3 sm:grid-cols-3">
            <x-analytics.metric-card class="min-h-28" label="Genre terpantau" :value="$saturationRows->count()" />
            <x-analytics.metric-card class="min-h-28" label="Oversaturated" :value="$oversaturatedCount" />
            <x-analytics.metric-card class="min-h-28" label="Emerging" :value="$emergingCount" />
        </div>

        <div aria-label="Legenda status saturasi" class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            <span class="saas-label mr-1 text-foreground">Status</span>
            <x-ui.badge tone="danger">oversaturated</x-ui.badge>
            <x-ui.badge tone="success">emerging</x-ui.badge>
            <x-ui.badge tone="neutral">healthy</x-ui.badge>
        </div>

        <x-ui.card
            variant="sectioned"
            class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none"
            data-ui="saturation-chart-card"
        >
            <x-ui.card-header class="gap-1 border-b px-4 py-3">
                <p class="saas-label">Supply / Genre volume</p>
                <h2 class="text-sm font-semibold tracking-[-0.01em]">Jumlah Game per Genre</h2>
            </x-ui.card-header>
            <x-ui.card-content class="p-4">
                @if ($saturationRows->isNotEmpty())
                    <div id="satchart" class="min-w-0" aria-hidden="true"></div>
                    <div class="sr-only">
                        <h3>Data jumlah game dan status saturasi per genre</h3>
                        <ul>
                            @foreach ($saturationRows as $row)
                                <li>{{ $row['genreL1'] }}: {{ $row['game_count'] }} game, status {{ $row['status'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-sm text-muted-foreground">Belum ada data saturasi.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card
            variant="sectioned"
            class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none"
            data-ui="saturation-table-card"
        >
            <x-ui.card-header class="gap-1 border-b px-4 py-3">
                <p class="saas-label">Market detail / Supply</p>
                <h2 id="saturation-table-title" class="text-sm font-semibold tracking-[-0.01em]">Detail Saturasi</h2>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                <x-ui.table aria-labelledby="saturation-table-title" class="text-xs sm:text-sm">
                    <x-ui.table-header>
                        <x-ui.table-row>
                            <x-ui.table-head class="h-9 px-4">Genre</x-ui.table-head>
                            <x-ui.table-head class="h-9">Game</x-ui.table-head>
                            <x-ui.table-head class="h-9">Avg Playing</x-ui.table-head>
                            <x-ui.table-head class="h-9 pr-4">Status</x-ui.table-head>
                        </x-ui.table-row>
                    </x-ui.table-header>
                    <x-ui.table-body>
                        @forelse ($saturationRows as $row)
                            @php($tone = $row['status'] === 'oversaturated' ? 'danger' : ($row['status'] === 'emerging' ? 'success' : 'neutral'))
                            <x-ui.table-row>
                                <th scope="row" class="px-4 py-2 text-left align-middle whitespace-nowrap font-medium">{{ $row['genreL1'] }}</th>
                                <x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell>
                                <x-ui.table-cell class="pr-4"><x-ui.badge :tone="$tone">{{ $row['status'] }}</x-ui.badge></x-ui.table-cell>
                            </x-ui.table-row>
                        @empty
                            <x-ui.table-row>
                                <x-ui.table-cell colspan="4" class="py-5 text-center text-muted-foreground">Belum ada data saturasi.</x-ui.table-cell>
                            </x-ui.table-row>
                        @endforelse
                    </x-ui.table-body>
                </x-ui.table>
            </x-ui.card-content>
        </x-ui.card>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const saturation = @json($saturation['data']);
            const target = document.querySelector('#satchart');

            if (target && saturation.length) {
                registerChart(target, {
                    chart: { type: 'bar', height: 320, toolbar: { show: false } },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
                    series: [{ name: 'Jumlah Game', data: saturation.map(row => ({ x: row.genreL1, y: row.game_count })) }],
                    dataLabels: { enabled: false },
                });
            }
        });
    </script>
@endsection
