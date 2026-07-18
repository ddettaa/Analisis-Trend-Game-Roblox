@extends('layouts.app')
@section('content')
    <h1>Saturasi Genre</h1>
    <div id="satchart"></div>
    <table border="1">
        <thead><tr><th>Genre</th><th>Game</th><th>Avg Playing</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($saturation['data'] as $row)
            <tr><td>{{ $row['genreL1'] }}</td><td>{{ $row['game_count'] }}</td><td>{{ round($row['avg_playing']) }}</td><td>{{ $row['status'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sat = @json($saturation['data']);
            const colorFor = s => s === 'oversaturated' ? '#e74c3c' : (s === 'emerging' ? '#2ecc71' : '#95a5a6');
            registerChart(document.querySelector("#satchart"), {
                chart: { type: 'bar', height: 360 },
                series: [{ name: 'Jumlah Game', data: sat.map(r => ({ x: r.genreL1, y: r.game_count, fillColor: colorFor(r.status) })) }],
                title: { text: 'Jumlah Game per Genre (warna = status saturasi)' }
            });
        });
    </script>
@endsection
