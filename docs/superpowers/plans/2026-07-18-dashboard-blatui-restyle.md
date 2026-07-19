# Dashboard BlatUI Restyle + Landing Page — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Merombak tampilan dashboard dengan komponen BlatUI (sidebar, dark/light, card/table/badge, chart theme-aware via npm) dan menambah landing page interaktif di `/` dengan dashboard pindah ke `/dashboard/*`.

**Architecture:** Foundation BlatUI dipasang penuh (composer + npm + vendor:publish + Vite build). Layout baru bersidebar; ApexCharts pindah dari CDN ke bundle npm dengan registry `registerChart()` yang membuat semua chart theme-aware dan ikut berubah saat toggle tema. Landing page memakai FastApiClient yang sudah ada (tanpa endpoint baru), dengan counter animasi Alpine dan chart mini.

**Tech Stack:** Laravel 13 + BlatUI v1.16 (Blade/Alpine/Tailwind v4), Vite, ApexCharts (npm), PHPUnit.

## Global Constraints

- Kerja di `Frontend/` (Laravel). Backend/FastAPI TIDAK disentuh sama sekali.
- Route baru: `/` = landing; `/dashboard`, `/dashboard/saturasi`, `/dashboard/viral` = tiga halaman existing. Sidebar & CTA memakai URL baru.
- Test existing di-update ke route baru TANPA melonggarkan asersi konten (`assertSee('Adventure')`, banner uvicorn/"Belum ada data"/error tetap).
- Landing TIDAK boleh error saat API unavailable/no_data — hero + CTA tetap tampil.
- ApexCharts via npm bundle; CDN `cdn.jsdelivr.net/npm/apexcharts` DIHAPUS dari layout. Chart dibuat lewat `registerChart(el, options)` (didefinisikan Task 1) — jangan `new ApexCharts(...)` langsung di view.
- Tema: class `dark` pada `<html>`, persist di `localStorage.theme`, default ikut `prefers-color-scheme`. Chart ikut tema & update saat toggle.
- Komponen BlatUI dipasang via `php artisan blatui:add <name>`; PAKAI API komponen yang benar-benar ter-publish di `resources/views/components/ui/` — kalau berbeda dari contoh plan, sesuaikan pemakaian (bukan mengarang); kalau jauh berbeda/tak ada, STOP lapor BLOCKED.
- Data flow tidak berubah: controller → view → `@json` → chart; tabel loop `{{ }}` (escaped).
- Git identity: `git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit ...`. JANGAN commit `.env`, `vendor/`, `node_modules/`, `public/build/` (cek .gitignore Laravel — `public/build` di-ignore default oleh Laravel 12; verifikasi).
- Test: `cd Frontend && php artisan test`. Setelah tiap task, seluruh suite harus hijau.

---

## File Structure

```
Frontend/
├── composer.json                 (M: +tailwind-merge-laravel, +blade-lucide-icons)
├── package.json                  (M: +alpinejs, plugins, apexcharts)
├── resources/css/app.css         (M: import tokens BlatUI)
├── resources/js/app.js           (M: Alpine+plugins+engine, ApexCharts, registerChart, theme observer)
├── resources/views/components/ui/…  (A: hasil blatui:add — committed)
├── resources/views/layouts/app.blade.php   (M: sidebar + toggle + alert + @vite)
├── resources/views/landing.blade.php       (A: hero + stats + mini chart)
├── resources/views/dashboard/{ringkasan,saturasi,viral}.blade.php  (M: restyle)
├── app/Http/Controllers/DashboardController.php  (M: +landing())
├── routes/web.php                (M: restrukturisasi)
└── tests/Feature/{DashboardTest,LandingTest}.php  (M/A)
README.md                         (M: cara run + npm build)
```

---

## Task 1: Foundations BlatUI + infrastruktur chart

**Files:**
- Modify: `Frontend/composer.json`, `Frontend/package.json` (via CLI), `Frontend/resources/css/app.css`, `Frontend/resources/js/app.js`

