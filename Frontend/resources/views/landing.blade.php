<!DOCTYPE html>
<html lang="id" class="bg-background">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script>
        (() => {
            try {
                const mode = localStorage.getItem('theme:mode') || 'system';
                const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            } catch {
              
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="landing" class="min-h-screen overflow-x-hidden bg-background text-foreground antialiased">
@php
    $rankingItems = is_array($ranking['ranking'] ?? null) ? $ranking['ranking'] : [];
    $previewItems = array_slice($rankingItems, 0, 3);
    $previewMaximum = max(array_map(static fn ($item) => (int) ($item['game_count'] ?? 0), $previewItems) ?: [1]);
    $topGenre = $rankingItems[0]['genreL1'] ?? null;
@endphp

<header class="sticky top-0 z-40 h-14 border-b border-border/70 bg-background/85 backdrop-blur-xl">
    <nav aria-label="Navigasi utama" class="saas-container flex h-full items-center justify-between gap-3">
        <x-analytics.brand-mark class="min-h-10 min-w-10 justify-center [&>span:last-child]:hidden sm:[&>span:last-child]:block" />

        <div class="hidden items-center gap-1 md:flex">
            <a class="inline-flex min-h-10 items-center rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" href="#features">Fitur</a>
            <a class="inline-flex min-h-10 items-center rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" href="#live-data">Live data</a>
            <a class="inline-flex min-h-10 items-center rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring" href="#methodology">Metodologi</a>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.button href="/dashboard" size="lg" variant="outline" class="px-3 sm:px-4">Dashboard</x-ui.button>
            <x-analytics.theme-toggle />
        </div>
    </nav>
</header>

<main>
    <section data-section="hero" aria-labelledby="hero-heading" class="border-b border-border/70 py-16 sm:py-20 lg:py-24">
        <div class="saas-container grid gap-12 lg:grid-cols-[minmax(0,0.88fr)_minmax(28rem,1.12fr)] lg:items-center lg:gap-16">
            <div class="min-w-0">
                <p data-hero-item class="saas-label">Roblox market intelligence</p>
                <h1 data-hero-item id="hero-heading" class="landing-display mt-5 max-w-[12ch]">See the market before it moves</h1>
                <p data-hero-item class="mt-6 max-w-xl text-base leading-7 text-muted-foreground sm:text-lg sm:leading-8">
                    Ubah data pasar Roblox menjadi sinyal yang terbaca—genre yang menguat, ruang yang mulai jenuh, dan momentum awal yang layak diperhatikan.
                </p>
                <div data-hero-item class="mt-8 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    <x-ui.button href="/dashboard" size="lg">
                        Lihat Dashboard
                        <x-slot:after><x-lucide-arrow-right aria-hidden="true" /></x-slot:after>
                    </x-ui.button>
                    <p class="font-mono text-[11px] leading-5 tracking-[0.05em] text-muted-foreground">
                        @if ($status === 'ok' && $snapshot)
                            Snapshot #{{ $snapshot['snapshot_id'] ?? '—' }} · {{ number_format((int) ($snapshot['game_count'] ?? 0), 0, ',', '.') }} game terbaca
                        @else
                            Data discovery dengan fallback yang transparan
                        @endif
                    </p>
                </div>
            </div>

            <div data-hero-item data-ui="product-preview" class="saas-panel min-w-0 overflow-hidden shadow-[0_24px_80px_-44px_var(--foreground)]">
                <div class="flex min-h-11 items-center justify-between gap-3 border-b border-border bg-muted/35 px-4">
                    <div class="flex items-center gap-3">
                        <span aria-hidden="true" class="size-2 rounded-full bg-muted-foreground/45"></span>
                        <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Market overview</p>
                    </div>
                    <span class="rounded-full border border-border bg-background px-2 py-1 font-mono text-[9px] uppercase tracking-[0.08em] text-muted-foreground">
                        {{ $status === 'ok' ? 'Live snapshot' : 'Standby' }}
                    </span>
                </div>

                <div class="p-4 sm:p-5">
                    <p class="sr-only">
                        Ringkasan produk:
                        @if ($snapshot)
                            {{ number_format((int) ($snapshot['game_count'] ?? 0), 0, ',', '.') }} game dianalisis, {{ count($rankingItems) }} genre terpetakan, dan genre teratas {{ $topGenre ?? 'belum terbaca' }}.
                        @else
                            snapshot pasar belum tersedia.
                        @endif
                    </p>

                    <div class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-border bg-border sm:grid-cols-3">
                        <div class="min-w-0 bg-card p-3">
                            <p class="saas-label">Games</p>
                            <p class="mt-3 truncate text-xl font-semibold tracking-[-0.04em]">{{ $snapshot ? number_format((int) ($snapshot['game_count'] ?? 0), 0, ',', '.') : '—' }}</p>
                        </div>
                        <div class="min-w-0 bg-card p-3">
                            <p class="saas-label">Genres</p>
                            <p class="mt-3 truncate text-xl font-semibold tracking-[-0.04em]">{{ count($rankingItems) ?: '—' }}</p>
                        </div>
                        <div class="col-span-2 min-w-0 bg-card p-3 sm:col-span-1">
                            <p class="saas-label">Top signal</p>
                            <p class="mt-3 truncate text-sm font-semibold">{{ $topGenre ?? 'Belum terbaca' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-border bg-background/45 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="saas-label">Genre composition</p>
                                <p class="mt-2 text-sm font-medium">Jumlah game per sinyal</p>
                            </div>
                            <span class="font-mono text-[10px] text-muted-foreground">TOP 03</span>
                        </div>

                        @if (count($previewItems))
                            <div class="mt-5 space-y-4">
                                @foreach ($previewItems as $genre)
                                    @php($barWidth = max(12, (int) round(((int) ($genre['game_count'] ?? 0) / max($previewMaximum, 1)) * 100)))
                                    <div>
                                        <div class="flex items-center justify-between gap-3 text-xs">
                                            <span class="truncate font-medium">{{ $genre['genreL1'] ?? 'Tanpa genre' }}</span>
                                            <span class="shrink-0 font-mono text-[10px] text-muted-foreground">{{ number_format((int) ($genre['game_count'] ?? 0), 0, ',', '.') }}</span>
                                        </div>
                                        <div aria-hidden="true" class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                                            <div class="h-full rounded-full bg-foreground/75" style="width: {{ $barWidth }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="mt-5 rounded-md border border-dashed border-border px-4 py-7 text-center">
                                <p class="text-sm font-medium">Belum terbaca</p>
                                <p class="mt-1 text-xs leading-5 text-muted-foreground">Sinyal genre akan muncul saat ranking tersedia.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section data-section="proof" aria-label="Bukti data pasar" class="border-b border-border/70 bg-muted/20">
        <dl class="saas-container grid divide-y divide-border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="py-5 sm:px-6 sm:first:pl-0">
                <dt class="saas-label">Market coverage</dt>
                <dd class="mt-2 text-sm font-medium">{{ $snapshot ? number_format((int) ($snapshot['game_count'] ?? 0), 0, ',', '.') . ' game dianalisis' : 'Menunggu snapshot' }}</dd>
            </div>
            <div class="py-5 sm:px-6">
                <dt class="saas-label">Genre signals</dt>
                <dd class="mt-2 text-sm font-medium">{{ count($rankingItems) ? count($rankingItems) . ' genre terpetakan' : 'Ranking belum tersedia' }}</dd>
            </div>
            <div class="py-5 sm:pl-6">
                <dt class="saas-label">Provenance</dt>
                <dd class="mt-2 text-sm font-medium">{{ $status === 'ok' && $snapshot ? 'Snapshot API #' . ($snapshot['snapshot_id'] ?? '—') : 'Status sumber ditampilkan apa adanya' }}</dd>
            </div>
        </dl>
    </section>

    <section data-section="features" id="features" aria-labelledby="features-heading" class="scroll-mt-20 py-20 lg:py-28">
        <div class="saas-container">
            <div data-reveal class="max-w-2xl">
                <p class="saas-label">Decision workspace</p>
                <h2 id="features-heading" class="mt-4 text-3xl font-semibold tracking-[-0.045em] sm:text-4xl">Tiga lensa untuk membaca pasar.</h2>
                <p class="mt-4 max-w-xl leading-7 text-muted-foreground">Dari komposisi pasar sampai pergerakan awal, setiap tampilan dirancang untuk memperjelas apa yang layak ditelusuri berikutnya.</p>
            </div>

            <div data-reveal class="mt-10 grid gap-4 lg:grid-cols-2">
                <article class="saas-panel min-h-72 p-6 sm:p-8 lg:row-span-2 lg:min-h-full">
                    <div class="flex size-10 items-center justify-center rounded-lg border border-border bg-muted/40 text-muted-foreground">
                        <x-lucide-chart-no-axes-column-increasing aria-hidden="true" class="size-5" />
                    </div>
                    <p class="saas-label mt-8">01 / Composition</p>
                    <h3 class="mt-3 text-xl font-semibold tracking-[-0.025em]">Market composition &amp; ranking</h3>
                    <p class="mt-3 max-w-md text-sm leading-6 text-muted-foreground">Bandingkan jumlah game, aktivitas, kunjungan, dan rating rata-rata untuk melihat genre yang membentuk pasar Roblox.</p>
                    <div aria-hidden="true" class="mt-10 grid grid-cols-5 items-end gap-2 border-b border-border pb-px">
                        <span class="h-12 rounded-t-sm bg-muted"></span>
                        <span class="h-20 rounded-t-sm bg-muted-foreground/25"></span>
                        <span class="h-28 rounded-t-sm bg-foreground/70"></span>
                        <span class="h-16 rounded-t-sm bg-muted-foreground/30"></span>
                        <span class="h-24 rounded-t-sm bg-muted-foreground/45"></span>
                    </div>
                </article>

                <article class="saas-panel p-6 sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40 text-muted-foreground">
                            <x-lucide-scan-search aria-hidden="true" class="size-5" />
                        </div>
                        <div>
                            <p class="saas-label">02 / Saturation</p>
                            <h3 class="mt-3 text-xl font-semibold tracking-[-0.025em]">Saturation intelligence</h3>
                            <p class="mt-3 text-sm leading-6 text-muted-foreground">Petakan ruang yang padat dan konteks persaingannya sebelum memilih kategori untuk eksplorasi lebih jauh.</p>
                        </div>
                    </div>
                </article>

                <article class="saas-panel p-6 sm:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40 text-muted-foreground">
                            <x-lucide-activity aria-hidden="true" class="size-5" />
                        </div>
                        <div>
                            <p class="saas-label">03 / Momentum</p>
                            <h3 class="mt-3 text-xl font-semibold tracking-[-0.025em]">Early momentum</h3>
                            <p class="mt-3 text-sm leading-6 text-muted-foreground">Temukan game muda dengan traksi awal untuk membangun daftar pantau, bukan klaim keberhasilan yang belum terbukti.</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section data-section="live-data" id="live-data" aria-labelledby="live-data-heading" class="scroll-mt-20 border-y border-border/70 bg-muted/20 py-20 lg:py-28">
        <div class="saas-container">
            <div data-reveal class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl">
                    <p class="saas-label">Live market pulse</p>
                    <h2 id="live-data-heading" class="mt-4 text-3xl font-semibold tracking-[-0.045em] sm:text-4xl">Snapshot terbaru, tanpa lapisan cerita.</h2>
                </div>
                <p class="max-w-md text-sm leading-6 text-muted-foreground">Angka di bawah berasal dari respons data yang sama dengan dashboard.</p>
            </div>

            @if ($status === 'ok' && $snapshot)
                <div data-reveal class="mt-10 grid gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3">
                    <div class="bg-card p-5 sm:p-6">
                        <p class="saas-label">Game dianalisis</p>
                        <p class="mt-4 text-3xl font-semibold tracking-[-0.05em]">{{ number_format((int) ($snapshot['game_count'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-card p-5 sm:p-6">
                        <p class="saas-label">Genre terpetakan</p>
                        <p class="mt-4 text-3xl font-semibold tracking-[-0.05em]">{{ count($rankingItems) }}</p>
                    </div>
                    <div class="bg-card p-5 sm:p-6">
                        <p class="saas-label">Genre teratas</p>
                        <p class="mt-4 truncate text-xl font-semibold tracking-[-0.035em]">{{ $topGenre ?? 'Belum terbaca' }}</p>
                    </div>
                </div>

                <div data-reveal class="mt-4">
                    @if (count($rankingItems))
                        <div class="saas-panel overflow-hidden">
                            <div class="border-b border-border px-5 py-5 sm:px-6">
                                <p class="saas-label">Genre ranking</p>
                                <h3 id="genre-ranking-heading" class="mt-2 text-lg font-semibold tracking-[-0.025em]">Top lima berdasarkan jumlah game</h3>
                            </div>
                            <div id="minichart" aria-hidden="true" class="min-h-72 px-2 py-4 sm:px-6"></div>
                            <ol data-ui="genre-ranking-list" class="sr-only" aria-label="Top 5 genre berdasarkan jumlah game">
                                @foreach (array_slice($rankingItems, 0, 5) as $genre)
                                    <li>{{ $loop->iteration }}. {{ $genre['genreL1'] }}: {{ number_format((int) $genre['game_count'], 0, ',', '.') }} game</li>
                                @endforeach
                            </ol>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const top5 = @json(array_slice($rankingItems, 0, 5));
                                registerChart(document.querySelector('#minichart'), {
                                    chart: { type: 'bar', height: 288, toolbar: { show: false } },
                                    plotOptions: { bar: { horizontal: true, borderRadius: 2, barHeight: '52%' } },
                                    series: [{ name: 'Jumlah game', data: top5.map((item) => item.game_count) }],
                                    xaxis: { categories: top5.map((item) => item.genreL1) },
                                    dataLabels: { enabled: false },
                                    legend: { show: false },
                                });
                            });
                        </script>
                    @else
                        <x-ui.alert tone="neutral">
                            <x-lucide-chart-no-axes-column aria-hidden="true" />
                            <x-ui.alert-title>Belum terbaca</x-ui.alert-title>
                            <x-ui.alert-description>Ranking genre belum tersedia untuk snapshot ini.</x-ui.alert-description>
                        </x-ui.alert>
                    @endif
                </div>
            @else
                <div data-reveal class="mt-10 max-w-3xl">
                    <x-analytics.status-panel :status="$status" />
                </div>
            @endif
        </div>
    </section>

    <section data-section="methodology" id="methodology" aria-labelledby="methodology-heading" class="scroll-mt-20 py-20 lg:py-28">
        <div class="saas-container grid gap-10 lg:grid-cols-[0.72fr_1.28fr] lg:gap-16">
            <div data-reveal>
                <p class="saas-label">Methodology</p>
                <h2 id="methodology-heading" class="mt-4 text-3xl font-semibold tracking-[-0.045em] sm:text-4xl">Dari sumber ke keputusan.</h2>
                <p class="mt-4 max-w-md leading-7 text-muted-foreground">Proses ringkas yang menjaga sinyal tetap dapat ditelusuri tanpa mengarang presisi yang tidak tersedia.</p>
            </div>

            <ol data-reveal class="divide-y divide-border border-y border-border">
                <li class="grid gap-3 py-6 sm:grid-cols-[5rem_9rem_1fr] sm:items-start">
                    <span class="font-mono text-xs text-muted-foreground">01</span>
                    <h3 class="text-sm font-semibold">Collect</h3>
                    <p class="text-sm leading-6 text-muted-foreground">Ambil snapshot dari sumber Roblox discover dan games API melalui layanan analisis.</p>
                </li>
                <li class="grid gap-3 py-6 sm:grid-cols-[5rem_9rem_1fr] sm:items-start">
                    <span class="font-mono text-xs text-muted-foreground">02</span>
                    <h3 class="text-sm font-semibold">Structure</h3>
                    <p class="text-sm leading-6 text-muted-foreground">Susun atribut game menjadi ranking genre, konteks saturasi, dan indikator momentum yang konsisten.</p>
                </li>
                <li class="grid gap-3 py-6 sm:grid-cols-[5rem_9rem_1fr] sm:items-start">
                    <span class="font-mono text-xs text-muted-foreground">03</span>
                    <h3 class="text-sm font-semibold">Read</h3>
                    <p class="text-sm leading-6 text-muted-foreground">Bawa sinyal ke workspace untuk dibandingkan, ditelusuri, dan dijadikan titik awal keputusan.</p>
                </li>
            </ol>
        </div>
    </section>

    <section data-section="final-cta" aria-labelledby="final-cta-heading" class="pb-8 sm:pb-12">
        <div data-reveal class="saas-container overflow-hidden rounded-2xl bg-foreground px-6 py-12 text-background sm:px-10 sm:py-14 lg:flex lg:items-end lg:justify-between lg:gap-12 lg:px-14">
            <div class="max-w-3xl">
                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.08em] text-background/60">Your next market read</p>
                <h2 id="final-cta-heading" class="mt-4 text-3xl font-semibold tracking-[-0.045em] sm:text-4xl">Baca sinyalnya. Tentukan langkah berikutnya.</h2>
                <p class="mt-4 max-w-xl text-sm leading-6 text-background/65">Masuk ke dashboard untuk menelusuri ranking, saturasi, dan momentum dari snapshot pasar terbaru.</p>
            </div>
            <x-ui.button href="/dashboard" size="lg" variant="secondary" class="mt-8 lg:mt-0">
                Buka Dashboard
                <x-slot:after><x-lucide-arrow-right aria-hidden="true" /></x-slot:after>
            </x-ui.button>
        </div>
    </section>
</main>

<footer class="border-t border-border/70 py-8">
    <div class="saas-container flex flex-col gap-5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-semibold text-foreground">Roblox Signals</p>
            <p class="mt-1">Market intelligence untuk eksplorasi, bukan jaminan hasil.</p>
        </div>
        <div class="flex flex-col gap-3 sm:items-end">
            <p>Data from Roblox discover &amp; games API</p>
            <a href="/dashboard" class="inline-flex min-h-10 items-center self-start rounded-md font-medium text-foreground underline decoration-border underline-offset-4 hover:decoration-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:self-end">Lihat Dashboard</a>
        </div>
    </div>
</footer>
</body>
</html>
