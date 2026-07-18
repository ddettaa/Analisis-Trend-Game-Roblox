# Editorial Data Story Frontend Restyle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform the Laravel landing page and three analytics dashboards into the approved ddettaa-inspired Editorial Data Story while preserving BlatUI, routes, data contracts, fallback behavior, and theme-aware ApexCharts.

**Architecture:** Keep controllers and `FastApiClient` unchanged. Add a thin analytics presentation layer of reusable Blade components backed by published BlatUI components, extend the existing token CSS with editorial light-blue/dark-red identities, and update page views to consume the same server-rendered variables. Alpine remains the theme/motion runtime and `registerChart()` remains the sole chart constructor.

**Tech Stack:** Laravel 13, Blade, BlatUI v1.16, Alpine.js, Tailwind CSS v4, ApexCharts, PHPUnit, Vite.

---

## Global constraints

- Work only in `Frontend/`; do not modify FastAPI or analytics calculations.
- Preserve routes `/`, `/dashboard`, `/dashboard/saturasi`, `/dashboard/viral`.
- Preserve current controller-to-view variable names and escaped `{{ }}` table output.
- Preserve the existing banner phrases used by tests, including `uvicorn` and `Server analisis mengembalikan error`.
- Use published BlatUI components from `Frontend/resources/views/components/ui/`.
- Never instantiate `new ApexCharts()` in a Blade view and never add a CDN script.
- Light mode uses electric blue; dark mode uses signal red. Content is identical in both modes.
- Preserve unrelated changes in `Notebook/cleaningData.ipynb`, the older untracked plan, and `.superpowers/`.
- Commit with `git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit ...`.

## File structure

```text
Frontend/
├── resources/css/app.css                         # Editorial tokens, surfaces, type, motion
├── resources/js/app.js                           # CSS-token-aware chart registry
├── resources/views/components/analytics/
│   ├── ascii-field.blade.php                     # Decorative signal texture
│   ├── brand-mark.blade.php                      # Shared wordmark / compact mark
│   ├── dashboard-nav.blade.php                   # Desktop rail + mobile bottom nav
│   ├── metric-card.blade.php                     # BlatUI metric card wrapper
│   ├── page-heading.blade.php                    # Editorial page heading
│   ├── status-panel.blade.php                    # BlatUI API state messages
│   └── theme-toggle.blade.php                    # Accessible Alpine theme toggle
├── resources/views/layouts/app.blade.php         # Responsive dashboard shell
├── resources/views/landing.blade.php             # Expressive landing page
├── resources/views/dashboard/ringkasan.blade.php # Overview workspace
├── resources/views/dashboard/saturasi.blade.php  # Saturation workspace
├── resources/views/dashboard/viral.blade.php     # Viral workspace
└── tests/Feature/
    ├── DashboardTest.php                         # Shared shell + active page contracts
    └── LandingTest.php                           # Editorial landing + empty/API-down contracts
```

### Task 1: Editorial theme tokens and chart bridge

**Files:**
- Modify: `Frontend/resources/css/app.css`
- Modify: `Frontend/resources/js/app.js`

- [ ] **Step 1: Verify the current baseline**

Run:

```powershell
cd Frontend
npm run build
php artisan test
```

Expected: Vite exits 0 and the current 12 tests pass.

- [ ] **Step 2: Replace `resources/css/app.css` with the editorial token layer**

Use this complete structure after the existing BlatUI import:

```css
@import './blatui.css';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';

@theme {
    --font-sans: 'Arial Nova', 'Aptos', 'Helvetica Neue', ui-sans-serif, system-ui, sans-serif;
}

:root {
    --editorial-accent: oklch(0.56 0.23 262);
    --editorial-accent-strong: oklch(0.48 0.25 263);
    --editorial-accent-soft: oklch(0.93 0.04 260);
    --editorial-ink: oklch(0.15 0.01 265);
    --editorial-canvas: oklch(0.965 0.008 90);
    --background: var(--editorial-canvas);
    --foreground: var(--editorial-ink);
    --card: oklch(0.995 0.003 90);
    --card-foreground: var(--editorial-ink);
    --primary: var(--editorial-accent);
    --primary-foreground: oklch(0.99 0 0);
    --accent: var(--editorial-accent-soft);
    --accent-foreground: var(--editorial-accent-strong);
    --ring: var(--editorial-accent);
    --chart-1: var(--editorial-accent);
    --chart-2: oklch(0.69 0.16 251);
    --chart-3: oklch(0.78 0.11 240);
    --chart-4: oklch(0.62 0.13 205);
    --chart-5: oklch(0.76 0.14 80);
    --sidebar: var(--editorial-ink);
    --sidebar-foreground: oklch(0.96 0.006 90);
    --sidebar-primary: var(--editorial-accent);
    --sidebar-primary-foreground: oklch(0.99 0 0);
    --sidebar-accent: oklch(0.25 0.02 265);
    --sidebar-accent-foreground: oklch(0.99 0 0);
    --sidebar-border: oklch(1 0 0 / 14%);
    --radius: 0.45rem;
}

.dark {
    --editorial-accent: oklch(0.62 0.24 27);
    --editorial-accent-strong: oklch(0.69 0.23 27);
    --editorial-accent-soft: oklch(0.27 0.07 27);
    --editorial-ink: oklch(0.97 0.006 90);
    --editorial-canvas: oklch(0.13 0.008 265);
    --background: var(--editorial-canvas);
    --foreground: var(--editorial-ink);
    --card: oklch(0.18 0.01 265);
    --card-foreground: var(--editorial-ink);
    --primary: var(--editorial-accent);
    --primary-foreground: oklch(0.99 0 0);
    --accent: var(--editorial-accent-soft);
    --accent-foreground: oklch(0.94 0.03 25);
    --ring: var(--editorial-accent);
    --chart-1: var(--editorial-accent);
    --chart-2: oklch(0.72 0.18 40);
    --chart-3: oklch(0.78 0.13 65);
    --chart-4: oklch(0.68 0.18 10);
    --chart-5: oklch(0.8 0.1 90);
    --sidebar: oklch(0.1 0.008 265);
    --sidebar-foreground: oklch(0.97 0.006 90);
    --sidebar-primary: var(--editorial-accent);
    --sidebar-primary-foreground: oklch(0.99 0 0);
    --sidebar-accent: oklch(0.22 0.025 265);
    --sidebar-accent-foreground: oklch(0.99 0 0);
}

@layer base {
    html { scroll-behavior: smooth; }
    body { min-width: 20rem; }
    ::selection { background: var(--editorial-accent); color: white; }
}

@layer components {
    .display-title {
        font-size: clamp(3.5rem, 11vw, 9rem);
        line-height: 0.82;
        letter-spacing: -0.075em;
        font-weight: 800;
    }
    .workspace-title {
        font-size: clamp(2.75rem, 6vw, 5.75rem);
        line-height: 0.86;
        letter-spacing: -0.065em;
        font-weight: 800;
    }
    .editorial-eyebrow {
        font-size: 0.6875rem;
        line-height: 1rem;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        font-weight: 800;
        color: var(--editorial-accent);
    }
    .editorial-card {
        position: relative;
        overflow: hidden;
        border-radius: var(--radius);
        box-shadow: none;
    }
    .editorial-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto;
        height: 0.1875rem;
        background: var(--editorial-accent);
    }
    .ascii-track {
        animation: ascii-drift 18s linear infinite;
        will-change: transform;
    }
}

@keyframes ascii-drift {
    from { transform: translateX(0); }
    to { transform: translateX(-25%); }
}

@media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
```

- [ ] **Step 3: Make `registerChart()` read active CSS tokens**

Replace the theme helpers and registry in `resources/js/app.js` with:

```js
const cssToken = (name, fallback) =>
    getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;

const chartTheme = () => ({
    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    foreColor: cssToken('--muted-foreground', '#6b7280'),
    primary: cssToken('--chart-1', '#2563eb'),
    grid: cssToken('--border', '#e5e7eb'),
});

window._charts = [];
window.registerChart = (el, options) => {
    if (!el) return null;
    const tokens = chartTheme();
    const chart = new ApexCharts(el, {
        colors: options.colors || [tokens.primary],
        ...options,
        theme: { ...(options.theme || {}), mode: tokens.mode },
        chart: {
            ...(options.chart || {}),
            background: 'transparent',
            foreColor: tokens.foreColor,
            fontFamily: 'inherit',
        },
        grid: { borderColor: tokens.grid, ...(options.grid || {}) },
    });
    chart.render();
    window._charts.push(chart);
    return chart;
};

new MutationObserver(() => {
    const tokens = chartTheme();
    window._charts.forEach((chart) => chart.updateOptions({
        colors: [tokens.primary],
        theme: { mode: tokens.mode },
        chart: { foreColor: tokens.foreColor },
        grid: { borderColor: tokens.grid },
    }));
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
```

Keep the ApexCharts and `./blatui` imports exactly once.

- [ ] **Step 4: Build and run the complete suite**

Run:

```powershell
cd Frontend
npm run build
php artisan test
```

Expected: build succeeds and 12 tests pass.

- [ ] **Step 5: Commit the foundation**

```powershell
git add Frontend/resources/css/app.css Frontend/resources/js/app.js
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "feat: add editorial theme tokens and chart palette bridge"
```

### Task 2: Shared analytics components and responsive dashboard shell