**Interfaces:**
- Produces: `window.registerChart(el, options)` — membuat ApexCharts theme-aware & mendaftarkannya untuk update saat toggle tema; class `dark` di `<html>` sebagai kontrak tema; aset ter-build (`npm run build`); `blatui:init` doctor semua ✓.

- [ ] **Step 0: Verifikasi npm tersedia**

Run: `npm --version && node --version`
Expected: versi tampil. Jika `npm` tidak ditemukan → BLOCKED (jangan install Node sendiri).

- [ ] **Step 1: Install dependensi composer & npm**

```bash
cd Frontend
composer require gehrisandro/tailwind-merge-laravel mallardduck/blade-lucide-icons
npm install -D alpinejs @alpinejs/anchor @floating-ui/dom @alpinejs/collapse @alpinejs/focus apexcharts
```
Expected: sukses tanpa error.

- [ ] **Step 2: Publish foundations**

Run: `cd Frontend && php artisan vendor:publish --tag=blatui-foundations`
Expected: theme tokens ke `resources/css/`, engine ke `resources/js/`. CATAT nama file persisnya (mis. `resources/css/blatui.css`, `resources/js/blatui.js` — bisa berbeda). Jika publish gagal/kosong → BLOCKED dengan output persis.

- [ ] **Step 3: Wire app.css & app.js**

`resources/css/app.css` — pastikan meng-import Tailwind & tokens hasil publish (sesuaikan nama file dari Step 2):
```css
@import 'tailwindcss';
@import './blatui.css'; /* ganti sesuai nama file tokens hasil publish */
```
`resources/js/app.js`:
```js
import './bootstrap';
import Alpine from 'alpinejs';
import anchor from '@alpinejs/anchor';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import ApexCharts from 'apexcharts';
// Import engine BlatUI hasil publish (sesuaikan path dari Step 2):
import './blatui';

window.ApexCharts = ApexCharts;

// ── Tema: default ikut sistem, persist di localStorage ──
if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}

// ── Chart registry theme-aware ──
const isDark = () => document.documentElement.classList.contains('dark');
const chartTheme = () => ({ mode: isDark() ? 'dark' : 'light', foreColor: isDark() ? '#a1a1aa' : '#52525b' });
window._charts = [];
window.registerChart = (el, options) => {
    const t = chartTheme();
    const c = new ApexCharts(el, {
        ...options,
        theme: { mode: t.mode },
        chart: { ...(options.chart || {}), background: 'transparent', foreColor: t.foreColor },
    });
    c.render();
    window._charts.push(c);
    return c;
};
new MutationObserver(() => {
    const t = chartTheme();
    window._charts.forEach(c => c.updateOptions({ theme: { mode: t.mode }, chart: { foreColor: t.foreColor } }));
}).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

window.Alpine = Alpine;
Alpine.plugin(anchor);
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.start();
```
⚠️ Jika engine BlatUI hasil publish SUDAH meng-import/start Alpine sendiri, JANGAN double-start — sesuaikan (hapus start/plugin manual yang duplikat) dan catat di report.

- [ ] **Step 4: Build & doctor**

```bash
cd Frontend && npm run build && php artisan blatui:init
```
Expected: build sukses; doctor menunjukkan SEMUA item ✓ (installed). Jika ada yang masih missing → perbaiki sesuai instruksi doctor; jika tak jelas → BLOCKED.

- [ ] **Step 5: Seluruh test tetap hijau (view belum berubah)**

Run: `cd Frontend && php artisan test`
Expected: 10 passed (view masih pakai CDN — belum disentuh; build tidak mengganggu test).

- [ ] **Step 6: Commit**

