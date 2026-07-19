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
                // Preserve the browser default when storage or media preferences are unavailable.
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="landing" class="min-h-screen overflow-x-hidden bg-background text-foreground antialiased">
@php
    $rankingItems = is_array($ranking['ranking'] ?? null) ? $ranking['ranking'] : [];
    $topGenres = array_slice($rankingItems, 0, 4);
    $leader = $rankingItems[0] ?? null;
    $snapshotId = $snapshot['snapshot_id'] ?? null;
    $snapshotTime = $snapshot['taken_at'] ?? null;
    $chartItems = array_map(function ($genre) {
        return [
            'genreL1' => $genre['genreL1'] ?? 'Belum terbaca',
            'game_count' => (int) ($genre['game_count'] ?? 0),
        ];
    }, array_slice($rankingItems, 0, 5));
@endphp

<header class="landing-header">
    <nav aria-label="Navigasi utama" class="landing-nav">
        <a href="/" aria-label="Analisis Trend Roblox — beranda" class="landing-brand">
            <span class="landing-brand-tile" aria-hidden="true">R/</span>
            <span>RblxLab</span>
        </a>
        <div class="landing-nav-links">
            <a href="#analisis">Analisis</a>
            <a href="#metode">Metode</a>
            <a href="#catatan">Catatan</a>
        </div>
        <div class="landing-nav-actions">
            <x-analytics.theme-toggle />
            <x-ui.button href="/dashboard" variant="outline" size="sm">Buka Dashboard</x-ui.button>
        </div>
    </nav>
</header>