**Files:**
- Create: `Frontend/resources/views/components/analytics/*.blade.php`
- Modify: `Frontend/resources/views/layouts/app.blade.php`
- Modify: `Frontend/tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add failing shell assertions**

Extend `test_ringkasan_page_ok()`:

```php
$response = $this->get('/dashboard');
$response->assertStatus(200)
    ->assertSee('Adventure')
    ->assertSee('ROBLOX.TRENDS')
    ->assertSee('data-ui="dashboard-nav"', false)
    ->assertSee('data-page="ringkasan"', false)
    ->assertSee('aria-current="page"', false);
```

Run `php artisan test --filter=test_ringkasan_page_ok` and expect failure because the new shell markers do not exist.

- [ ] **Step 2: Create the shared Blade components**

Create `components/analytics/brand-mark.blade.php`:

```blade
@props(['compact' => false])
<a href="/" aria-label="Analisis Trend Roblox — beranda" {{ $attributes->twMerge('inline-flex items-center font-black tracking-[-0.08em]') }}>
    @if ($compact)<span aria-hidden="true">R/T</span><span class="sr-only">ROBLOX.TRENDS</span>
    @else<span>ROBLOX.TRENDS</span>@endif
</a>
```

Create `components/analytics/theme-toggle.blade.php`:

```blade
<x-ui.button variant="ghost" size="icon" x-data @click="$store.theme.toggle()"
    aria-label="Ganti tema warna" title="Ganti tema warna" {{ $attributes }}>
    <x-lucide-sun class="size-4 dark:hidden" />
    <x-lucide-moon class="hidden size-4 dark:block" />
</x-ui.button>
```

Create `components/analytics/ascii-field.blade.php`:

```blade
<div data-ui="ascii-field" aria-hidden="true" {{ $attributes->twMerge('pointer-events-none overflow-hidden font-mono text-xs leading-5 tracking-[0.35em] opacity-30') }}>
    <div class="ascii-track w-max whitespace-nowrap">.:+x*#&nbsp;&nbsp;.:+x*#&nbsp;&nbsp;.:+x*#&nbsp;&nbsp;.:+x*#&nbsp;&nbsp;.:+x*#&nbsp;&nbsp;.:+x*#&nbsp;&nbsp;.:+x*#&nbsp;&nbsp;.:+x*#</div>
</div>
```

Create `components/analytics/page-heading.blade.php`:

```blade
@props(['eyebrow', 'title', 'description' => null])
<header {{ $attributes->twMerge('space-y-4') }}>
    <p class="editorial-eyebrow">{{ $eyebrow }}</p>
    <h1 class="workspace-title max-w-4xl">{{ $title }}</h1>
    @if ($description)<p class="max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">{{ $description }}</p>@endif
</header>
```

Create `components/analytics/metric-card.blade.php`:

```blade
@props(['label', 'value', 'annotation' => null])
<x-ui.card data-ui="metric-card" class="editorial-card min-h-36 p-5 sm:p-6">
    <p class="text-[0.6875rem] font-extrabold uppercase tracking-[0.16em] text-muted-foreground">{{ $label }}</p>
    <p class="mt-8 text-4xl font-black tabular-nums tracking-[-0.06em]">{{ $value }}</p>
    @if ($annotation)<p class="mt-2 text-xs text-muted-foreground">{{ $annotation }}</p>@endif
</x-ui.card>
```

Create `components/analytics/status-panel.blade.php`:

```blade
@props(['status'])
@if ($status === 'unavailable')
    <x-ui.alert tone="danger"><x-lucide-server-off /><x-ui.alert-title>Server analisis tidak aktif</x-ui.alert-title><x-ui.alert-description>Jalankan: <code>uvicorn api:app --port 8000</code> di folder Backend.</x-ui.alert-description></x-ui.alert>
@elseif ($status === 'no_data')
    <x-ui.alert tone="warning"><x-lucide-database-zap /><x-ui.alert-title>Belum ada data snapshot</x-ui.alert-title><x-ui.alert-description>Jalankan ingest dulu.</x-ui.alert-description></x-ui.alert>
@elseif ($status === 'error')
    <x-ui.alert tone="danger"><x-lucide-triangle-alert /><x-ui.alert-title>Server analisis mengembalikan error</x-ui.alert-title><x-ui.alert-description>Cek log uvicorn di folder Backend.</x-ui.alert-description></x-ui.alert>