```bash
git add Frontend/composer.json Frontend/composer.lock Frontend/package.json Frontend/package-lock.json Frontend/resources/css Frontend/resources/js Frontend/vite.config.js
git commit -m "feat: install BlatUI foundations, npm ApexCharts, theme-aware chart registry"
```
Verifikasi `git status`: tidak ada `node_modules/`, `public/build/`, `.env`.

---

## Task 2: Restrukturisasi route + landing minimal (TDD)

**Files:**
- Modify: `Frontend/routes/web.php`, `Frontend/app/Http/Controllers/DashboardController.php`, `Frontend/tests/Feature/DashboardTest.php`
- Create: `Frontend/tests/Feature/LandingTest.php`, `Frontend/resources/views/landing.blade.php` (minimal)

**Interfaces:**
- Consumes: `FastApiClient` (snapshot(), genreRanking(), status()).
- Produces: route `/` → `DashboardController@landing`; `/dashboard`, `/dashboard/saturasi`, `/dashboard/viral` → method existing. View `landing` menerima `$snapshot`, `$status`, `$ranking`. Task 5 akan restyle view landing ini tanpa mengubah kontrak data.

- [ ] **Step 1: Update DashboardTest ke route baru + tulis LandingTest**

Di `Frontend/tests/Feature/DashboardTest.php`, ganti URL: `$this->get('/')` → `$this->get('/dashboard')` (test ringkasan & test unavailable & test error), `'/saturasi'` → `'/dashboard/saturasi'`, `'/viral'` → `'/dashboard/viral'`. JANGAN ubah asersi konten.

Create `Frontend/tests/Feature/LandingTest.php`:
```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LandingTest extends TestCase
{
    public function test_landing_shows_hero_and_stats(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [['genreL1' => 'Simulation', 'game_count' => 190, 'avg_visits' => 1, 'avg_playing' => 1, 'avg_rating' => 92]], 'share' => ['Simulation' => 100.0]], 200),
        ]);
        $this->get('/')->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Lihat Dashboard')
            ->assertSee('781');
    }

    public function test_landing_survives_api_down(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $this->get('/')->assertStatus(200)
            ->assertSee('Analisis Trend Roblox')
            ->assertSee('Lihat Dashboard');
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Frontend && php artisan test --filter=LandingTest`
Expected: FAIL (route `/` masih ringkasan; view landing belum ada). DashboardTest juga FAIL (404 di /dashboard).

- [ ] **Step 3: Routes + controller + view minimal**

`Frontend/routes/web.php` (ganti isi):
```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'landing']);
Route::get('/dashboard', [DashboardController::class, 'ringkasan']);
Route::get('/dashboard/saturasi', [DashboardController::class, 'saturasi']);
Route::get('/dashboard/viral', [DashboardController::class, 'viral']);
```
Tambah method di `DashboardController`:
```php
public function landing(FastApiClient $api)
{
    $snapshot = $api->snapshot();
    $ranking = $api->genreRanking();
    return view('landing', [
        'snapshot' => $snapshot, 'status' => $api->status(), 'ranking' => $ranking,
    ]);
}
```
Create `Frontend/resources/views/landing.blade.php` (minimal — restyle penuh di Task 5):
```blade
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
```

- [ ] **Step 4: Jalankan SELURUH test, pastikan LULUS**

Run: `cd Frontend && php artisan test`
Expected: 12 passed (10 existing dengan route baru + 2 landing).

- [ ] **Step 5: Commit**

```bash
git add Frontend/routes/web.php Frontend/app/Http/Controllers/DashboardController.php Frontend/resources/views/landing.blade.php Frontend/tests/Feature/DashboardTest.php Frontend/tests/Feature/LandingTest.php
git commit -m "feat: move dashboard to /dashboard, add landing route with minimal page"
```

---

## Task 3: Layout baru — sidebar, toggle tema, alert, @vite

**Files:**
- Modify: `Frontend/resources/views/layouts/app.blade.php`, tiga view `Frontend/resources/views/dashboard/*.blade.php` (hanya bagian `<script>` chart → `registerChart`)
- (CLI) `php artisan blatui:add button card table badge alert separator` (tambah yang dibutuhkan; commit hasil publish di `resources/views/components/ui/`)

