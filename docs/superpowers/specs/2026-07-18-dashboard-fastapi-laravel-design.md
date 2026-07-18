# Desain: Dashboard FastAPI + Laravel (BlatUI)

**Tanggal:** 2026-07-18
**Status:** Draft untuk review
**Cakupan:** API analisis (FastAPI membaca snapshot dari SQLite) + dashboard Laravel Blade/BlatUI dengan ApexCharts
**Bergantung pada:** pipeline `Backend/` yang sudah ada (db.py, cleaning.py, ingest.py, analysis.py) + `roblox.db` berisi ≥1 snapshot
**Di luar cakupan (fase lanjutan):** ARIMA/STL forecasting (masih terkunci sampai histori cukup), scheduler otomatis, autentikasi/multi-user

---

## 1. Tujuan

Membuat hasil analisis genre TERLIHAT lewat dashboard web:
1. FastAPI menyajikan analisis genre dari snapshot terbaru `roblox.db` sebagai JSON.
2. Laravel (Blade + BlatUI + ApexCharts) menampilkannya dalam beberapa halaman bertab.
3. Kegagalan salah satu server memberi pesan jelas, bukan crash misterius.

---

## 2. Konteks & Kondisi Saat Ini

- Pipeline `Backend/` sudah jalan & ter-merge ke main. `analysis.py` punya 4 fungsi murni: `genre_ranking`, `genre_share`, `saturation_flags`, `viral_muda`.
- `roblox.db` menyimpan `games_snapshot` dengan kolom `rating` TAPI TIDAK menyimpan kolom turunan `umur_hari` & `playing_per_hari` (keduanya analysis-only). `created`/`updated` tersimpan sebagai string ISO → cukup untuk merekonstruksi kolom turunan.
- Tooling terverifikasi: PHP 8.4.6 ✅, Composer 2.8.8 ✅. FastAPI/uvicorn BELUM terpasang di `Backend/.venv` (perlu install).
- Frontend: **BlatUI** (`composer require anousss007/blatui`) — komponen shadcn/ui untuk Blade + Alpine.js + Tailwind CSS v4, mendukung ApexCharts. Kode komponen disalin ke proyek (no runtime lock-in).

### Prinsip pembagian tanggung jawab
Python = otak (semua hitung). Laravel = wajah (hanya menampilkan JSON). Laravel tidak menghitung apa pun sendiri.

---

## 3. Arsitektur & Aliran Data

```
roblox.db (SQLite, snapshot histori)
        │  dibaca oleh
        ▼
FastAPI (Backend/api.py, port 8000)
  • baca snapshot TERBARU dari roblox.db (via snapshot_loader.py)
  • rekonstruksi umur_hari & playing_per_hari
  • panggil analysis.py
  • sajikan JSON (numpy → tipe native), CORS aktif untuk port 8001
        │  HTTP (JSON)
        ▼
Laravel (Frontend/, port 8001)
  • FastApiClient service memanggil FastAPI via Http::get
  • DashboardController → Blade (tab: Ringkasan · Saturasi · Viral Muda)
  • ApexCharts gambar grafik; tabel via komponen BlatUI
  • header tiap halaman menampilkan info snapshot
```

Dev: dua terminal manual — `uvicorn` (8000) & `php artisan serve --port=8001`.

---

## 4. Komponen Baru di Backend

### `Backend/snapshot_loader.py` (baru)
- `load_latest_as_df(conn) -> pd.DataFrame` — baca `games_snapshot` untuk `snapshot_id` terbaru; hitung ulang `umur_hari` = (updated − created).days dan `playing_per_hari` = playing / umur_hari (0 bila umur_hari 0). Mengembalikan DataFrame berisi kolom yang dibutuhkan `analysis.py`.
- `load_snapshot_meta(conn) -> dict | None` — ambil `snapshot_id`, `taken_at`, `game_count` dari snapshot terbaru; `None` bila tak ada snapshot.

**DRY:** rumus `umur_hari`/`playing_per_hari` diekstrak dari `cleaning.py` menjadi helper bersama (mis. `derive_columns(df)` di `cleaning.py`) yang dipakai baik oleh `clean_games` maupun `snapshot_loader`, agar rumus tidak diduplikasi.

### `Backend/api.py` (baru) — FastAPI
Dependensi baru: `fastapi`, `uvicorn` (tambah ke `Backend/requirements.txt`).

Endpoint (semua GET):

| Endpoint | Memakai | Respons JSON |
|---|---|---|
| `/api/snapshot` | `load_snapshot_meta` | `{"snapshot_id":3,"taken_at":"...","game_count":781}` |
| `/api/genre/ranking` | `genre_ranking`+`genre_share` | `{"ranking":[{"genreL1":..,"game_count":..,"avg_visits":..,"avg_playing":..,"avg_rating":..}],"share":{"<genre>":<pct>}}` |
| `/api/genre/saturation` | `saturation_flags` | `{"data":[{"genreL1":..,"game_count":..,"avg_playing":..,"status":"oversaturated|healthy|emerging"}]}` |
| `/api/viral-muda?max_umur=90` | `viral_muda` | `{"max_umur":90,"data":[{"name":..,"playing":..,"umur_hari":..,"playing_per_hari":..,"genreL1":..}]}` |

**Penanganan error & serialisasi:**
- DB kosong / belum ada snapshot → HTTP 404 `{"detail":"Belum ada snapshot. Jalankan ingest dulu."}` (bukan 500).
- Semua nilai numpy (`int64`/`float64`) dikonversi ke `int`/`float` native sebelum serialisasi (JSON valid).
- CORS: izinkan origin `http://127.0.0.1:8001` dan `http://localhost:8001`.
- DB path dari env var `ROBLOX_DB` (default `roblox.db` relatif ke Backend).

