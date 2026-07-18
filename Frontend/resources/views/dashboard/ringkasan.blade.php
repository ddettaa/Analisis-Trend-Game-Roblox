@extends('layouts.app')
@section('content')
    <h1>Ringkasan Genre</h1>
    <div id="sharechart"></div>
    <div id="rankingchart"></div>
    <table border="1">
        <thead><tr><th>Genre</th><th>Game</th><th>Avg Playing</th><th>Avg Rating</th></tr></thead>
        <tbody>
        @foreach ($ranking['ranking'] as $row)
            <tr><td>{{ $row['genreL1'] }}</td><td>{{ $row['game_count'] }}</td><td>{{ round($row['avg_playing']) }}</td><td>{{ round($row['avg_rating'], 1) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <script>
        const share = @json($ranking['share']);
        const ranking = @json($ranking['ranking']);
        new ApexCharts(document.querySelector("#sharechart"), {
            chart: { type: 'pie', height: 320 },
            series: Object.values(share),
            labels: Object.keys(share),
            title: { text: 'Komposisi Genre (%)' }
        }).render();
        new ApexCharts(document.querySelector("#rankingchart"), {
            chart: { type: 'bar', height: 360 },
            series: [{ name: 'Avg Playing', data: ranking.map(r => Math.round(r.avg_playing)) }],
            xaxis: { categories: ranking.map(r => r.genreL1) },
            title: { text: 'Rata-rata Pemain Aktif per Genre' }
        }).render();
    </script>
@endsection