**Interfaces:**
- Consumes: `registerChart` (Task 1), route baru (Task 2).
- Produces: layout bersidebar dengan `@yield('content')` yang dipakai ketiga view dashboard; banner status dengan teks TIDAK berubah; `@vite` menggantikan CDN.

- [ ] **Step 1: blatui:add komponen**

Run: `cd Frontend && php artisan blatui:add button card table badge alert separator`
Expected: file ter-publish ke `resources/views/components/ui/`. BACA file hasil publish untuk tahu API-nya (props/slot). Kalau sebuah komponen tidak tersedia (mis. `table` tidak ada), catat & pakai `<table>` Tailwind polos — jangan mengarang.

- [ ] **Step 2: Tulis ulang layout**

`Frontend/resources/views/layouts/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="w-60 shrink-0 border-r border-border bg-card flex flex-col">
        <div class="p-4 font-semibold text-lg">Analisis Trend Roblox</div>
        <nav class="flex-1 px-2 space-y-1">
            <a href="/dashboard" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ request()->is('dashboard') ? 'bg-accent text-accent-foreground font-medium' : 'text-muted-foreground hover:bg-accent/50' }}">
                <x-lucide-bar-chart-3 class="size-4" /> Ringkasan
            </a>
            <a href="/dashboard/saturasi" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ request()->is('dashboard/saturasi') ? 'bg-accent text-accent-foreground font-medium' : 'text-muted-foreground hover:bg-accent/50' }}">
                <x-lucide-gauge class="size-4" /> Saturasi
            </a>
            <a href="/dashboard/viral" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm {{ request()->is('dashboard/viral') ? 'bg-accent text-accent-foreground font-medium' : 'text-muted-foreground hover:bg-accent/50' }}">
                <x-lucide-flame class="size-4" /> Viral Muda
            </a>
        </nav>
        <div class="p-4 border-t border-border">
            <button type="button" x-data
                @click="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                <x-lucide-sun-moon class="size-4" /> Ganti Tema
            </button>
        </div>
    </aside>

    {{-- Konten --}}
    <main class="flex-1 p-6 space-y-4 overflow-x-hidden">
        @if ($status === 'unavailable')
            <div class="rounded-lg border border-destructive/50 bg-destructive/10 text-destructive px-4 py-3 text-sm">
                Server analisis tidak aktif. Jalankan: <code>uvicorn api:app --port 8000</code> di folder Backend.
            </div>
        @elseif ($status === 'no_data')
            <div class="rounded-lg border border-yellow-500/50 bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 px-4 py-3 text-sm">
                Belum ada data snapshot. Jalankan ingest dulu.
            </div>
        @elseif ($status === 'error')
            <div class="rounded-lg border border-destructive/50 bg-destructive/10 text-destructive px-4 py-3 text-sm">
                Server analisis mengembalikan error. Cek log uvicorn di folder Backend.
            </div>
        @elseif ($snapshot)
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <x-ui.badge variant="secondary">Snapshot #{{ $snapshot['snapshot_id'] }}</x-ui.badge>
                <span>{{ $snapshot['game_count'] }} game</span>
                <span>·</span>
                <span>{{ $snapshot['taken_at'] }}</span>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
```
⚠️ Sesuaikan `<x-ui.badge>`/class token (bg-background, text-foreground, border-border, bg-card, bg-accent, text-muted-foreground, destructive) ke token yang benar-benar ada di CSS tokens hasil publish — kalau nama tokennya berbeda, pakai yang ada. Teks banner TIDAK boleh berubah (test bergantung).
⚠️ Ikon lucide: kalau nama ikon (`bar-chart-3`, `gauge`, `flame`, `sun-moon`) tidak tersedia di paket versi terpasang, ganti dengan ikon lain yang ada — jangan biarkan error.

