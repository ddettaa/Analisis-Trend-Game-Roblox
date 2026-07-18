<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <nav>
        <a href="/">Ringkasan</a> |
        <a href="/saturasi">Saturasi</a> |
        <a href="/viral">Viral Muda</a>
    </nav>

    @if ($status === 'unavailable')
        <p style="color:red">Server analisis tidak aktif. Jalankan: <code>uvicorn api:app --port 8000</code> di folder Backend.</p>
    @elseif ($status === 'no_data')
        <p style="color:orange">Belum ada data snapshot. Jalankan ingest dulu.</p>
    @elseif ($snapshot)
        <p>Snapshot #{{ $snapshot['snapshot_id'] }} — {{ $snapshot['game_count'] }} game — {{ $snapshot['taken_at'] }}</p>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
