# Roblox Signal Lab Landing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the Laravel landing page as the approved high-fidelity Roblox Signal Lab editorial poster while preserving live data, API fallbacks, theme switching, accessibility, and dashboard isolation.

**Architecture:** Keep the existing `FastApiClient -> DashboardController::landing() -> Blade` flow. The landing view owns semantic content and data mapping, three small Blade components own decorative SVG/CSS artwork, and all new styling is scoped below `[data-page='landing']` in `app.css` so dashboard pages remain unchanged.

**Tech Stack:** Laravel 13, Blade, Tailwind CSS 4, BlatUI, Alpine theme store, ApexCharts through `registerChart()`, PHPUnit feature tests, Vite.

---

## File map

- Create `Frontend/resources/views/components/analytics/signal-stream.blade.php`: five-lane hero SVG and dot nodes; decorative only.
- Create `Frontend/resources/views/components/analytics/paper-planes.blade.php`: manifesto flight paths and four paper planes; decorative only.
- Create `Frontend/resources/views/components/analytics/target-rings.blade.php`: concentric CTA rings and central label; decorative only.
- Modify `Frontend/resources/views/landing.blade.php`: Indonesian content, all landing sections, live-data mapping, fallbacks, chart registration, and semantic landmarks.
- Modify `Frontend/resources/css/app.css`: landing-only tokens, layout, motifs, dark mode, focus, mobile breakpoints, and reduced motion.
- Modify `Frontend/tests/Feature/LandingTest.php`: replace the old ASCII/editorial contract with the approved Signal Lab contract while retaining data and failure-state coverage.

### Task 1: Lock the new landing contract with failing feature tests

**Files:**
- Modify: `Frontend/tests/Feature/LandingTest.php`
- Test: `Frontend/tests/Feature/LandingTest.php`

- [ ] **Step 1: Replace old hero and ASCII assertions with the approved structural contract**

Keep the existing HTTP fakes, status assertions, live values, empty state, and failure-state checks. In `test_landing_shows_hero_and_stats()`, replace the old visual assertions with these exact assertions:

```php
$response->assertStatus(200)
    ->assertSee('Analisis Trend Roblox')
    ->assertSee('Buka Dashboard')
    ->assertSee('781')
    ->assertSee('Kami membaca bagaimana tren menjadi peluang.')
    ->assertSee('Data adalah sebuah praktik.')
    ->assertSee('Ranking')
    ->assertSee('Saturasi')
    ->assertSee('Momentum')
    ->assertSee('Simulation')
    ->assertSee('data-page="landing"', false)
    ->assertSee('id="analisis"', false)
    ->assertSee('id="sinyal"', false)
    ->assertSee('id="metode"', false)
    ->assertSee('id="catatan"', false)
    ->assertSee('data-ui="signal-stream"', false)
    ->assertSee('data-ui="paper-planes"', false)
    ->assertSee('data-ui="target-rings"', false)
    ->assertSee('data-ui="genre-ranking-list"', false)
    ->assertSee('try {', false);

$this->assertSame(1, substr_count($response->getContent(), 'href="/"'));
$this->assertSame(0, substr_count($response->getContent(), 'data-ui="ascii-field"'));
```

Update the remaining three test methods to assert the Indonesian headline instead of `Decode what Roblox plays.`. Preserve their current status-panel and `Belum terbaca` assertions unchanged.

- [ ] **Step 2: Run the focused test and verify it fails for the new contract**

Run:

```powershell
php artisan test tests/Feature/LandingTest.php
```

Expected: FAIL because the old page does not contain `Kami membaca bagaimana tren menjadi peluang.` or the three new `data-ui` artwork markers.

### Task 2: Add isolated decorative artwork components

**Files:**
- Create: `Frontend/resources/views/components/analytics/signal-stream.blade.php`
- Create: `Frontend/resources/views/components/analytics/paper-planes.blade.php`
- Create: `Frontend/resources/views/components/analytics/target-rings.blade.php`
- Test: `Frontend/tests/Feature/LandingTest.php`

- [ ] **Step 1: Create the signal-stream component**

Use a single semantic-free SVG with the marker expected by the feature test:

```blade
<svg data-ui="signal-stream" aria-hidden="true" {{ $attributes->twMerge('landing-signal-stream') }} viewBox="0 0 650 430" fill="none" focusable="false">
    <path class="landing-stream-line landing-stream-blue" d="M20 432C52 341 123 303 236 259C330 223 358 185 373 116C381 79 404 59 444 59H550" />
    <path class="landing-stream-line landing-stream-mint" d="M45 435C75 352 139 319 250 277C350 239 381 196 391 137C397 100 420 81 461 81H560" />
    <path class="landing-stream-line landing-stream-amber" d="M72 438C101 365 155 337 269 295C368 258 400 216 409 158C415 123 438 104 477 104H570" />
    <path class="landing-stream-line landing-stream-coral" d="M99 441C126 379 175 354 286 313C386 276 421 236 428 180C433 146 455 128 493 128H580" />
    <path class="landing-stream-line landing-stream-pink" d="M126 444C151 394 194 372 304 332C405 295 440 257 446 202C450 169 472 153 509 153H590" />
    <g class="landing-stream-nodes landing-stream-nodes-blue"><circle cx="565" cy="59" r="6"/><circle cx="584" cy="59" r="7"/><circle cx="605" cy="59" r="8"/><circle cx="629" cy="59" r="10"/></g>
    <g class="landing-stream-nodes landing-stream-nodes-mint"><circle cx="575" cy="81" r="5"/><circle cx="593" cy="81" r="6"/><circle cx="613" cy="81" r="7"/><circle cx="635" cy="81" r="9"/></g>
    <g class="landing-stream-nodes landing-stream-nodes-amber"><circle cx="584" cy="104" r="5"/><circle cx="601" cy="104" r="6"/><circle cx="620" cy="104" r="7"/><circle cx="641" cy="104" r="8"/></g>
</svg>
```

- [ ] **Step 2: Create the paper-planes component**

Use one wrapper with a dashed route SVG and four plane SVGs. Every plane uses a distinct landing color and remains hidden from assistive technology:

```blade
<div data-ui="paper-planes" aria-hidden="true" {{ $attributes->twMerge('landing-plane-map') }}>
    <svg class="landing-flight-path" viewBox="0 0 500 270" fill="none" focusable="false">
        <path d="M4 240C85 183 57 130 142 136C218 141 170 54 253 53C347 51 338 201 484 184" />
    </svg>
    @foreach (['blue', 'coral', 'mint', 'pink'] as $tone)
        <svg class="landing-plane landing-plane-{{ $tone }} landing-plane-{{ $loop->iteration }}" viewBox="0 0 120 90" focusable="false">
            <path class="landing-plane-main" d="M2 34L116 3L74 84L58 52L2 34Z" />
            <path class="landing-plane-fold" d="M58 52L116 3L71 62Z" />
            <path class="landing-plane-tail" d="M58 52L46 74L71 62Z" />
        </svg>
    @endforeach
</div>
```

- [ ] **Step 3: Create the target-rings component**

```blade
<div data-ui="target-rings" aria-hidden="true" {{ $attributes->twMerge('landing-target') }}>
    <span class="landing-target-ring landing-target-ring-paper"></span>
    <span class="landing-target-ring landing-target-ring-blue"></span>
    <span class="landing-target-ring landing-target-ring-mint"></span>
    <span class="landing-target-ring landing-target-ring-pink"></span>
    <span class="landing-target-ring landing-target-ring-amber"></span>
    <span class="landing-target-label">BUKA DATA.<br>BAWA PERTANYAAN.</span>
</div>
```

- [ ] **Step 4: Run Blade component discovery smoke check**

Run:

```powershell
php artisan view:clear
php artisan test tests/Feature/LandingTest.php
```

Expected: tests still FAIL on the new headline because the components exist but are not yet rendered. There must be no `Unable to locate a class or view for component` error.

### Task 3: Rebuild the server-rendered landing structure

**Files:**
- Modify: `Frontend/resources/views/landing.blade.php`
- Test: `Frontend/tests/Feature/LandingTest.php`

- [ ] **Step 1: Preserve the theme pre-paint and define safe live-data values**

Keep the existing document head and pre-paint script. Immediately after `<body>`, define safe values without changing the controller:

```blade
@php
    $rankingItems = $ranking['ranking'] ?? [];
    $topGenres = array_slice($rankingItems, 0, 4);
    $leader = $rankingItems[0] ?? null;
    $snapshotId = $snapshot['snapshot_id'] ?? null;
    $snapshotTime = $snapshot['taken_at'] ?? null;
@endphp
```

- [ ] **Step 2: Replace the header and hero**

Use a landing-specific wordmark so the dashboard component stays untouched:

```blade
<header class="landing-header">
    <nav aria-label="Navigasi utama" class="landing-nav">
        <a href="/" class="landing-brand" aria-label="Analisis Trend Roblox — beranda">
            <span class="landing-brand-tile" aria-hidden="true">R/</span><span>RblxLab</span>
        </a>
        <div class="landing-nav-links">
            <a href="#analisis">Ranking</a><a href="#analisis">Saturasi</a><a href="#analisis">Momentum</a><a href="#metode">Tentang</a><a href="#catatan">Catatan Data</a>
        </div>
        <div class="landing-nav-actions">
            <span class="landing-theme"><x-analytics.theme-toggle /></span>
            <a href="/dashboard" class="landing-nav-cta">Buka Dashboard <span aria-hidden="true">↗</span></a>
        </div>
    </nav>
</header>

<main>
    <section class="landing-hero" aria-labelledby="landing-title">
        <h1 id="landing-title">Kami membaca<br>bagaimana tren<br>menjadi peluang.</h1>
        <div class="landing-hero-copy"><p>Studio analisis yang membaca arah baru pasar Roblox.</p><a href="#sinyal" class="landing-text-link">Jelajahi data <span aria-hidden="true">↗</span></a></div>
        <x-analytics.signal-stream />
    </section>
```

- [ ] **Step 3: Add the manifesto and three research rows**

Render the pale-sky manifesto with `<x-analytics.paper-planes />`, followed by `<section id="analisis">`. The three rows must use the exact visible labels `01 Ranking`, `02 Saturasi`, and `03 Momentum`, each with a text explanation and an `/dashboard`, `/dashboard/saturasi`, or `/dashboard/viral` link respectively.

```blade
<section class="landing-manifesto" aria-labelledby="manifesto-title">
    <div class="landing-section-kicker">Manifesto kami</div>
    <h2 id="manifesto-title">Data adalah sebuah <em>praktik.</em></h2>
    <p>Kami merawat rasa ingin tahu, menguji asumsi, dan membiarkan pola menunjukkan arahnya.</p>
    <a href="#metode" class="landing-text-link">Baca pendekatan kami <span aria-hidden="true">↗</span></a>
    <x-analytics.paper-planes />
</section>
```

- [ ] **Step 4: Add the live selected-signals section and preserve every fallback**

Create `<section id="sinyal">`. When `$status === 'ok' && $snapshot`, render four squared cards:

- Atlas: `number_format((int) $snapshot['game_count'], 0, ',', '.')` and the visible `#minichart`.
- Lensa: `count($rankingItems)`.
- Jejak: `$leader['genreL1'] ?? 'Belum terbaca'` and its `game_count`.
- Muse: `#{{ $snapshotId }}` and `$snapshotTime`.

Keep the accessible list exactly:

```blade
<ol data-ui="genre-ranking-list" class="sr-only" aria-label="Top 5 genre berdasarkan jumlah game">
    @foreach (array_slice($rankingItems, 0, 5) as $genre)
        <li>{{ $loop->iteration }}. {{ $genre['genreL1'] }}: {{ number_format((int) $genre['game_count'], 0, ',', '.') }} game</li>
    @endforeach
</ol>
```

When the API status is not OK, render `<x-analytics.status-panel :status="$status" />`. When the API is OK but ranking is empty, render the current neutral alert with `Belum terbaca` and `Ranking genre belum tersedia untuk snapshot ini.`.

- [ ] **Step 5: Keep chart creation inside the existing registry**

Only render the script when ranking items exist. Keep `registerChart()` and configure a flat horizontal plot:

```blade
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const top5 = @json(array_slice($rankingItems, 0, 5));
        registerChart(document.querySelector('#minichart'), {
            chart: { type: 'bar', height: 150, toolbar: { show: false }, sparkline: { enabled: true } },
            plotOptions: { bar: { horizontal: true, borderRadius: 0, barHeight: '58%' } },
            series: [{ name: 'Jumlah game', data: top5.map((item) => item.game_count) }],
            xaxis: { categories: top5.map((item) => item.genreL1) },
            dataLabels: { enabled: false },
            grid: { show: false },
            tooltip: { x: { formatter: (_, options) => top5[options.dataPointIndex]?.genreL1 ?? '' } },
        });
    });
</script>
```

- [ ] **Step 6: Add process, CTA, notes, closing, and footer**

Create `id="metode"` with Temukan, Bersihkan, Uji, Tampilkan; a coral CTA using `<x-analytics.target-rings />`; `id="catatan"` with up to four `$topGenres`; and the closing statement `Bawa kami sebuah pertanyaan sulit.`. Every CTA points to a real dashboard route, and the footer retains `Data dari Roblox Discover & Games API`.

For note cards, use deterministic motif classes:

```blade
@foreach ($topGenres as $genre)
    <article class="landing-note landing-note-{{ (($loop->iteration - 1) % 4) + 1 }}">
        <div class="landing-note-art" aria-hidden="true"></div>
        <p class="landing-note-meta">Peringkat {{ $loop->iteration }}</p>
        <h3>{{ $genre['genreL1'] }}</h3>
        <p>{{ number_format((int) $genre['game_count'], 0, ',', '.') }} game dalam snapshot terbaru.</p>
        <a href="/dashboard" class="landing-text-link">Baca sinyal <span aria-hidden="true">↗</span></a>
    </article>
@endforeach
```

- [ ] **Step 7: Run the focused feature tests**

Run:

```powershell
php artisan test tests/Feature/LandingTest.php
```

Expected: all 4 landing tests PASS.

- [ ] **Step 8: Commit the structural implementation**

```powershell
git add Frontend/tests/Feature/LandingTest.php Frontend/resources/views/landing.blade.php Frontend/resources/views/components/analytics/signal-stream.blade.php Frontend/resources/views/components/analytics/paper-planes.blade.php Frontend/resources/views/components/analytics/target-rings.blade.php
git commit -m "feat: rebuild landing as Roblox signal lab"
```

### Task 4: Implement high-fidelity, isolated styling

**Files:**
- Modify: `Frontend/resources/css/app.css`
- Test: `Frontend/tests/Feature/LandingTest.php`

- [ ] **Step 1: Add landing tokens and canvas rules scoped to the page**

Append tokens under the body selector, not `:root`, so dashboard tokens are unchanged:

```css
[data-page='landing'] {
    --landing-paper: #f4f0e6;
    --landing-ink: #080b19;
    --landing-blue: #1747e8;
    --landing-coral: #ff4b31;
    --landing-mint: #5acda7;
    --landing-amber: #f2a43a;
    --landing-pink: #ed55bd;
    --landing-sky: #cfe8f3;
    background-color: var(--landing-paper);
    background-image: radial-gradient(color-mix(in oklab, var(--landing-ink) 14%, transparent) .7px, transparent .7px);
    background-size: 16px 16px;
    color: var(--landing-ink);
}

.dark [data-page='landing'] {
    --landing-paper: #10121b;
    --landing-ink: #f4f0e6;
    --landing-sky: #172b3f;
}
```

- [ ] **Step 2: Recreate the approved desktop composition**

Implement the mockup's exact class families in this order: `.landing-header/.landing-nav`, `.landing-hero`, `.landing-manifesto`, `.landing-research-row`, `.landing-work-grid/.landing-work-card`, `.landing-process-grid`, `.landing-collab/.landing-target`, `.landing-notes-grid`, `.landing-closing`, and `.landing-footer`.

Required geometry:

```css
[data-page='landing'] :is(.landing-nav, .landing-hero, .landing-research, .landing-work, .landing-process, .landing-notes, .landing-closing, .landing-footer) {
    width: min(100%, 90rem);
    margin-inline: auto;
}

.landing-hero { position: relative; min-height: 42rem; padding: 1rem clamp(1rem, 4vw, 3.25rem) 0; overflow: hidden; }
.landing-hero h1 { position: relative; z-index: 2; max-width: 12ch; margin: 0; font-size: clamp(4.2rem, 9.5vw, 9.4rem); font-weight: 900; letter-spacing: -.073em; line-height: .82; }
.landing-manifesto { position: relative; min-height: 27rem; padding: clamp(2rem, 4vw, 4rem); overflow: hidden; background: var(--landing-sky); border-block: 1px solid color-mix(in oklab, var(--landing-ink) 28%, transparent); }
.landing-research-row { display: grid; grid-template-columns: 5rem 1.15fr .72fr .62fr; align-items: center; gap: 1rem; min-height: 10.5rem; border-bottom: 1px solid color-mix(in oklab, var(--landing-ink) 55%, transparent); }
.landing-work-grid, .landing-notes-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
.landing-process-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.landing-collab { display: grid; grid-template-columns: 1.05fr .95fr; min-height: 31rem; overflow: hidden; background: var(--landing-coral); }
```

All cards use squared corners, one-pixel rules, and no drop shadow. Use CSS radial gradients, repeating linear gradients, borders, masks, and pseudo-elements for wave, eye, fingerprint, arch, and note motifs; do not add image files.

- [ ] **Step 3: Style the three SVG components using landing tokens**

Set all signal paths to `stroke-width: 14`, `stroke-linecap: round`, and map tone classes to landing colors. Position `.landing-signal-stream` absolutely in the lower-right hero. Position the plane map in the right half of the manifesto. Size the five target rings from 22rem down to 6rem and color them paper, cobalt, mint, magenta, and amber.

