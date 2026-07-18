@extends('layouts.app')

@section('content')
    <section data-workspace="viral" class="space-y-8">
        <x-analytics.page-heading
            eyebrow="Early signals / < 90 days"
            title="Young momentum."
            description="Temukan game baru dengan pertumbuhan pemain cepat (umur < 90 hari)."
        />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-analytics.metric-card
                label="Game terdeteksi"
                :value="count($viral['data'])"
                :annotation="'Window max '.($viral['max_umur'] ?? 90).' hari'"
            />
        </div>

        <x-ui.card variant="sectioned" class="editorial-card" data-ui="viral-chart-card">
            <x-ui.card-header>
                <p class="editorial-eyebrow">Velocity / Top 15</p>
                <h2 class="leading-none font-semibold">Kecepatan Pertumbuhan Pemain</h2>
            </x-ui.card-header>
            <x-ui.card-content>
                @if (count($viral['data']))
                    <div id="viralchart" aria-hidden="true"></div>
                @else
                    <p class="text-sm text-muted-foreground">Belum ada game viral muda.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card variant="sectioned" class="editorial-card" data-ui="viral-table-card">
            <x-ui.card-header>
                <p class="editorial-eyebrow">Early signals / Game list</p>
                <h2 id="viral-table-title" class="leading-none font-semibold">Daftar Game</h2>
            </x-ui.card-header>
            <x-ui.card-content>
                <x-ui.table aria-labelledby="viral-table-title">
                    <x-ui.table-header>
                        <x-ui.table-row>
                            <x-ui.table-head>Nama</x-ui.table-head>
                            <x-ui.table-head>Playing</x-ui.table-head>
                            <x-ui.table-head>Umur (hari)</x-ui.table-head>
                            <x-ui.table-head>Playing/hari</x-ui.table-head>
                            <x-ui.table-head>Genre</x-ui.table-head>
                        </x-ui.table-row>
                    </x-ui.table-header>
                    <x-ui.table-body>
                        @forelse ($viral['data'] as $row)
                            <x-ui.table-row>
                                <th scope="row" class="p-2 align-middle whitespace-nowrap font-medium">{{ $row['name'] }}</th>
                                <x-ui.table-cell>{{ $row['playing'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ $row['umur_hari'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ $row['playing_per_hari'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ $row['genreL1'] }}</x-ui.table-cell>
                            </x-ui.table-row>
                        @empty
                            <x-ui.table-row>
                                <x-ui.table-cell colspan="5" class="py-6 text-center text-muted-foreground">Belum ada game viral muda.</x-ui.table-cell>
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

            if (viral.length) {
                registerChart(document.querySelector('#viralchart'), {
                    chart: { type: 'bar', height: 420, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
                    series: [{ name: 'Playing/hari', data: viral.map(row => row.playing_per_hari) }],
                    xaxis: { categories: viral.map(row => row.name) },
                    dataLabels: { enabled: false },
                });
            }
        });
    </script>
@endsection
