# Desain: Data Pipeline & Analisis Genre Roblox

**Tanggal:** 2026-07-18
**Status:** Draft untuk review
**Cakupan:** Jalur B (fondasi data/snapshot) + Jalur A (analisis deskriptif genre)
**Di luar cakupan (fase lanjutan):** ARIMA/STL forecasting, joblib, FastAPI, Laravel + ApexCharts

---

## 1. Tujuan

Membangun pipeline data yang:
1. Menjalankan scraper Roblox yang sudah ada secara berkala.
2. Menyimpan hasil tiap run sebagai **snapshot berstempel waktu** (bukan menimpa) — ini fondasi wajib untuk forecasting nanti.
3. Menyediakan analisis deskriptif genre dari data terkini.

Tujuan strategis: menabung deret waktu mingguan sekarang, supaya dalam ~30 minggu ARIMA/STL punya bahan bakar.

---

## 2. Konteks & Kondisi Saat Ini

- **Data:** `Dataset/Dataset.csv` — 782 baris, 14 kolom (`uid, name, visits, playing, likes, genre, genreL1, created, updated, description, creator, playerCount, totalUpVotes, totalDownVotes`). Satu snapshot, diambil 2026-07-18.
- **Scraper:** `Dataset/roblox_scraper_final.py` — Playwright, scrape halaman discover + enrich via `games.roblox.com/v1/games`. Merge-aware (memuat CSV lama, tambah baru, enrich semua).
- **Lingkungan:** Python 3.14, venv di `Notebook/.venv`, sudah terpasang pandas 3.0.3, matplotlib, jupyter. **Playwright belum terverifikasi terpasang** (scraper memakainya).

### Masalah inti yang desain ini selesaikan
Scraper menyimpan dengan mode `"w"` ke `Dataset.csv` yang sama → **menimpa keadaan sebelumnya**. Nilai `visits`/`playing` minggu ini hilang saat run berikutnya. Ini membuat deret waktu (syarat ARIMA/STL) mustahil terbentuk. Desain ini membungkus scraper agar tiap run disimpan terpisah.

---

## 3. Arsitektur

```
roblox_scraper_final.py (ADA)
        │  dipanggil oleh
        ▼
Backend/ingest.py  ──►  simpan snapshot ke SQLite (snapshots + games_snapshot)
        │                                    │
        │                                    ▼
        │                          Backend/analysis.py (Jalur A)
        │                          agregasi genre dari snapshot terbaru
        ▼
(fase lanjutan) scheduler mingguan → ARIMA/STL → joblib → FastAPI → Laravel
```

Pembagian tanggung jawab:
- **Scraper** (tak diubah, atau diubah minimal): hasilkan CSV terkini.
- **`ingest.py`**: jalankan scraper → baca CSV hasil → simpan sebagai snapshot baru di SQLite.
- **`analysis.py`**: baca snapshot terbaru dari SQLite → hitung analisis deskriptif genre.

---

## 4. Skema Data (SQLite)

File: `Backend/roblox.db`

### Tabel `snapshots`
Satu baris per run scraper.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `snapshot_id` | INTEGER PK AUTOINCREMENT | ID snapshot |
| `taken_at` | TEXT (ISO 8601) | Waktu run (stempel waktu — kunci deret waktu) |
| `game_count` | INTEGER | Jumlah game di snapshot ini |
| `note` | TEXT | Catatan opsional (mis. "run manual") |

### Tabel `games_snapshot`
Satu baris per game **per snapshot**. Inilah yang menyimpan histori.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INTEGER PK AUTOINCREMENT | |
| `snapshot_id` | INTEGER FK → snapshots | Milik snapshot mana |
| `uid` | TEXT | ID game Roblox |
| `name` | TEXT | |
| `visits` | INTEGER | |
| `playing` | INTEGER | |
| `likes` | INTEGER | |
| `genre` | TEXT | |
| `genreL1` | TEXT | genre level atas (dipakai untuk analisis genre) |
| `created` | TEXT (ISO) | |
| `updated` | TEXT (ISO) | |
| `creator` | TEXT | |
| `playerCount` | INTEGER | |
| `totalUpVotes` | INTEGER | |
| `totalDownVotes` | INTEGER | |
| `rating` | REAL | dihitung: upVotes/(up+down)*100, 0 bila tanpa vote |

Indeks: `(snapshot_id)`, `(uid)`, `(genreL1)` untuk query cepat.

Catatan: `description` **tidak** disimpan ke DB (tak dipakai analisis; hemat ruang). Tetap ada di CSV.

---

## 5. Jalur B — Ingest & Snapshot

### `Backend/ingest.py`
Langkah:
1. (Opsional, via flag `--scrape`) jalankan `roblox_scraper_final.py` untuk hasilkan CSV terkini. Tanpa flag, pakai CSV yang ada.
2. Baca `Dataset/Dataset.csv` dengan pandas.
3. **Cleaning** (dari temuan notebook):
   - `created`, `updated` → datetime (`errors="coerce"`).
   - `genreL1` kosong → `"Unknown"` (ada 30 di data awal).
   - Hitung `rating`.
   - Buang duplikat `uid`.
   - Buang/laporkan baris dengan `umur_hari` negatif (data rusak: `updated` < `created`).
