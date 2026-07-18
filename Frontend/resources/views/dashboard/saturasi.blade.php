@extends('layouts.app')

@section('content')
    <section data-workspace="saturasi" class="space-y-8">
        <x-analytics.page-heading
            eyebrow="Supply pressure / Genre map"
            title="Market saturation."
            description="Lihat kepadatan pasokan tiap genre untuk mengenali ruang tumbuh dan kompetisi pasar."
        />

        <div aria-label="Legenda status saturasi" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            <span class="font-medium text-foreground">Status</span>
            <x-ui.badge tone="danger">oversaturated</x-ui.badge>
            <x-ui.badge tone="success">emerging</x-ui.badge>
            <x-ui.badge tone="neutral">healthy</x-ui.badge>
        </div>

        <x-ui.card variant="sectioned" class="editorial-card" data-ui="saturation-chart-card">
            <x-ui.card-header>
                <p class="editorial-eyebrow">Supply / Genre volume</p>
                <h2 class="leading-none font-semibold">Jumlah Game per Genre</h2>
            </x-ui.card-header>
            <x-ui.card-content>
                @if (count($saturation['data']))
                    <div id="satchart" aria-hidden="true"></div>
                @else
                    <p class="text-sm text-muted-foreground">Belum ada data saturasi.</p>
                @endif
            </x-ui.card-content>
        </x-ui.card>

        <x-ui.card variant="sectioned" class="editorial-card" data-ui="saturation-table-card">
            <x-ui.card-header>
                <p class="editorial-eyebrow">Market detail / Supply</p>
                <h2 id="saturation-table-title" class="leading-none font-semibold">Detail Saturasi</h2>
            </x-ui.card-header>
            <x-ui.card-content>
                <x-ui.table aria-labelledby="saturation-table-title">
                    <x-ui.table-header>
                        <x-ui.table-row>
                            <x-ui.table-head>Genre</x-ui.table-head>
                            <x-ui.table-head>Game</x-ui.table-head>
                            <x-ui.table-head>Avg Playing</x-ui.table-head>
                            <x-ui.table-head>Status</x-ui.table-head>
                        </x-ui.table-row>
                    </x-ui.table-header>
                    <x-ui.table-body>
                        @forelse ($saturation['data'] as $row)
                            @php($tone = $row['status'] === 'oversaturated' ? 'danger' : ($row['status'] === 'emerging' ? 'success' : 'neutral'))
                            <x-ui.table-row>
                                <th scope="row" class="p-2 align-middle whitespace-nowrap font-medium">{{ $row['genreL1'] }}</th>
                                <x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell>
                                <x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell>
                                <x-ui.table-cell><x-ui.badge :tone="$tone">{{ $row['status'] }}</x-ui.badge></x-ui.table-cell>
                            </x-ui.table-row>
                        @empty
                            <x-ui.table-row>
                                <x-ui.table-cell colspan="4" class="py-6 text-center text-muted-foreground">Belum ada data saturasi.</x-ui.table-cell>
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
            const colorFor = status => status === 'oversaturated' ? '#dc2626' : (status === 'emerging' ? '#16a34a' : '#94a3b8');

            if (saturation.length) {
                registerChart(document.querySelector('#satchart'), {
                    chart: { type: 'bar', height: 380, toolbar: { show: false } },
                    plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
                    series: [{ name: 'Jumlah Game', data: saturation.map(row => ({ x: row.genreL1, y: row.game_count, fillColor: colorFor(row.status) })) }],
                    dataLabels: { enabled: false },
                });
            }
        });
    </script>
@endsection
