@extends('layouts.app')
@section('content')
    <h1>Game Viral Muda (umur < 90 hari)</h1>
    <div id="viralchart"></div>
    <table border="1">
        <thead><tr><th>Nama</th><th>Playing</th><th>Umur (hari)</th><th>Playing/hari</th><th>Genre</th></tr></thead>
        <tbody>
        @foreach ($viral['data'] as $row)
            <tr><td>{{ $row['name'] }}</td><td>{{ $row['playing'] }}</td><td>{{ $row['umur_hari'] }}</td><td>{{ $row['playing_per_hari'] }}</td><td>{{ $row['genreL1'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viral = @json($viral['data']).slice(0, 15);
            registerChart(document.querySelector("#viralchart"), {
                chart: { type: 'bar', height: 400 },
                plotOptions: { bar: { horizontal: true } },
                series: [{ name: 'Playing/hari', data: viral.map(r => r.playing_per_hari) }],
                xaxis: { categories: viral.map(r => r.name) },
                title: { text: 'Top 15 Kecepatan Pertumbuhan Pemain' }
            });
        });
    </script>
@endsection