- [ ] **Step 4: Add dark-mode, focus, and touch behavior**

All text and rules derive from landing variables. Add `:focus-visible` outlines using `--landing-blue`, ensure `.landing-nav-cta` and theme control meet a 44px minimum target, and keep coral cards on dark mode readable using near-black text where contrast is stronger.

- [ ] **Step 5: Add responsive breakpoints**

At `max-width: 64rem`, reduce headline scale and hide secondary nav anchors. At `max-width: 48rem`, stack manifesto artwork below copy, convert research rows to two columns, use two-column work/note grids, use a two-column process, and stack the coral CTA. At `max-width: 32rem`, use one-column cards and preserve no horizontal overflow.

- [ ] **Step 6: Add restrained motion and reduced-motion overrides**

Animate only the stream and planes:

```css
@keyframes landing-stream-drift { 50% { transform: translate3d(.6rem, -.3rem, 0); } }
@keyframes landing-plane-float { 50% { translate: 0 -.45rem; } }
.landing-signal-stream { animation: landing-stream-drift 9s ease-in-out infinite; }
.landing-plane { animation: landing-plane-float 7s ease-in-out infinite; }

@media (prefers-reduced-motion: reduce) {
    .landing-signal-stream,
    .landing-plane { animation: none; }
}
```

- [ ] **Step 7: Run build and focused tests**

```powershell
npm run build
php artisan test tests/Feature/LandingTest.php
```

Expected: production build succeeds and all 4 landing tests pass.

- [ ] **Step 8: Commit the visual system**

```powershell
git add Frontend/resources/css/app.css
git commit -m "style: match landing to editorial reference"
```

### Task 5: Visual QA and responsive refinement

**Files:**
- Inspect and modify from visual evidence: `Frontend/resources/views/landing.blade.php`
- Inspect and modify from visual evidence: `Frontend/resources/css/app.css`
- Test: `Frontend/tests/Feature/LandingTest.php`

- [ ] **Step 1: Start the Laravel preview with deterministic fake-data support**

Run the existing Laravel server and FastAPI server when local data is available. If FastAPI has no snapshot, verify the status-panel state first and use the PHPUnit fake responses for semantic coverage.

```powershell
php artisan serve --host=127.0.0.1 --port=8011
```

Expected: landing is available at `http://127.0.0.1:8011/`.

- [ ] **Step 2: Compare against the approved mockup at three widths**

Inspect at 1440×1000, 768×1024, and 390×844. Verify the four-line hero, curved signal lanes, pale-sky manifesto, paper planes, three research rows, four selected-signal cards, process strip, coral concentric-ring CTA, note cards, closing block, and footer.

- [ ] **Step 3: Verify interactions and states**

Toggle light/dark twice, tab through every link, verify visible focus, check Dashboard CTAs, inspect reduced motion, and confirm no content is covered or clipped beyond its intended section artwork.

- [ ] **Step 4: Apply only evidence-based polish fixes**

Adjust scoped landing CSS or semantic markup for observed overflow, collision, contrast, or focus issues. Do not change controller/API contracts or shared dashboard styles.

- [ ] **Step 5: Re-run focused tests and build after polish**

```powershell
php artisan test tests/Feature/LandingTest.php
npm run build
```

Expected: all landing tests pass and Vite build succeeds.

- [ ] **Step 6: Commit verified polish if files changed**

```powershell
git add Frontend/resources/views/landing.blade.php Frontend/resources/css/app.css Frontend/tests/Feature/LandingTest.php
git commit -m "fix: polish responsive signal lab landing"
```

### Task 6: Full regression verification

**Files:**
- Verify only; no planned product-file changes.

- [ ] **Step 1: Run the complete Laravel suite**

```powershell
php artisan test
```

Expected: 14 tests pass with zero failures.

- [ ] **Step 2: Run JavaScript reduced-motion tests**

```powershell
node --test tests/JavaScript/chart-preferences.test.mjs
```

Expected: 4 tests pass with zero failures.

- [ ] **Step 3: Run the production build**

```powershell
npm run build
```

Expected: build succeeds. Existing optional font-fallback and bundle-size warnings may remain; no new error is acceptable.

- [ ] **Step 4: Confirm scope and worktree cleanliness**

```powershell
git status --short
git diff --check HEAD~3..HEAD
git diff --name-only 87e8b7c..HEAD
```

Expected tracked product changes are limited to the landing view, landing tests, landing CSS, the three analytics artwork components, and this plan. The main worktree's pre-existing deleted documents remain untouched.
