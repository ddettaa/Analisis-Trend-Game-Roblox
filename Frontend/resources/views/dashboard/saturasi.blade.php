@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold">Saturasi Genre</h1>

    <x-ui.card variant="sectioned">
        <x-ui.card-header><x-ui.card-title>Jumlah Game per Genre (warna = status saturasi)</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content><div id="satchart"></div></x-ui.card-content>
    </x-ui.card>

    <x-ui.card variant="sectioned">
        <x-ui.card-header><x-ui.card-title>Detail Saturasi</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <table class="w-full text-sm">
                <thead class="border-b border-border text-left text-muted-foreground">
                    <tr><th class="py-2">Genre</th><th>Game</th><th>Avg Playing</th><th>Status</th></tr>
                </thead>
                <tbody>
                @foreach ($saturation['data'] as $row)
                    <tr class="border-b border-border/50">
                        <td class="py-2 font-medium">{{ $row['genreL1'] }}</td>
                        <td>{{ $row['game_count'] }}</td>
                        <td>{{ round($row['avg_playing']) }}</td>
                        <td>
                            @if ($row['status'] === 'oversaturated')
                                <x-ui.badge variant="destructive">oversaturated</x-ui.badge>
                            @elseif ($row['status'] === 'emerging')
                                <x-ui.badge class="bg-green-600 text-white">emerging</x-ui.badge>
                            @else
                                <x-ui.badge variant="secondary">healthy</x-ui.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sat = @json($saturation['data']);
            const colorFor = s => s === 'oversaturated' ? '#e74c3c' : (s === 'emerging' ? '#2ecc71' : '#95a5a6');
            registerChart(document.querySelector('#satchart'), {
                chart: { type: 'bar', height: 360 },
                series: [{ name: 'Jumlah Game', data: sat.map(r => ({ x: r.genreL1, y: r.game_count, fillColor: colorFor(r.status) })) }],
            });
        });
    </script>
@endsection
