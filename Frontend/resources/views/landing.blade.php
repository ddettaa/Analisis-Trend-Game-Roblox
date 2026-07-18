<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
<main class="mx-auto max-w-4xl px-6 py-16 space-y-12">
    {{-- Hero --}}
    <section class="text-center space-y-4">
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight">Analisis Trend Roblox</h1>
        <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
            Pantau genre yang naik daun, deteksi game viral muda, dan lihat peta saturasi pasar — dari data snapshot Roblox asli.
        </p>
        <a href="/dashboard" class="inline-flex items-center gap-2 rounded-md bg-primary text-primary-foreground px-6 py-3 font-medium hover:opacity-90">
            Lihat Dashboard <x-lucide-arrow-right class="size-4" />
        </a>
    </section>

    {{-- Statistik live --}}
    @if ($snapshot)
        <section class="grid gap-4 sm:grid-cols-3 text-center">
            <div class="rounded-xl border border-border bg-card p-6">
                <div class="text-3xl font-bold"
                     x-data="{ n: 0, target: {{ (int) $snapshot['game_count'] }} }"
                     x-init="const s = Math.max(1, Math.round(target / 60)); const t = setInterval(() => { n = Math.min(target, n + s); if (n >= target) clearInterval(t); }, 16)"
                     x-text="n.toLocaleString('id-ID')">{{ $snapshot['game_count'] }}</div>
                <div class="text-sm text-muted-foreground mt-1">Game dianalisis</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-6">
                <div class="text-3xl font-bold"
                     x-data="{ n: 0, target: {{ count($ranking['ranking']) }} }"
                     x-init="const t = setInterval(() => { n = Math.min(target, n + 1); if (n >= target) clearInterval(t); }, 40)"
                     x-text="n">{{ count($ranking['ranking']) }}</div>
                <div class="text-sm text-muted-foreground mt-1">Genre terpetakan</div>
            </div>
            <div class="rounded-xl border border-border bg-card p-6">
                <div class="text-3xl font-bold">{{ $ranking['ranking'][0]['genreL1'] ?? '—' }}</div>
                <div class="text-sm text-muted-foreground mt-1">Genre teratas</div>
            </div>
        </section>

        {{-- Mini chart top-5 --}}
        <section class="rounded-xl border border-border bg-card p-6">
            <h2 class="font-semibold mb-4">Top 5 Genre Berdasarkan Jumlah Game</h2>
            <div id="minichart"></div>
        </section>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const top5 = @json(array_slice($ranking['ranking'], 0, 5));
                registerChart(document.querySelector('#minichart'), {
                    chart: { type: 'bar', height: 240, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                    series: [{ name: 'Jumlah Game', data: top5.map(r => r.game_count) }],
                    xaxis: { categories: top5.map(r => r.genreL1) },
                });
            });
        </script>
    @else
        <section class="text-center text-sm text-muted-foreground rounded-xl border border-border bg-card p-6">
            @if ($status === 'unavailable')
                Statistik live belum tersedia — server analisis tidak aktif.
            @elseif ($status === 'no_data')
                Statistik live belum tersedia — belum ada data snapshot.
            @else
                Statistik live belum tersedia.
            @endif
        </section>
    @endif

    <footer class="text-center text-xs text-muted-foreground pt-8">
        Open source — data di-scrape dari Roblox discover &amp; games API.
    </footer>
</main>
</body>
</html>