---

## 5. Frontend Laravel (`Frontend/`)

### Setup (sekali)
1. `composer create-project laravel/laravel .` di `Frontend/`.
2. `composer require anousss007/blatui` → `php artisan blatui:init`.
3. `php artisan blatui:add` komponen: card, table, badge, dan navigasi/tab.
4. `.env`: `FASTAPI_URL=http://127.0.0.1:8000`.
5. Build asset Tailwind (`npm install && npm run build` atau sesuai instruksi BlatUI).

### Routes & Controller

| Route | Method | Data diambil |
|---|---|---|
| `GET /` | `DashboardController@ringkasan` | `/api/snapshot` + `/api/genre/ranking` |
| `GET /saturasi` | `DashboardController@saturasi` | `/api/snapshot` + `/api/genre/saturation` |
| `GET /viral` | `DashboardController@viral` | `/api/snapshot` + `/api/viral-muda` |

### `App\Services\FastApiClient`
Bungkus semua panggilan HTTP ke FastAPI (Laravel `Http::get`). Tanggung jawab:
- Method: `snapshot()`, `genreRanking()`, `genreSaturation()`, `viralMuda(int $maxUmur = 90)`.
- Error handling: connection refused (FastAPI mati) → lempar/kembalikan penanda yang membuat controller merender banner "Server analisis tidak aktif — jalankan `uvicorn api:app`". HTTP 404 → banner "Belum ada data — jalankan ingest dulu".
- Base URL dari `config`/`.env` `FASTAPI_URL`.

**DRY:** info snapshot dibutuhkan semua halaman → tiap controller method memanggil `FastApiClient::snapshot()`; logika HTTP tidak diduplikasi karena terpusat di service.

### Views (Blade + BlatUI)
- `layouts/app.blade.php` — muat BlatUI/Tailwind/Alpine, nav tab, header info snapshot.
- `dashboard/ringkasan.blade.php` — pie chart share genre (ApexCharts) + bar chart ranking + tabel ranking (komponen table BlatUI).
- `dashboard/saturasi.blade.php` — bar chart per genre diwarnai status + tabel.
- `dashboard/viral.blade.php` — tabel game viral muda (BlatUI table), opsional bar chart playing_per_hari.

**ApexCharts membaca data:** controller kirim array PHP → Blade `@json($data)` ke variabel JS → ApexCharts render. Tabel di-loop server-side (tanpa JS).

---

## 6. Testing

- **`snapshot_loader.py`:** unit test dengan DB in-memory berisi snapshot dummy → verifikasi `umur_hari`/`playing_per_hari` dihitung benar, `load_snapshot_meta` mengembalikan snapshot terbaru & `None` saat kosong.
- **`api.py`:** pakai FastAPI `TestClient` — verifikasi tiap endpoint mengembalikan struktur JSON benar dari DB dummy; verifikasi 404 saat DB kosong; verifikasi tipe hasil native (bukan numpy) sehingga JSON serializable.
- **Refactor DRY `cleaning.py`:** setelah ekstrak `derive_columns`, jalankan ulang test cleaning Task 2 (harus tetap 7/7 hijau) — memastikan refactor tidak mengubah perilaku.
- **Laravel:** test ringan — `FastApiClient` di-test dengan `Http::fake()` (respons palsu) untuk verifikasi parsing + jalur error (connection refused → penanda banner, 404 → penanda banner). View di-smoke-test (route mengembalikan 200 saat FastAPI di-fake).

---

## 7. Risiko yang Diakui

1. **Dua server harus hidup bersamaan** — kalau salah satu mati, dashboard tak berfungsi. Dimitigasi dengan pesan error ramah (bukan crash) yang memberi tahu perintah yang harus dijalankan.
2. **Setup BlatUI menambah kompleksitas** (Tailwind v4 build + Alpine wiring). Untuk level menengah masih wajar; task setup dibuat eksplisit langkah demi langkah.
3. **Refactor `cleaning.py` menyentuh kode yang sudah di-merge** — risiko regresi. Dimitigasi: ekstraksi `derive_columns` murni, test Task 2 dijalankan ulang sebagai gerbang.
4. **BlatUI dependency pihak ketiga** (`anousss007/blatui`) — versi/ketersediaan bisa berubah. Karena kode komponen disalin ke proyek (bukan runtime), risiko lock-in rendah.
5. **CORS/port** — jika port bentrok dengan layanan lain, sesuaikan; didokumentasikan di README.

---

## 8. Struktur Folder Target

```
Analisis Trend Roblox/
├── Backend/
│   ├── snapshot_loader.py   (BARU — baca snapshot → DataFrame)
│   ├── api.py               (BARU — FastAPI)
│   ├── cleaning.py          (refactor: ekstrak derive_columns)
│   ├── db.py / ingest.py / analysis.py   (sudah ada)
│   ├── requirements.txt     (+ fastapi, uvicorn)
│   └── tests/
│       ├── test_snapshot_loader.py   (BARU)
│       └── test_api.py               (BARU)
├── Frontend/                (BARU — proyek Laravel)
│   ├── app/Services/FastApiClient.php
│   ├── app/Http/Controllers/DashboardController.php
│   ├── routes/web.php
│   └── resources/views/{layouts,dashboard}/...
└── docs/superpowers/specs/
    └── 2026-07-18-dashboard-fastapi-laravel-design.md
```
