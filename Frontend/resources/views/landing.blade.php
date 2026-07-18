<!DOCTYPE html>
<html lang="id" class="bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script>
        (() => {
            const mode = localStorage.getItem('theme:mode') || 'system';
            const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="landing" class="min-h-screen overflow-x-hidden bg-background text-foreground antialiased">
@php($rankingItems = $ranking['ranking'] ?? [])

<header class="sticky top-0 z-40 h-14 border-b border-border/70 bg-background/90 backdrop-blur">
    <nav aria-label="Navigasi utama" class="mx-auto flex h-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="/" aria-label="Analisis Trend Roblox" class="inline-flex items-center">
            <x-analytics.brand-mark />
        </a>
        <div class="flex items-center gap-2">
            <x-ui.button href="/dashboard" variant="outline" size="sm">Lihat Dashboard</x-ui.button>
            <x-analytics.theme-toggle />
        </div>
    </nav>
</header>

<main>
    <section class="relative isolate flex min-h-[calc(100vh-3.5rem)] items-center overflow-hidden px-4 py-16 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-30" aria-hidden="true">
            <x-analytics.ascii-field data-ui="ascii-field" />
        </div>
        <div class="mx-auto w-full max-w-7xl">
            <div class="max-w-4xl">
                <p class="mb-5 font-mono text-xs font-semibold uppercase tracking-[0.2em] text-primary">Analisis Trend Roblox / Live intelligence</p>
                <h1 aria-label="Decode what Roblox plays." class="max-w-3xl text-5xl font-black tracking-[-0.05em] sm:text-7xl lg:text-8xl">
                    Decode what <span class="text-primary">Roblox</span> plays.
                </h1>
                <p class="mt-7 max-w-2xl text-base leading-7 text-muted-foreground sm:text-lg sm:leading-8">
                    Baca arah pasar Roblox dari sinyal yang sulit terlihat: genre yang menguat, ruang yang mulai padat, dan game muda yang bergerak cepat.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <x-ui.button href="/dashboard" size="lg">Lihat Dashboard</x-ui.button>
                    <p class="font-mono text-xs uppercase tracking-[0.16em] text-muted-foreground">Data discovery, bukan tebakan</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-primary px-4 py-5 text-primary-foreground sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl items-center gap-4 font-mono text-sm sm:text-base">
            <x-analytics.ascii-field data-ui="ascii-field" class="hidden shrink-0 sm:block" />
            <p class="font-semibold tracking-tight">
                @if ($snapshot)
                    {{ number_format((int) $snapshot['game_count'], 0, ',', '.') }} games. One market signal.
                @else
                    Market signals, when data is ready.
                @endif
            </p>
        </div>
    </section>

    @if ($snapshot)
        <section aria-labelledby="metrics-heading" class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-primary">Market pulse</p>
                        <h2 id="metrics-heading" class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Sinyal pasar, dalam satu snapshot.</h2>
                    </div>
                    <p class="max-w-md text-sm leading-6 text-muted-foreground">Ringkas, terukur, dan siap menjadi titik awal eksplorasi lebih dalam.</p>
                </div>
                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <x-ui.card class="p-6 sm:p-8">
                        <p class="text-sm text-muted-foreground">Game dianalisis</p>
                        <p class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ number_format((int) $snapshot['game_count'], 0, ',', '.') }}</p>
                    </x-ui.card>
                    <x-ui.card class="p-6 sm:p-8">
                        <p class="text-sm text-muted-foreground">Genre terpetakan</p>
                        <p class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ count($rankingItems) }}</p>
                    </x-ui.card>
                    <x-ui.card class="p-6 sm:p-8">
                        <p class="text-sm text-muted-foreground">Genre teratas</p>
                        <p class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ $rankingItems[0]['genreL1'] ?? 'Belum terbaca' }}</p>
                    </x-ui.card>
                </div>
            </div>
        </section>
    @else
        <section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-3xl">
                <x-analytics.status-panel :status="$status" />
            </div>
        </section>
    @endif

    <section aria-labelledby="insights-heading" class="border-y border-border bg-muted/30 px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
            <div>
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-primary">Editorial intelligence</p>
                <h2 id="insights-heading" class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Tiga cara membaca pergerakan.</h2>
                <p class="mt-5 max-w-md leading-7 text-muted-foreground">Dari volume genre sampai momentum game baru, dashboard menata data mentah menjadi keputusan yang bisa ditindaklanjuti.</p>
            </div>
            <div class="divide-y divide-border border-y border-border">
                <article class="grid gap-3 py-5 sm:grid-cols-[10rem_1fr]">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-[0.12em]">Ranking Genre</h3>
                    <p class="leading-7 text-muted-foreground">Lihat kategori yang membentuk pasar dan bandingkan besarnya peluang antar-genre.</p>
                </article>
                <article class="grid gap-3 py-5 sm:grid-cols-[10rem_1fr]">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-[0.12em]">Peta Saturasi</h3>
                    <p class="leading-7 text-muted-foreground">Temukan area yang terlalu ramai sebelum waktu dan sumber daya masuk ke pasar yang sempit.</p>
                </article>
                <article class="grid gap-3 py-5 sm:grid-cols-[10rem_1fr]">
                    <h3 class="font-mono text-sm font-bold uppercase tracking-[0.12em]">Viral Muda</h3>
                    <p class="leading-7 text-muted-foreground">Tangkap game baru dengan traksi awal yang berpotensi membentuk pola permainan berikutnya.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-7xl">
            @if (count($rankingItems))
                <x-ui.card class="overflow-hidden">
                    <div class="border-b border-border px-6 py-5 sm:px-8">
                        <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-primary">Live ranking</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight">Genre berdasarkan jumlah game</h2>
                    </div>
                    <div id="minichart" class="px-2 py-4 sm:px-6"></div>
                </x-ui.card>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const top5 = @json(array_slice($rankingItems, 0, 5));
                        registerChart(document.querySelector('#minichart'), {
                            chart: { type: 'bar', height: 280, toolbar: { show: false } },
                            plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
                            series: [{ name: 'Jumlah game', data: top5.map((item) => item.game_count) }],
                            xaxis: { categories: top5.map((item) => item.genreL1) },
                            dataLabels: { enabled: false },
                        });
                    });
                </script>
            @elseif ($status === 'ok')
                <x-ui.alert variant="neutral" title="Belum terbaca">
                    Ranking genre belum tersedia untuk snapshot ini.
                </x-ui.alert>
            @endif
        </div>
    </section>

    <section class="bg-foreground px-4 py-16 text-background sm:px-6 lg:px-8 lg:py-24">
        <div class="mx-auto flex max-w-7xl flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-primary">Ready for the next signal?</p>
                <h2 class="mt-3 text-4xl font-black tracking-[-0.04em] sm:text-5xl">Mulai dari data. Bergerak dengan keyakinan.</h2>
            </div>
            <x-ui.button href="/dashboard" size="lg" variant="secondary">Lihat Dashboard</x-ui.button>
        </div>
    </section>
</main>

<footer class="px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-2 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
        <p class="font-mono font-bold tracking-[0.12em] text-foreground">ROBLOX.TRENDS</p>
        <p>Data from Roblox discover &amp; games API</p>
    </div>
</footer>
</body>
</html>