@endif
```

Create `components/analytics/dashboard-nav.blade.php` with one shared item array rendered twice:

```blade
@php
$items = [
    ['page' => 'ringkasan', 'href' => '/dashboard', 'label' => 'Ringkasan', 'icon' => 'bar-chart-3'],
    ['page' => 'saturasi', 'href' => '/dashboard/saturasi', 'label' => 'Saturasi', 'icon' => 'gauge'],
    ['page' => 'viral', 'href' => '/dashboard/viral', 'label' => 'Viral Muda', 'icon' => 'flame'],
];
$active = request()->is('dashboard/saturasi') ? 'saturasi' : (request()->is('dashboard/viral') ? 'viral' : 'ringkasan');
@endphp
<nav data-ui="dashboard-nav" aria-label="Navigasi dashboard">
    <div class="fixed inset-y-14 left-0 z-30 hidden w-24 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:flex">
        <div class="flex justify-center py-6"><x-analytics.brand-mark compact class="text-xl text-sidebar-primary" /></div>
        <div class="flex flex-1 flex-col">
            @foreach ($items as $item)
                <a href="{{ $item['href'] }}" data-page="{{ $item['page'] }}" @if($active === $item['page']) aria-current="page" @endif
                    class="flex min-h-20 flex-col items-center justify-center gap-2 border-t border-sidebar-border px-2 text-center text-[0.625rem] font-bold uppercase tracking-wider {{ $active === $item['page'] ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground/60 hover:text-sidebar-foreground' }}">
                    <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" />{{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
    <div class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-3 border-t border-sidebar-border bg-sidebar text-sidebar-foreground lg:hidden">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}" data-page="{{ $item['page'] }}" @if($active === $item['page']) aria-current="page" @endif
                class="flex min-h-16 flex-col items-center justify-center gap-1 text-[0.625rem] font-bold uppercase tracking-wide {{ $active === $item['page'] ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground/60' }}">
                <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-4" />{{ $item['label'] }}
            </a>
        @endforeach
    </div>
</nav>
```

- [ ] **Step 3: Replace the dashboard layout**

Replace `resources/views/layouts/app.blade.php` completely:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script>
        try {
            const mode = localStorage.getItem('theme:mode');
            if (mode === 'dark' || ((!mode || mode === 'system') && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (error) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-shell="editorial-dashboard" class="min-h-screen bg-background text-foreground antialiased">
    <header class="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-border bg-background/90 px-4 backdrop-blur sm:px-6">
        <x-analytics.brand-mark class="text-sm sm:text-base" />
        <div class="flex items-center gap-2">
            <a href="/" class="hidden text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground sm:inline">Landing</a>
            <x-analytics.theme-toggle />
        </div>
    </header>
    <x-analytics.dashboard-nav />
    <main class="min-w-0 space-y-8 px-4 pb-24 pt-8 sm:px-6 lg:pl-30 lg:pr-8 lg:pb-10">
        <x-analytics.status-panel :status="$status" />
        @if ($status === 'ok' && $snapshot)
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                <x-ui.badge variant="outline">Snapshot #{{ $snapshot['snapshot_id'] }}</x-ui.badge>
                <span>{{ $snapshot['game_count'] }} game</span>
                <span aria-hidden="true">/</span>
                <span>{{ $snapshot['taken_at'] }}</span>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
```

- [ ] **Step 4: Run build and tests**

Run `npm run build && php artisan test`. Expected: build succeeds and 12 tests pass, including the new shell assertions.

- [ ] **Step 5: Commit**

```powershell
git add Frontend/resources/views/components/analytics Frontend/resources/views/layouts/app.blade.php Frontend/tests/Feature/DashboardTest.php
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "feat: add editorial BlatUI dashboard shell"
```

### Task 3: Expressive landing page

**Files:**
- Modify: `Frontend/tests/Feature/LandingTest.php`
- Modify: `Frontend/resources/views/landing.blade.php`

- [ ] **Step 1: Add landing contracts and the empty ranking test**

Add to the successful response chain:

```php
->assertSee('Decode what Roblox plays')
->assertSee('data-page="landing"', false)
->assertSee('data-ui="ascii-field"', false)
->assertSee('Simulation');
```

Add this test:

```php
public function test_landing_handles_empty_ranking(): void
{
    Http::fake([
        '*/api/snapshot' => Http::response(['snapshot_id' => 2, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 0], 200),
        '*/api/genre/ranking' => Http::response(['ranking' => [], 'share' => []], 200),
    ]);

    $this->get('/')->assertOk()
        ->assertSee('Decode what Roblox plays')
        ->assertSee('Belum terbaca');
}
```

Run `php artisan test --filter=LandingTest`. Expected: failures for the new editorial markers and copy.

- [ ] **Step 2: Rebuild `landing.blade.php`**