- [ ] **Step 3: Ganti pembuatan chart di 3 view dashboard**

Di `ringkasan.blade.php`, `saturasi.blade.php`, `viral.blade.php`: setiap `new ApexCharts(document.querySelector("#x"), {...}).render();` → `registerChart(document.querySelector("#x"), {...});` (opsi chart tidak berubah). Bungkus script dengan penundaan sampai bundle siap:
```blade
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ... registerChart(...) calls ...
    });
</script>
```

- [ ] **Step 4: Build + seluruh test**

```bash
cd Frontend && npm run build && php artisan test
```
Expected: build sukses; 12 passed (banner & konten tetap ter-render server-side).

- [ ] **Step 5: Commit**

```bash
git add Frontend/resources/views
git commit -m "feat: BlatUI sidebar layout with theme toggle and vite assets"
```

---

## Task 4: Restyle tiga halaman dashboard

**Files:**
- Modify: `Frontend/resources/views/dashboard/ringkasan.blade.php`, `.../saturasi.blade.php`, `.../viral.blade.php`

**Interfaces:**
- Consumes: layout Task 3, komponen ui hasil Task 3 Step 1, `registerChart`.
- Produces: tiga halaman ber-card. Kontrak data dari controller TIDAK berubah.

- [ ] **Step 1: Ringkasan — stat cards + 2 chart cards + tabel**

`ringkasan.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold">Ringkasan Genre</h1>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-ui.card>
            <x-ui.card-header><x-ui.card-title class="text-sm text-muted-foreground">Total Game</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><span class="text-3xl font-bold">{{ $snapshot['game_count'] ?? '—' }}</span></x-ui.card-content>
        </x-ui.card>
        <x-ui.card>
            <x-ui.card-header><x-ui.card-title class="text-sm text-muted-foreground">Jumlah Genre</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><span class="text-3xl font-bold">{{ count($ranking['ranking']) }}</span></x-ui.card-content>
        </x-ui.card>
        <x-ui.card>
            <x-ui.card-header><x-ui.card-title class="text-sm text-muted-foreground">Rating Rata-rata</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><span class="text-3xl font-bold">{{ count($ranking['ranking']) ? round(collect($ranking['ranking'])->avg('avg_rating'), 1) : '—' }}</span></x-ui.card-content>
        </x-ui.card>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-ui.card>
            <x-ui.card-header><x-ui.card-title>Komposisi Genre (%)</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><div id="sharechart"></div></x-ui.card-content>
        </x-ui.card>
        <x-ui.card>
            <x-ui.card-header><x-ui.card-title>Rata-rata Pemain Aktif per Genre</x-ui.card-title></x-ui.card-header>
            <x-ui.card-content><div id="rankingchart"></div></x-ui.card-content>
        </x-ui.card>
    </div>

    <x-ui.card>
        <x-ui.card-header><x-ui.card-title>Ranking Genre</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <table class="w-full text-sm">
                <thead class="border-b border-border text-left text-muted-foreground">
                    <tr><th class="py-2">Genre</th><th>Game</th><th>Avg Playing</th><th>Avg Rating</th></tr>
                </thead>
                <tbody>
                @foreach ($ranking['ranking'] as $row)
                    <tr class="border-b border-border/50">
                        <td class="py-2 font-medium">{{ $row['genreL1'] }}</td>
                        <td>{{ $row['game_count'] }}</td>
                        <td>{{ round($row['avg_playing']) }}</td>
                        <td>{{ round($row['avg_rating'], 1) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const share = @json($ranking['share']);
            const ranking = @json($ranking['ranking']);
            registerChart(document.querySelector('#sharechart'), {
                chart: { type: 'pie', height: 320 },
                series: Object.values(share),
                labels: Object.keys(share),
            });
            registerChart(document.querySelector('#rankingchart'), {
                chart: { type: 'bar', height: 320 },
                series: [{ name: 'Avg Playing', data: ranking.map(r => Math.round(r.avg_playing)) }],
                xaxis: { categories: ranking.map(r => r.genreL1) },
            });
        });
    </script>
@endsection
```
(Jika API komponen card hasil publish berbeda — mis. slot bernama — sesuaikan pemakaian; struktur konten dipertahankan.)

