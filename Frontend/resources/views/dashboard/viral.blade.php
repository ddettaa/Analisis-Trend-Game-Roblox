@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold">Game Viral Muda <span class="text-base font-normal text-muted-foreground">(umur &lt; 90 hari)</span></h1>

    <x-ui.card variant="sectioned">
        <x-ui.card-header><x-ui.card-title>Top 15 Kecepatan Pertumbuhan Pemain</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content><div id="viralchart"></div></x-ui.card-content>
    </x-ui.card>

    <x-ui.card variant="sectioned">
        <x-ui.card-header><x-ui.card-title>Daftar Game</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <table class="w-full text-sm">
                <thead class="border-b border-border text-left text-muted-foreground">
                    <tr><th class="py-2">Nama</th><th>Playing</th><th>Umur (hari)</th><th>Playing/hari</th><th>Genre</th></tr>
                </thead>
                <tbody>
                @foreach ($viral['data'] as $row)
                    <tr class="border-b border-border/50">
                        <td class="py-2 font-medium">{{ $row['name'] }}</td>
                        <td>{{ $row['playing'] }}</td>
                        <td>{{ $row['umur_hari'] }}</td>
                        <td>{{ $row['playing_per_hari'] }}</td>
                        <td>{{ $row['genreL1'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viral = @json($viral['data']).slice(0, 15);
            registerChart(document.querySelector('#viralchart'), {
                chart: { type: 'bar', height: 400 },
                plotOptions: { bar: { horizontal: true } },
                series: [{ name: 'Playing/hari', data: viral.map(r => r.playing_per_hari) }],
                xaxis: { categories: viral.map(r => r.name) },
            });
        });
    </script>
@endsection
