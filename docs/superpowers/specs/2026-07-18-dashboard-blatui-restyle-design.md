# Desain: Restyle Dashboard dengan Komponen BlatUI

**Tanggal:** 2026-07-18
**Status:** Draft untuk review
**Cakupan:** Melengkapi foundation BlatUI + restyle layout & 3 halaman dashboard dengan komponen BlatUI; ApexCharts pindah dari CDN ke bundle npm & theme-aware
**Bergantung pada:** dashboard yang sudah jalan (FastAPI + Laravel, ter-merge ke main)
**Di luar cakupan:** perubahan endpoint/data/controller-logic; komponen chart BlatUI (opsional, bukan ketergantungan); halaman baru

---

## 1. Tujuan

Dashboard saat ini fungsional tapi visualnya polos (tabel `border="1"`, nav teks). Restyle dengan komponen BlatUI supaya tampak seperti dashboard analytics modern:
- Tema **light + dark mengikuti sistem**, dengan toggle (BlatUI built-in, persist otomatis).
- **Sidebar kiri** untuk navigasi.
- Chart **theme-aware** (warna ikut tema aktif).
- Semua fungsi & data yang ada tetap bekerja — 10 test existing tetap hijau.

## 2. Kondisi Saat Ini (dari `php artisan blatui:init` doctor)

- Terpasang: BlatUI v1.16 (composer), Tailwind CSS v4.
- **Hilang:** `gehrisandro/tailwind-merge-laravel`, `mallardduck/blade-lucide-icons` (composer); `alpinejs`, `@alpinejs/anchor`, `@floating-ui/dom`, `@alpinejs/collapse`, `@alpinejs/focus`, `apexcharts` (npm); theme tokens + BlatUI engine (perlu `vendor:publish --tag=blatui-foundations`).
- Chart saat ini via CDN `<script src="https://cdn.jsdelivr.net/npm/apexcharts">` di layout — akan dihapus, diganti bundle npm.

## 3. Pendekatan (disetujui: C)

Foundation dipasang penuh; komponen BlatUI untuk struktur UI; ApexCharts tetap (terbukti jalan) tapi via npm + disinkronkan ke tema. Chart component BlatUI boleh diadopsi JIKA saat implementasi terbukti mudah — tidak jadi ketergantungan.

## 4. Rincian

### 4a. Foundations
1. `composer require gehrisandro/tailwind-merge-laravel mallardduck/blade-lucide-icons`
2. `npm install -D alpinejs @alpinejs/anchor @floating-ui/dom @alpinejs/collapse @alpinejs/focus apexcharts`
3. `php artisan vendor:publish --tag=blatui-foundations` (theme tokens ke resources/css, engine ke resources/js)
4. Wire `resources/css/app.css` + `resources/js/app.js` sesuai output publish (import tokens, start Alpine + plugins, import ApexCharts & expose untuk dipakai view).
5. `npm run build`; layout pakai `@vite(['resources/css/app.css','resources/js/app.js'])`, CDN ApexCharts dihapus.
6. Verifikasi: `php artisan blatui:init` doctor menunjukkan semua ✓.

**Konsekuensi run:** frontend kini butuh aset ter-build — `npm run build` sekali (atau `npm run dev` saat development). Dicatat di README.

### 4b. Layout baru (`layouts/app.blade.php`)
- **Sidebar kiri:** judul "Analisis Trend Roblox", 3 item menu (ikon lucide + label): Ringkasan (`/`), Saturasi (`/saturasi`), Viral Muda (`/viral`). Item aktif di-highlight (cek `request()->is(...)`).
- **Toggle tema** light/dark di sidebar bawah (mekanisme dark-mode bawaan BlatUI; default ikut prefers-color-scheme; persist).
- **Area konten:** heading halaman, info snapshot sebagai badge (`Snapshot #N · 781 game · tanggal`), banner status pakai komponen alert/callout BlatUI dengan varian: unavailable → destructive (pesan uvicorn), no_data → warning (pesan ingest), error → destructive (pesan cek log). **Teks pesan & logika @if TIDAK berubah** (test assertSee bergantung padanya).
- Komponen di-`blatui:add` sesuai kebutuhan (mis. button, card, table, badge, alert, separator — daftar final saat implementasi).

### 4c. Tiga halaman
- **Ringkasan:** baris stat card di atas (total game, jumlah genre, avg rating — dihitung dari data yang sudah dikirim controller, tanpa endpoint baru); card "Komposisi Genre" (pie) + card "Rata-rata Pemain per Genre" (bar); tabel ranking pakai komponen table BlatUI.
- **Saturasi:** card chart bar berwarna status; tabel dengan kolom Status sebagai badge (oversaturated=destructive/merah, emerging=hijau, healthy=abu/secondary).
- **Viral Muda:** card chart horizontal bar top-15; tabel game pakai komponen table.
- Data flow tidak berubah: controller → `@json` → chart; tabel loop server-side dengan `{{ }}`.

### 4d. Chart theme-aware
- ApexCharts di-import di `app.js`, di-expose (mis. `window.ApexCharts`) agar `<script>` di view tetap sederhana.
- Opsi chart membaca tema aktif: `theme.mode` dark/light + warna teks/grid dari CSS variables token BlatUI.
- Saat toggle tema: chart di-update (`updateOptions`) atau re-render — pilih yang paling sederhana yang bekerja.

## 5. Testing & Verifikasi

- **Gerbang regresi:** seluruh 10 test existing (5 DashboardTest, 3 FastApiClientTest, 2 bawaan) harus tetap hijau — `assertSee('Adventure')`, banner uvicorn/no_data/error, dsb. menjamin data & status tetap dirender.
- Tidak ada test baru wajib (perubahan visual client-side); boleh menambah assertSee ringan bila membantu (mis. label menu sidebar).
- **Verifikasi akhir:** dua server + browser — cek sidebar, toggle dark/light, chart mengikuti tema, badge status, ketiga halaman.

## 6. Risiko yang Diakui

1. **`vendor:publish` + wiring Vite belum pernah dijalankan di proyek ini** — output BlatUI bisa beda dari dugaan. Mitigasi: aturan STOP-jika-beda (seperti Task 5 scaffold dulu): implementer berhenti & lapor output persis, tidak menebak API.
2. **Build npm di Windows** — node/npm harus tersedia; kalau `npm` tidak ada di PATH, itu blocker yang dilaporkan, bukan ditebak solusinya.
3. **Toggle tema vs re-render chart** — sinkronisasi bisa butuh iterasi; fallback yang bisa diterima: chart mengikuti tema saat load halaman (tanpa live-update saat toggle), dicatat sebagai keterbatasan bila terjadi.
4. **CDN dihapus** — kalau bundle gagal, chart hilang; karenanya verifikasi browser wajib sebelum merge.

## 7. File yang Disentuh

```
Frontend/
├── composer.json / package.json     (dependensi baru)
├── resources/css/app.css            (tokens + tailwind wiring)
├── resources/js/app.js              (Alpine + plugins + ApexCharts)
├── resources/views/layouts/app.blade.php     (sidebar + toggle + alert)
├── resources/views/dashboard/ringkasan.blade.php
├── resources/views/dashboard/saturasi.blade.php
├── resources/views/dashboard/viral.blade.php
└── resources/views/components/ui/…  (hasil blatui:add — generated, di-commit)
README.md                            (cara run: tambah npm run build)
```