- [ ] **Step 2: Saturasi — chart card + tabel dengan badge status**

`saturasi.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold">Saturasi Genre</h1>

    <x-ui.card>
        <x-ui.card-header><x-ui.card-title>Jumlah Game per Genre (warna = status saturasi)</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content><div id="satchart"></div></x-ui.card-content>
    </x-ui.card>

    <x-ui.card>
        <x-ui.card-header><x-ui.card-title>Detail Saturasi</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <table class="w-full text-sm">
                <thead class="border-b border-border text-left text-muted-foreground">
                    <tr><th class="py-2">Genre</th><th>Game</th><th>Avg Playing</th><th>Status</th></tr>
                </thead>
                <tbody>
                @foreach ($saturation['data'] as $row)
                    <tr class="border-b border-border/50">
                        <td class="py-2 font-medium">{{ $row['genreL1'] }}</td>
                        <td>{{ $row['game_count'] }}</td>
                        <td>{{ round($row['avg_playing']) }}</td>
                        <td>
                            @if ($row['status'] === 'oversaturated')
                                <x-ui.badge variant="destructive">oversaturated</x-ui.badge>
                            @elseif ($row['status'] === 'emerging')
                                <x-ui.badge class="bg-green-600 text-white">emerging</x-ui.badge>
                            @else
                                <x-ui.badge variant="secondary">healthy</x-ui.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sat = @json($saturation['data']);
            const colorFor = s => s === 'oversaturated' ? '#e74c3c' : (s === 'emerging' ? '#2ecc71' : '#95a5a6');
            registerChart(document.querySelector('#satchart'), {
                chart: { type: 'bar', height: 360 },
                series: [{ name: 'Jumlah Game', data: sat.map(r => ({ x: r.genreL1, y: r.game_count, fillColor: colorFor(r.status) })) }],
            });
        });
    </script>
@endsection
```

- [ ] **Step 3: Viral — chart card + tabel**

`viral.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold">Game Viral Muda <span class="text-base font-normal text-muted-foreground">(umur &lt; 90 hari)</span></h1>

    <x-ui.card>
        <x-ui.card-header><x-ui.card-title>Top 15 Kecepatan Pertumbuhan Pemain</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content><div id="viralchart"></div></x-ui.card-content>
    </x-ui.card>

    <x-ui.card>
        <x-ui.card-header><x-ui.card-title>Daftar Game</x-ui.card-title></x-ui.card-header>
        <x-ui.card-content>
            <table class="w-full text-sm">
                <thead class="border-b border-border text-left text-muted-foreground">
                    <tr><th class="py-2">Nama</th><th>Playing</th><th>Umur (hari)</th><th>Playing/hari</th><th>Genre</th></tr>
                </thead>
                <tbody>
                @foreach ($viral['data'] as $row)
                    <tr class="border-b border-border/50">
                        <td class="py-2 font-medium">{{ $row['name'] }}</td>
                        <td>{{ $row['playing'] }}</td>
                        <td>{{ $row['umur_hari'] }}</td>
                        <td>{{ $row['playing_per_hari'] }}</td>
                        <td>{{ $row['genreL1'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-ui.card-content>
    </x-ui.card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const viral = @json($viral['data']).slice(0, 15);
            registerChart(document.querySelector('#viralchart'), {
                chart: { type: 'bar', height: 400 },
                plotOptions: { bar: { horizontal: true } },
                series: [{ name: 'Playing/hari', data: viral.map(r => r.playing_per_hari) }],
                xaxis: { categories: viral.map(r => r.name) },
            });
        });
    </script>
@endsection
```

