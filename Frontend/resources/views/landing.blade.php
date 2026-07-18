<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>
        <h1>Analisis Trend Roblox</h1>
        <p>Analisis genre & tren game Roblox dari data snapshot.</p>
        @if ($snapshot)
            <p>{{ $snapshot['game_count'] }} game dianalisis</p>
        @endif
        <a href="/dashboard">Lihat Dashboard</a>
    </main>
</body>
</html>