Replace the file completely:

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script>
        try {
            const mode = localStorage.getItem('theme:mode');
            if (mode === 'dark' || ((!mode || mode === 'system') && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
        } catch (error) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="landing" class="min-h-screen overflow-x-hidden bg-background text-foreground antialiased">
    <header class="sticky top-0 z-50 flex h-14 items-center justify-between border-b border-border bg-background/90 px-4 backdrop-blur sm:px-8">
        <x-analytics.brand-mark class="text-sm sm:text-base" />
        <div class="flex items-center gap-1 sm:gap-3">
            <x-ui.button href="/dashboard" variant="ghost" size="sm">Lihat Dashboard</x-ui.button>
            <x-analytics.theme-toggle />
        </div>
    </header>

    <main>
        <section class="flex min-h-[calc(100svh-3.5rem)] items-center px-4 py-16 sm:px-8 lg:px-12">
            <div class="mx-auto w-full max-w-7xl">
                <p class="editorial-eyebrow">Analisis Trend Roblox / Live intelligence</p>
                <h1 class="display-title mt-8 max-w-6xl">Decode what<br><span class="text-primary">Roblox plays.</span></h1>
                <div class="mt-10 flex max-w-3xl flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <p class="max-w-xl text-base leading-7 text-muted-foreground sm:text-lg">Pantau genre yang naik, ukur saturasi pasar, dan temukan game muda dengan momentum paling kuat dari snapshot Roblox.</p>
                    <x-ui.button href="/dashboard" size="lg">Lihat Dashboard <x-slot:after><x-lucide-arrow-up-right /></x-slot:after></x-ui.button>
                </div>
            </div>
        </section>

        <section class="relative overflow-hidden bg-primary px-4 py-20 text-primary-foreground sm:px-8 sm:py-28 lg:px-12">
            <x-analytics.ascii-field class="absolute inset-x-0 bottom-4" />
            <div class="relative z-10 mx-auto max-w-7xl">
                <p class="text-xs font-black uppercase tracking-[0.2em] opacity-70">Current signal</p>
                <h2 class="mt-5 max-w-5xl text-5xl font-black leading-[0.9] tracking-[-0.065em] sm:text-7xl lg:text-8xl">
                    @if ($snapshot){{ $snapshot['game_count'] }} games.<br>One market signal.@else Market signals,<br>when data is ready.@endif
                </h2>
            </div>
        </section>

        @if ($snapshot)
            <section class="mx-auto grid max-w-7xl gap-3 px-4 py-16 sm:grid-cols-3 sm:px-8 lg:px-12">
                <x-analytics.metric-card label="Game dianalisis" :value="$snapshot['game_count']" />
                <x-analytics.metric-card label="Genre terpetakan" :value="count($ranking['ranking'])" />
                <x-analytics.metric-card label="Genre teratas" :value="$ranking['ranking'][0]['genreL1'] ?? 'Belum terbaca'" />
            </section>
        @else
            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-8 lg:px-12"><x-analytics.status-panel :status="$status" /></section>
        @endif

        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-8 lg:px-12">
            <p class="editorial-eyebrow">Three views / One market</p>
            @foreach ([
                ['01', 'Ranking Genre', 'Bandingkan ukuran pasar dan pemain aktif setiap genre.'],
                ['02', 'Peta Saturasi', 'Temukan ruang yang padat, sehat, atau baru tumbuh.'],
                ['03', 'Viral Muda', 'Lihat game berumur kurang dari 90 hari dengan momentum tertinggi.'],
            ] as $insight)
                <div class="grid gap-4 border-t border-border py-8 sm:grid-cols-[5rem_1fr_1fr] sm:items-center">
                    <span class="editorial-eyebrow">{{ $insight[0] }}</span><h2 class="text-3xl font-black tracking-[-0.05em] sm:text-5xl">{{ $insight[1] }}</h2><p class="text-sm leading-6 text-muted-foreground">{{ $insight[2] }}</p>
                </div>
            @endforeach
        </section>

        <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-8 lg:px-12">
            @if (!empty($ranking['ranking']))
                <x-ui.card variant="sectioned" class="editorial-card">
                    <x-ui.card-header><p class="editorial-eyebrow">Top five / Supply</p><x-ui.card-title class="text-2xl tracking-tight">Genre berdasarkan jumlah game</x-ui.card-title></x-ui.card-header>
                    <x-ui.card-content><div id="minichart"></div></x-ui.card-content>
                </x-ui.card>
            @elseif ($status === 'ok')
                <x-ui.alert tone="neutral"><x-lucide-chart-no-axes-column /><x-ui.alert-title>Belum terbaca</x-ui.alert-title><x-ui.alert-description>Ranking genre belum memiliki data untuk divisualisasikan.</x-ui.alert-description></x-ui.alert>
            @endif
        </section>

        <section class="bg-foreground px-4 py-20 text-background sm:px-8 lg:px-12">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 sm:flex-row sm:items-end sm:justify-between">
                <h2 class="max-w-4xl text-5xl font-black leading-[0.9] tracking-[-0.06em] sm:text-7xl">Turn signals<br>into decisions.</h2>
                <x-ui.button href="/dashboard" size="lg" class="bg-background text-foreground hover:bg-background/90">Buka analytics</x-ui.button>
            </div>
        </section>
    </main>
    <footer class="flex flex-col gap-2 border-t border-border px-4 py-6 text-xs font-semibold uppercase tracking-widest text-muted-foreground sm:flex-row sm:justify-between sm:px-8"><span>ROBLOX.TRENDS</span><span>Data from Roblox discover &amp; games API</span></footer>

    @if (!empty($ranking['ranking']))
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const top5 = @json(array_slice($ranking['ranking'], 0, 5));
        registerChart(document.querySelector('#minichart'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
            series: [{ name: 'Jumlah Game', data: top5.map((row) => row.game_count) }],
            xaxis: { categories: top5.map((row) => row.genreL1) },
            dataLabels: { enabled: false },
        });
    });
    </script>
    @endif