- [ ] **Step 4: Build + seluruh test**

Run: `cd Frontend && npm run build && php artisan test`
Expected: 12 passed.

- [ ] **Step 5: Commit**

```bash
git add Frontend/resources/views/dashboard
git commit -m "feat: restyle dashboard pages with BlatUI cards, badges, themed tables"
```

---

## Task 5: Landing page penuh + README

**Files:**
- Modify: `Frontend/resources/views/landing.blade.php`, `README.md`

**Interfaces:**
- Consumes: `$snapshot`, `$status`, `$ranking` (Task 2); `registerChart`; token tema.
- Produces: landing final. Asersi test Task 2 tetap terpenuhi ('Analisis Trend Roblox', 'Lihat Dashboard', '781' saat ada data).

- [ ] **Step 1: Landing penuh**

`landing.blade.php` (ganti seluruh isi):
```blade
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
```

- [ ] **Step 2: README — cara run baru**

Di `README.md`, ganti bagian Status/tambah bagian "Menjalankan":
```markdown
## Menjalankan

1. **Backend (FastAPI):**
   `cd Backend && .venv/Scripts/python -m uvicorn api:app --port 8000`
2. **Frontend (Laravel):** *(sekali saja: `cd Frontend && npm install && npm run build`)*
   `cd Frontend && php artisan serve --port=8001`
3. Buka `http://127.0.0.1:8001` — landing page; dashboard di `/dashboard`.
```

- [ ] **Step 3: Build + seluruh test**

Run: `cd Frontend && npm run build && php artisan test`
Expected: 12 passed (asersi landing tetap terpenuhi).

- [ ] **Step 4: Commit**

```bash
git add Frontend/resources/views/landing.blade.php README.md
git commit -m "feat: interactive landing page with live stats, animated counters, mini chart"
```

---

## Verifikasi Akhir (dilakukan controller, bukan task subagent)

Dua server + browser: landing (hero, counter animasi, mini chart), toggle tema (chart ikut berubah), sidebar aktif-state, 3 halaman dashboard (card/badge/chart), banner status. Bukti sebelum merge.

## Self-Review Notes

- **Spec §4a (foundations):** Task 1 ✓ (composer, npm, publish, wiring, build, doctor).
- **Spec §4b (layout sidebar+toggle+alert):** Task 3 ✓; teks banner tak berubah ✓.
- **Spec §4b-2 (route + landing):** Task 2 (route+minimal+test) & Task 5 (penuh: hero/counter/mini chart/graceful) ✓.
- **Spec §4c (tiga halaman):** Task 4 ✓ (stat cards ringkasan, badge saturasi, card semua).
- **Spec §4d (chart theme-aware):** Task 1 `registerChart` + MutationObserver ✓; view memakainya (Task 3 Step 3, Task 4, Task 5) ✓.
- **Spec §5 (testing):** route update tanpa melonggarkan asersi (Task 2 Step 1) ✓; test landing baru ✓; tiap task diakhiri suite penuh ✓.
- **Spec §6 risiko:** STOP-jika-beda di Task 1 Step 2/4, Task 3 Step 1/2 ✓; npm cek di Task 1 Step 0 ✓; fallback tema chart — MutationObserver adalah mekanisme utama; bila gagal saat verifikasi browser, fallback "ikut tema saat load" diterima & dicatat ✓.
- **Placeholder scan:** tidak ada TBD; semua step berkode ✓. Catatan sadar: pemakaian `<x-ui.card>`/`<x-ui.badge>`/ikon lucide ditandai "sesuaikan ke API ter-publish" — itu instruksi adaptasi eksplisit dengan batas STOP, bukan placeholder.
- **Type consistency:** `registerChart(el, options)` konsisten Task 1→3→4→5; kontrak view landing (`$snapshot`,`$status`,`$ranking`) konsisten Task 2→5; route konsisten Task 2→3 ✓.
