@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold">Ringkasan Genre</h1>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.card variant="sectioned">
            <x-ui.card-header><x-ui.card-title class="text-sm text-muted-foreground">Total Game</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><span class="text-3xl font-bold">{{ $snapshot['game_count'] ?? '—' }}</span></x-ui.card-content>
        </x-ui.card>
        <x-ui.card variant="sectioned">
            <x-ui.card-header><x-ui.card-title class="text-sm text-muted-foreground">Jumlah Genre</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><span class="text-3xl font-bold">{{ count($ranking['ranking']) }}</span></x-ui.card-content>
        </x-ui.card>
        <x-ui.card variant="sectioned">
            <x-ui.card-header><x-ui.card-title class="text-sm text-muted-foreground">Rating Rata-rata</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><span class="text-3xl font-bold">{{ count($ranking['ranking']) ? round(collect($ranking['ranking'])->avg('avg_rating'), 1) : '—' }}</span></x-ui.card-content>
        </x-ui.card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card variant="sectioned">
            <x-ui.card-header><x-ui.card-title>Komposisi Genre (%)</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><div id="sharechart"></div></x-ui.card-content>
        </x-ui.card>
        <x-ui.card variant="sectioned">
            <x-ui.card-header><x-ui.card-title>Rata-rata Pemain Aktif per Genre</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><div id="rankingchart"></div></x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.card variant="sectioned">
        <x-ui.card-header><x-ui.card-title>Ranking Genre</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <table class="w-full text-sm">
                <thead class="border-b border-border text-left text-muted-foreground">
                    <tr><th class="py-2">Genre</th><th>Game</th><th>Avg Playing</th><th>Avg Rating</th></tr>
                </thead>
                <tbody>
                @foreach ($ranking['ranking'] as $row)
                    <tr class="border-b border-border/50">
                        <td class="py-2 font-medium">{{ $row['genreL1'] }}</td>
                        <td>{{ $row['game_count'] }}</td>
                        <td>{{ round($row['avg_playing']) }}</td>
                        <td>{{ round($row['avg_rating'], 1) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const share = @json($ranking['share']);
            const ranking = @json($ranking['ranking']);
            registerChart(document.querySelector('#sharechart'), {
                chart: { type: 'pie', height: 320 },
                series: Object.values(share),
                labels: Object.keys(share),
            });
            registerChart(document.querySelector('#rankingchart'), {
                chart: { type: 'bar', height: 320 },
                series: [{ name: 'Avg Playing', data: ranking.map(r => Math.round(r.avg_playing)) }],
                xaxis: { categories: ranking.map(r => r.genreL1) },
            });
        });
    </script>
@endsection