</body>
</html>
```

- [ ] **Step 3: Run focused and full verification**

Run `npm run build && php artisan test --filter=LandingTest && php artisan test`. Expected: 3 landing tests and 13 total tests pass.

- [ ] **Step 4: Commit**

```powershell
git add Frontend/resources/views/landing.blade.php Frontend/tests/Feature/LandingTest.php
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "feat: build expressive editorial landing page"
```

### Task 4: Ringkasan editorial workspace

**Files:**
- Modify: `Frontend/tests/Feature/DashboardTest.php`
- Modify: `Frontend/resources/views/dashboard/ringkasan.blade.php`

- [ ] **Step 1: Add failing overview assertions**

Add to `test_ringkasan_page_ok()`:

```php
->assertSee('Genre intelligence')
->assertSee('data-workspace="ringkasan"', false)
->assertSeeInOrder(['Total Game', 'Jumlah Genre', 'Rating Rata-rata', 'Ranking Genre']);
```

Run the focused test and expect failure on `Genre intelligence`.

- [ ] **Step 2: Replace the overview markup**

Replace `dashboard/ringkasan.blade.php` completely:

```blade
@extends('layouts.app')
@section('content')
<section data-workspace="ringkasan" class="space-y-8">
    <x-analytics.page-heading eyebrow="Market pulse / Live snapshot" title="Genre intelligence."
        description="Baca komposisi pasar Roblox, pemain aktif, dan kualitas rata-rata setiap genre dalam satu workspace." />

    <div class="grid gap-3 sm:grid-cols-3">
        <x-analytics.metric-card label="Total Game" :value="$snapshot['game_count'] ?? '—'" />
        <x-analytics.metric-card label="Jumlah Genre" :value="count($ranking['ranking'])" />
        <x-analytics.metric-card label="Rating Rata-rata" :value="count($ranking['ranking']) ? round(collect($ranking['ranking'])->avg('avg_rating'), 1) : '—'" />
    </div>

    <div class="grid gap-3 xl:grid-cols-[1.2fr_0.8fr]">
        <x-ui.card variant="sectioned" class="editorial-card">
            <x-ui.card-header><p class="editorial-eyebrow">Demand / Active players</p><x-ui.card-title>Rata-rata Pemain Aktif per Genre</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><div id="rankingchart"></div></x-ui.card-content>
        </x-ui.card>
        <x-ui.card variant="sectioned" class="editorial-card">
            <x-ui.card-header><p class="editorial-eyebrow">Supply / Share</p><x-ui.card-title>Komposisi Genre (%)</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><div id="sharechart"></div></x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.card variant="sectioned" class="editorial-card">
        <x-ui.card-header><p class="editorial-eyebrow">Complete index</p><x-ui.card-title>Ranking Genre</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <x-ui.table>
                <x-ui.table-header><x-ui.table-row><x-ui.table-head>Genre</x-ui.table-head><x-ui.table-head>Game</x-ui.table-head><x-ui.table-head>Avg Playing</x-ui.table-head><x-ui.table-head>Avg Rating</x-ui.table-head></x-ui.table-row></x-ui.table-header>
                <x-ui.table-body>
                    @foreach ($ranking['ranking'] as $row)
                        <x-ui.table-row><x-ui.table-cell class="font-semibold">{{ $row['genreL1'] }}</x-ui.table-cell><x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell><x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell><x-ui.table-cell>{{ round($row['avg_rating'], 1) }}</x-ui.table-cell></x-ui.table-row>
                    @endforeach
                </x-ui.table-body>
            </x-ui.table>
        </x-ui.card-content>
    </x-ui.card>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const share = @json($ranking['share']);
    const ranking = @json($ranking['ranking']);
    registerChart(document.querySelector('#rankingchart'), {
        chart: { type: 'bar', height: 340, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
        series: [{ name: 'Avg Playing', data: ranking.map((row) => Math.round(row.avg_playing)) }],
        xaxis: { categories: ranking.map((row) => row.genreL1) },
        dataLabels: { enabled: false },
    });
    registerChart(document.querySelector('#sharechart'), {
        chart: { type: 'donut', height: 340, toolbar: { show: false } },
        series: Object.values(share),
        labels: Object.keys(share),
        dataLabels: { enabled: false },
        legend: { position: 'bottom' },
    });
});
</script>
@endsection
```

- [ ] **Step 3: Verify**

Run `npm run build && php artisan test --filter=test_ringkasan_page_ok && php artisan test`. Expected: all 13 tests pass.

- [ ] **Step 4: Commit**

```powershell
git add Frontend/resources/views/dashboard/ringkasan.blade.php Frontend/tests/Feature/DashboardTest.php
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "feat: restyle overview as editorial analytics workspace"
```

### Task 5: Saturation and viral editorial workspaces

**Files:**
- Modify: `Frontend/tests/Feature/DashboardTest.php`
- Modify: `Frontend/resources/views/dashboard/saturasi.blade.php`
- Modify: `Frontend/resources/views/dashboard/viral.blade.php`

- [ ] **Step 1: Add failing page-specific assertions**

Update the two tests:

```php
public function test_saturasi_page_ok(): void
{
    $this->fakeAll();
    $this->get('/dashboard/saturasi')->assertOk()
        ->assertSee('Market saturation')
        ->assertSee('data-workspace="saturasi"', false)
        ->assertSee('data-page="saturasi" aria-current="page"', false)
        ->assertSee('healthy');
}