<main>
    <section class="landing-hero" aria-labelledby="landing-title">
        <div class="landing-hero-copy">
            <p class="landing-eyebrow">RblxLab / observatorium pasar</p>
            <h1 id="landing-title" aria-label="Kami membaca bagaimana tren menjadi peluang.">
                Kami membaca bagaimana<br>tren menjadi peluang.
            </h1>
            <p class="landing-hero-description">Studio riset independen untuk membaca permainan, genre, dan ruang tumbuh di Roblox sebelum semuanya menjadi ramai.</p>
            <div class="landing-hero-actions">
                <x-ui.button href="#sinyal" size="lg">Lihat sinyal</x-ui.button>
                <span>Mulai dengan pertanyaan yang tepat.</span>
            </div>
        </div>
        <x-analytics.signal-stream />
    </section>

    <section class="landing-manifesto" aria-labelledby="manifesto-title">
        <div>
            <p class="landing-eyebrow">Manifesto</p>
            <h2 id="manifesto-title">Data adalah sebuah praktik.</h2>
            <p>Kami mengumpulkan, mempertanyakan, lalu menghubungkan sinyal yang biasanya tersembunyi di balik angka.</p>
        </div>
        <x-analytics.paper-planes />
    </section>

    <section id="analisis" class="landing-research" aria-labelledby="analisis-title">
        <div class="landing-section-heading">
            <p class="landing-eyebrow">Riset yang bisa ditindaklanjuti</p>
            <h2 id="analisis-title">Bukan sekadar melihat pasar.</h2>
        </div>
        <div class="landing-research-list">
            <article class="landing-research-item">
                <p>01</p>
                <h3>Ranking</h3>
                <p>Petakan genre berdasarkan jumlah game agar besarnya pasar terlihat tanpa kabut.</p>
                <a href="/dashboard">Baca ranking <span aria-hidden="true">↗</span></a>
            </article>
            <article class="landing-research-item">
                <p>02</p>
                <h3>Saturasi</h3>
                <p>Temukan kategori yang mulai padat sebelum ide yang baik habis berebut perhatian.</p>
                <a href="/dashboard/saturasi">Baca saturasi <span aria-hidden="true">↗</span></a>
            </article>
            <article class="landing-research-item">
                <p>03</p>
                <h3>Momentum</h3>
                <p>Amati game muda yang bergerak cepat untuk menangkap pola sebelum menjadi kebiasaan.</p>
                <a href="/dashboard/viral">Baca momentum <span aria-hidden="true">↗</span></a>
            </article>
        </div>
    </section>

    <section id="sinyal" class="landing-signals" aria-labelledby="sinyal-title">
        <div class="landing-section-heading">
            <p class="landing-eyebrow">Snapshot saat ini</p>
            <h2 id="sinyal-title">Sinyal untuk memulai.</h2>
        </div>
        @if ($status === 'ok' && $snapshot)
            <div class="landing-signal-grid">
                <article class="landing-signal-card landing-signal-card-atlas">
                    <p>Atlas</p>
                    <h3>{{ number_format((int) ($snapshot['game_count'] ?? 0), 0, ',', '.') }} game</h3>
                    <p>Skala semesta yang sedang kami baca.</p>
                    <div id="minichart" class="landing-minichart"></div>
                </article>
                <article class="landing-signal-card">
                    <p>Lensa</p>
                    <h3>{{ count($rankingItems) }} genre</h3>
                    <p>Sudut pasar yang sudah terpetakan.</p>
                </article>
                <article class="landing-signal-card">
                    <p>Jejak</p>
                    @if ($leader && isset($leader['genreL1'], $leader['game_count']))
                        <h3>{{ $leader['genreL1'] }}</h3>
                        <p>{{ number_format((int) $leader['game_count'], 0, ',', '.') }} game pada genre terdepan.</p>
                    @else
                        <h3>Belum terbaca</h3>
                        <p>Genre terdepan menunggu data yang lengkap.</p>
                    @endif
                </article>
                <article class="landing-signal-card">
                    <p>Muse</p>
                    <h3>#{{ $snapshotId ?? '—' }}</h3>
                    <p>{{ $snapshotTime ?? 'Waktu snapshot belum tersedia.' }}</p>
                </article>
            </div>

            @if (count($rankingItems))
                <div class="landing-ranking-panel">
                    <div>
                        <p class="landing-eyebrow">Lima genre terdepan</p>
                        <h3>Komposisi pasar saat ini.</h3>
                    </div>
                    <ol data-ui="genre-ranking-list" aria-label="Top 5 genre berdasarkan jumlah game">
                        @foreach (array_slice($rankingItems, 0, 5) as $genre)
                            <li>{{ $loop->iteration }}. {{ $genre['genreL1'] ?? 'Belum terbaca' }}: {{ number_format((int) ($genre['game_count'] ?? 0), 0, ',', '.') }} game</li>
                        @endforeach
                    </ol>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const top5 = @json($chartItems);
                        registerChart(document.querySelector('#minichart'), {
                            chart: { type: 'bar', height: 220, toolbar: { show: false }, animations: { enabled: false } },
                            plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
                            series: [{ name: 'Jumlah game', data: top5.map((item) => item.game_count) }],
                            xaxis: { categories: top5.map((item) => item.genreL1) },
                            dataLabels: { enabled: false },
                        });
                    });
                </script>
            @else
                <x-ui.alert tone="neutral">
                    <x-lucide-chart-no-axes-column />
                    <x-ui.alert-title>Belum terbaca</x-ui.alert-title>
                    <x-ui.alert-description>Ranking genre belum tersedia untuk snapshot ini.</x-ui.alert-description>
                </x-ui.alert>
            @endif
        @else
            <x-analytics.status-panel :status="$status" />
        @endif
    </section>

    <section id="metode" class="landing-process" aria-labelledby="metode-title">
        <div class="landing-section-heading">
            <p class="landing-eyebrow">Cara kerja</p>
            <h2 id="metode-title">Data yang bisa diajak berdialog.</h2>
        </div>
        <ol class="landing-process-list">
            <li><span>01</span><strong>Temukan</strong><p>Mengumpulkan jejak pasar yang relevan.</p></li>
            <li><span>02</span><strong>Bersihkan</strong><p>Menata data agar perbandingan berarti.</p></li>
            <li><span>03</span><strong>Uji</strong><p>Menguji asumsi dari beberapa sudut.</p></li>
            <li><span>04</span><strong>Tampilkan</strong><p>Membuat sinyal mudah dipakai.</p></li>
        </ol>
    </section>

    <section class="landing-coral-cta" aria-labelledby="cta-title">
        <div>
            <p class="landing-eyebrow">Dari sinyal ke tindakan</p>
            <h2 id="cta-title">Buka pola, bukan hanya halaman.</h2>
            <x-ui.button href="/dashboard" size="lg">Buka Dashboard</x-ui.button>
        </div>
        <x-analytics.target-rings />
    </section>

    <section id="catatan" class="landing-notes" aria-labelledby="catatan-title">
        <div class="landing-section-heading">
            <p class="landing-eyebrow">Catatan lapangan</p>
            <h2 id="catatan-title">Genre yang layak ditanya lagi.</h2>
        </div>
        @if (count($topGenres))
            <div class="landing-note-grid">
                @foreach ($topGenres as $genre)
                    <article class="landing-note landing-note-{{ $loop->iteration }}">
                        <p>Catatan {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                        <h3>{{ $genre['genreL1'] ?? 'Belum terbaca' }}</h3>
                        <p>{{ number_format((int) ($genre['game_count'] ?? 0), 0, ',', '.') }} game tercatat dalam snapshot ini.</p>
                        <a href="/dashboard">Telusuri di dashboard <span aria-hidden="true">↗</span></a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="landing-closing" aria-labelledby="closing-title">
        <span class="landing-brand-tile" aria-hidden="true">R/</span>
        <div>
            <h2 id="closing-title">Bawa kami sebuah pertanyaan sulit.</h2>
            <p>Kami akan mulai dari data yang tersedia, lalu mencari apa yang belum terbaca.</p>
        </div>
        <x-ui.button href="/dashboard" variant="outline" size="lg">Buka Dashboard</x-ui.button>
    </section>
</main>

<footer class="landing-footer">
    <p>RblxLab</p>
    <p>Data dari Roblox Discover &amp; Games API.</p>
</footer>
</body>
</html>