4. Buat baris baru di `snapshots` (`taken_at` = sekarang).
5. Simpan semua game ke `games_snapshot` dengan `snapshot_id` tsb.
6. Cetak ringkasan: jumlah game, genre teratas, berapa yang di-skip & alasannya.

**Prinsip:** idempoten per-CSV tapi append per-run. Menjalankan dua kali di hari sama = dua snapshot (itu wajar & sesuai desain, bukan bug).

### Penanganan error
- Scraper gagal (selector/URL Roblox berubah) → `ingest.py` deteksi CSV tak berubah/kosong → **batalkan snapshot**, laporkan jelas. Tidak menyimpan snapshot kosong.
- CSV rusak/kolom hilang → validasi kolom wajib di awal, hentikan dengan pesan spesifik.

---

## 6. Jalur A — Analisis Deskriptif Genre

### `Backend/analysis.py`
Membaca **snapshot terbaru** dari SQLite, menghasilkan (sebagai fungsi yang mengembalikan DataFrame/dict, agar mudah dipanggil FastAPI nanti):

1. **Ranking genre** — per `genreL1`: jumlah game, total & rata-rata `visits`, `playing`, `likes`, `rating`.
2. **Genre dominan** — komposisi jumlah game per genre (share %).
3. **Genre rating tinggi** — rata-rata `rating` per genre, terurut.
4. **Indikator oversaturated (heuristik 1-snapshot)** — genre dengan **banyak game** tapi **rata-rata playing per game rendah** = sesak/jenuh. Genre dengan sedikit game tapi playing tinggi = ruang tumbuh. (Ini proksi, bukan STL sejati — ditandai jelas di output.)
5. **Kandidat viral muda** — game `umur_hari < 90` dengan `playing_per_hari` tinggi (dari notebook Langkah 6).

**Catatan kejujuran:** poin 4 & 5 adalah heuristik dari satu titik waktu, **bukan** deteksi tren temporal. Output harus menyebut keterbatasan ini agar tidak disalahartikan sebagai forecasting.

---

## 7. Testing

- **`ingest.py`:** uji dengan CSV contoh kecil (3-5 game) → cek jumlah baris di `games_snapshot` benar, `rating` dihitung benar, duplikat `uid` terbuang, baris `umur_hari` negatif tertangani.
- **`analysis.py`:** uji dengan snapshot dummy berisi genre yang diketahui → cek ranking & share % sesuai hitungan manual.
- **Snapshot ganda:** ingest dua kali → pastikan ada 2 baris `snapshots` dan histori tidak saling menimpa.

---

## 8. Risiko yang Diakui

1. **Kerapuhan scraper:** bergantung struktur halaman & API Roblox yang bisa berubah tanpa pemberitahuan → scraper bisa rusak sewaktu-waktu. Mitigasi: `ingest.py` mendeteksi hasil kosong & tidak menyimpan snapshot rusak.
2. **Legal/ToS:** scraping otomatis berkala menyentuh area abu-abu Terms of Service Roblox. Ini proyek analisis pribadi; keputusan & risiko ada di pengguna. Didokumentasikan, bukan disembunyikan.
3. **Rate limit:** enrich API sudah menangani HTTP 429 dengan backoff; scheduler mingguan (bukan lebih sering) mengurangi risiko.
4. **Python 3.14 sangat baru:** Playwright/statsmodels mungkin belum sepenuhnya kompatibel. Perlu verifikasi saat implementasi.

---

## 9. Fase Lanjutan (di luar cakupan spec ini, dicatat agar arah tak hilang)

- **Scheduler mingguan** (Windows Task Scheduler) memanggil `ingest.py --scrape`.
- **ARIMA** — forecast popularitas per genre dari deret `taken_at`. Aktif setelah ~30 snapshot mingguan.
- **STL Decomposition** — pisah tren/musiman/residu per genre; deteksi naik/turun & emerging. Butuh ~2 siklus musiman.
- **joblib** — simpan model terlatih; FastAPI `joblib.load()` untuk melayani prediksi.
- **FastAPI** — ekspos analisis + prediksi sebagai REST JSON.
- **Laravel (Blade) + ApexCharts** — dashboard yang memanggil FastAPI.

---

## 10. Struktur Folder Target

```
Analisis Trend Roblox/
├── Dataset/
│   ├── Dataset.csv                 (output scraper terkini)
│   └── roblox_scraper_final.py     (scraper — sudah ada)
├── Backend/
│   ├── roblox.db                   (SQLite — snapshot histori)
│   ├── ingest.py                   (Jalur B)
│   ├── analysis.py                 (Jalur A)
│   └── tests/
├── Notebook/
│   └── cleaningData.ipynb          (eksplorasi — sudah ada)
├── Frontend/                       (Laravel — fase lanjutan)
└── docs/superpowers/specs/
    └── 2026-07-18-data-pipeline-analisis-genre-design.md
```