public function test_viral_page_ok(): void
{
    $this->fakeAll();
    $this->get('/dashboard/viral')->assertOk()
        ->assertSee('Young momentum')
        ->assertSee('data-workspace="viral"', false)
        ->assertSee('data-page="viral" aria-current="page"', false)
        ->assertSee('umur &lt; 90 hari', false);
}
```

Run both focused tests and expect failures on the new headings.

- [ ] **Step 2: Rebuild saturation view**

Replace `dashboard/saturasi.blade.php` completely:

```blade
@extends('layouts.app')
@section('content')
<section data-workspace="saturasi" class="space-y-8">
    <x-analytics.page-heading eyebrow="Supply pressure / Genre map" title="Market saturation."
        description="Bandingkan jumlah game dan pemain aktif untuk melihat genre yang padat, sehat, atau baru tumbuh." />
    <div class="flex flex-wrap gap-2" aria-label="Legenda status saturasi">
        <x-ui.badge tone="danger">oversaturated</x-ui.badge><x-ui.badge tone="success">emerging</x-ui.badge><x-ui.badge tone="neutral">healthy</x-ui.badge>
    </div>
    <x-ui.card variant="sectioned" class="editorial-card">
        <x-ui.card-header><p class="editorial-eyebrow">Supply map / Status color</p><x-ui.card-title>Jumlah Game per Genre (warna = status saturasi)</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content><div id="satchart"></div></x-ui.card-content>
    </x-ui.card>
    <x-ui.card variant="sectioned" class="editorial-card">
        <x-ui.card-header><p class="editorial-eyebrow">Genre index</p><x-ui.card-title>Detail Saturasi</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <x-ui.table>
                <x-ui.table-header><x-ui.table-row><x-ui.table-head>Genre</x-ui.table-head><x-ui.table-head>Game</x-ui.table-head><x-ui.table-head>Avg Playing</x-ui.table-head><x-ui.table-head>Status</x-ui.table-head></x-ui.table-row></x-ui.table-header>
                <x-ui.table-body>
                    @foreach ($saturation['data'] as $row)
                        <x-ui.table-row><x-ui.table-cell class="font-semibold">{{ $row['genreL1'] }}</x-ui.table-cell><x-ui.table-cell>{{ $row['game_count'] }}</x-ui.table-cell><x-ui.table-cell>{{ round($row['avg_playing']) }}</x-ui.table-cell><x-ui.table-cell>
                            @if ($row['status'] === 'oversaturated')<x-ui.badge tone="danger">oversaturated</x-ui.badge>
                            @elseif ($row['status'] === 'emerging')<x-ui.badge tone="success">emerging</x-ui.badge>
                            @else<x-ui.badge tone="neutral">healthy</x-ui.badge>@endif
                        </x-ui.table-cell></x-ui.table-row>
                    @endforeach
                </x-ui.table-body>
            </x-ui.table>
        </x-ui.card-content>
    </x-ui.card>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const saturation = @json($saturation['data']);
    const colorFor = (status) => status === 'oversaturated' ? '#dc2626' : (status === 'emerging' ? '#16a34a' : '#94a3b8');
    registerChart(document.querySelector('#satchart'), {
        chart: { type: 'bar', height: 380, toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 2, columnWidth: '58%' } },
        series: [{ name: 'Jumlah Game', data: saturation.map((row) => ({ x: row.genreL1, y: row.game_count, fillColor: colorFor(row.status) })) }],
        dataLabels: { enabled: false },
    });
});
</script>
@endsection
```

- [ ] **Step 3: Rebuild viral view**

Replace `dashboard/viral.blade.php` completely:

```blade
@extends('layouts.app')
@section('content')
<section data-workspace="viral" class="space-y-8">
    <x-analytics.page-heading eyebrow="Early signals / &lt; 90 days" title="Young momentum."
        description="Game viral muda (umur &lt; 90 hari) diurutkan berdasarkan pertumbuhan pemain per hari." />
    <div class="grid gap-3 sm:max-w-sm"><x-analytics.metric-card label="Game terdeteksi" :value="count($viral['data'])" annotation="Jendela usia maksimum 90 hari" /></div>
    <x-ui.card variant="sectioned" class="editorial-card">
        <x-ui.card-header><p class="editorial-eyebrow">Velocity / Top 15</p><x-ui.card-title>Kecepatan Pertumbuhan Pemain</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content><div id="viralchart"></div></x-ui.card-content>
    </x-ui.card>
    <x-ui.card variant="sectioned" class="editorial-card">
        <x-ui.card-header><p class="editorial-eyebrow">Young games index</p><x-ui.card-title>Daftar Game</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <x-ui.table>
                <x-ui.table-header><x-ui.table-row><x-ui.table-head>Nama</x-ui.table-head><x-ui.table-head>Playing</x-ui.table-head><x-ui.table-head>Umur (hari)</x-ui.table-head><x-ui.table-head>Playing/hari</x-ui.table-head><x-ui.table-head>Genre</x-ui.table-head></x-ui.table-row></x-ui.table-header>
                <x-ui.table-body>
                    @foreach ($viral['data'] as $row)
                        <x-ui.table-row><x-ui.table-cell class="font-semibold">{{ $row['name'] }}</x-ui.table-cell><x-ui.table-cell>{{ $row['playing'] }}</x-ui.table-cell><x-ui.table-cell>{{ $row['umur_hari'] }}</x-ui.table-cell><x-ui.table-cell>{{ $row['playing_per_hari'] }}</x-ui.table-cell><x-ui.table-cell>{{ $row['genreL1'] }}</x-ui.table-cell></x-ui.table-row>
                    @endforeach
                </x-ui.table-body>
            </x-ui.table>
        </x-ui.card-content>
    </x-ui.card>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const viral = @json($viral['data']).slice(0, 15);
    registerChart(document.querySelector('#viralchart'), {
        chart: { type: 'bar', height: 420, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
        series: [{ name: 'Playing/hari', data: viral.map((row) => row.playing_per_hari) }],
        xaxis: { categories: viral.map((row) => row.name) },
        dataLabels: { enabled: false },
    });
});
</script>
@endsection
```

- [ ] **Step 4: Verify**

Run:

```powershell
cd Frontend
npm run build
php artisan test --filter=DashboardTest
php artisan test
```

Expected: 5 dashboard tests and 13 total tests pass.

- [ ] **Step 5: Commit**

```powershell
git add Frontend/resources/views/dashboard/saturasi.blade.php Frontend/resources/views/dashboard/viral.blade.php Frontend/tests/Feature/DashboardTest.php
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "feat: restyle saturation and viral workspaces"
```

### Task 6: Final regression and visual verification

**Files:**
- Modify only if verification finds a defect in files already listed above.

- [ ] **Step 1: Run static contract scans**

```powershell
rg -n "cdn\.jsdelivr\.net/npm/apexcharts|new ApexCharts" Frontend/resources/views
rg -n "registerChart" Frontend/resources/views
rg -n "data-workspace|data-page=|data-ui=\"dashboard-nav\"" Frontend/resources/views
```

Expected: first command returns no matches; every chart view matches `registerChart`; all dashboard pages expose workspace/page markers.

- [ ] **Step 2: Run the full automated gate**

```powershell
cd Frontend
npm run build
php artisan test
```

Expected: build exits 0; 13 tests pass with zero failures.

- [ ] **Step 3: Run local browser verification**

Start the frontend and, when available, the existing Backend:

```powershell
cd Frontend
php artisan serve --port=8001
```

Verify at 1440px, 768px, and 390px widths:

- `/`: hero, signal band, ASCII movement, metrics, top-five chart, API-down fallback.
- `/dashboard`: desktop rail/mobile bottom nav, active overview state, table overflow.
- `/dashboard/saturasi`: legend badges, saturation chart, active saturation state.
- `/dashboard/viral`: horizontal chart, table, active viral state.
- Theme toggle changes blue to red and updates already-rendered charts.
- With reduced motion enabled, ASCII/reveal animation stops.
- No horizontal page overflow and no mobile navigation overlap.

- [ ] **Step 4: Commit only verification fixes, if any**

```powershell
git add Frontend/resources Frontend/tests
git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit -m "fix: polish editorial dashboard responsive states"
```

If no fixes are needed, do not create an empty commit.

- [ ] **Step 5: Final status check**

Run `git status --short` and verify only the user's pre-existing notebook change, `.superpowers/`, and older untracked plan remain outside this implementation.
