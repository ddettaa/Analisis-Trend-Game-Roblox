@extends('layouts.app')

@section('content')
    <section data-workspace="viral" class="space-y-5">
        <x-analytics.page-heading
            eyebrow="Early signals / < 90 days"
            title="Early momentum"
            description="Game viral muda (umur < 90 hari) diurutkan berdasarkan pertumbuhan pemain per hari."
        />

        <div data-ui="kpi-grid" class="grid gap-3 sm:grid-cols-3">
            <x-analytics.metric-card
                class="min-h-28"
                label="Game terdeteksi"
                :value="count($viral['data'])"
                :annotation="'Window max '.($viral['max_umur'] ?? 90).' hari'"
            />
        </div>

        <x-ui.card
            variant="sectioned"
            class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none"
            data-ui="viral-chart-card"
        >
            <x-ui.card-header class="gap-1 border-b px-4 py-3">
                <p class="saas-label">Velocity / Top 15</p>
                <h2 class="text-sm font-semibold tracking-[-0.01em]">Kecepatan Pertumbuhan Pemain</h2>
            </x-ui.card-header>
            <x-ui.card-content class="p-4">
                @if (count($viral['data']))
                    <div id="viralchart" class="min-w-0" aria-hidden="true"></div>
                    <div data-ui="viral-chart-equivalent" class="sr-only">
                        <h3>Data pertumbuhan pemain per hari</h3>
                        <ul>
                            @foreach (array_slice($viral['data'], 0, 15) as $row)
                                <li>{{ $row['name'] }}: {{ $row['playing_per_hari'] }} pemain per hari</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-sm text-muted-foreground">Belum ada game viral muda.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card
            variant="sectioned"
            class="saas-panel min-w-0 gap-0 overflow-hidden py-0 shadow-none"
            data-ui="viral-table-card"
        >
            <x-ui.card-header class="gap-1 border-b px-4 py-3">
                <p class="saas-label">Early signals / Game list</p>
                <h2 id="viral-table-title" class="text-sm font-semibold tracking-[-0.01em]">Daftar Game</h2>
            </x-ui.card-header>
            <x-ui.card-content class="p-0">
                <x-ui.table aria-labelledby="viral-table-title" class="text-xs sm:text-sm">
                    <x-ui.table-header>
                        <x-ui.table-row>
                            <x-ui.table-head class="h-9 px-4">Name</x-ui.table-head>
                            <x-ui.table-head class="h-9">Playing</x-ui.table-head>
                            <x-ui.table-head class="h-9">Age</x-ui.table-head>
                            <x-ui.table-head class="h-9">Playing per day</x-ui.table-head>
                            <x-ui.table-head class="h-9 pr-4">Genre</x-ui.table-head>
                        </x-ui.table-row>
                    </x-ui.table-header>
                    <x-ui.table-body>
                        @forelse ($viral['data'] as $row)
                            <x-ui.table-row>
                                <th scope="row" class="px-4 py-2 text-left align-middle whitespace-nowrap font-medium">{{ $row['name'] }}</th>
                                <x-ui.table-cell>{{ $row['playing'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ $row['umur_hari'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ $row['playing_per_hari'] }}</x-ui.table-cell>
                                <x-ui.table-cell class="pr-4">{{ $row['genreL1'] }}</x-ui.table-cell>
                            </x-ui.table-row>
                        @empty
                            <x-ui.table-row>
                                <x-ui.table-cell colspan="5" class="py-5 text-center text-muted-foreground">Belum ada game viral muda.</x-ui.table-cell>
                            </x-ui.table-row>
                        @endforelse
                    </x-ui.table-body>
                </x-ui.table>
            </x-ui.card-content>
        </x-ui.card>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viral = @json($viral['data']).slice(0, 15);
            const target = document.querySelector('#viralchart');
            const compactGameLabel = value => (
                value.length > 14 ? value.slice(0, 13) + '…' : value
            );

            if (target && viral.length) {
                registerChart(target, {
                    chart: { type: 'bar', height: 360, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
                    series: [{ name: 'Playing/hari', data: viral.map(row => row.playing_per_hari) }],
                    xaxis: { categories: viral.map(row => row.name) },
                    yaxis: {
                        labels: {
                            maxWidth: 132,
                            formatter: value => compactGameLabel(value),
                        },
                    },
                    dataLabels: { enabled: false },
                    responsive: [{
                        breakpoint: 640,
                        options: {
                            chart: { height: Math.max(360, viral.length * 36) },
                            yaxis: {
                                labels: {
                                    maxWidth: 104,
                                    formatter: value => compactGameLabel(value),
                                },
                            },
                        },
                    }],
                });
            }
        });
    </script>
@endsection
