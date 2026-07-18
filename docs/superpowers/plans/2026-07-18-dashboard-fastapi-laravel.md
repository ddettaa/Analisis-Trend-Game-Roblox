# Dashboard FastAPI + Laravel — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyajikan analisis genre Roblox sebagai dashboard web: FastAPI membaca snapshot terbaru dari SQLite dan mengekspos JSON; Laravel (Blade + BlatUI + ApexCharts) menampilkannya dalam halaman bertab.

**Architecture:** Dua server. FastAPI (`Backend/api.py`, :8000) membaca `roblox.db` via `snapshot_loader.py`, merekonstruksi kolom turunan, memanggil fungsi murni di `analysis.py`, dan mengembalikan JSON tipe-native. Laravel (`Frontend/`, :8001) memanggil FastAPI lewat `FastApiClient` service, merender Blade + ApexCharts, dan menampilkan pesan ramah bila FastAPI mati / snapshot kosong.

**Tech Stack:** Python 3.14 (pandas, FastAPI, uvicorn, pytest, TestClient), PHP 8.4 (Laravel, BlatUI = Blade+Alpine+Tailwind v4, ApexCharts).

## Global Constraints

- Backend venv: `Backend/.venv` (Python 3.14.4). Frontend: Laravel project di `Frontend/`.
- Python = otak (semua hitung); Laravel = wajah (hanya menampilkan JSON, tidak menghitung).
- FastAPI membaca snapshot TERBARU dari `roblox.db`; DB path dari env `ROBLOX_DB` (default `roblox.db` relatif ke Backend).
- `games_snapshot` TIDAK menyimpan `umur_hari`/`playing_per_hari` — direkonstruksi saat baca. Rumus turunan ada SATU sumber (`derive_columns` di `cleaning.py`), dipakai ulang (DRY) — jangan duplikasi rumus.
- Semua nilai numpy (`int64`/`float64`) dikonversi ke `int`/`float` native sebelum serialisasi JSON.
- FastAPI mengembalikan HTTP 404 `{"detail":"Belum ada snapshot. Jalankan ingest dulu."}` bila tak ada snapshot (bukan 500).
- CORS FastAPI mengizinkan `http://127.0.0.1:8001` dan `http://localhost:8001`.
- Ports: FastAPI 8000, Laravel 8001.
- Laravel `.env`: `FASTAPI_URL=http://127.0.0.1:8000`.
- Git identity: `git -c user.name="ddettaa" -c user.email="adityarahmann15@gmail.com" commit ...`
- Test dijalankan dari `Backend/`: `.venv/Scripts/python -m pytest ...`
- Kolom yang dibutuhkan analysis.py di DataFrame: `genreL1, visits, playing, rating, umur_hari, playing_per_hari` (+ `name` untuk viral_muda).

---

## File Structure

```
Backend/
├── requirements.txt        (Modify: + fastapi, uvicorn)
├── cleaning.py             (Modify: ekstrak derive_columns, dipakai clean_games)
├── snapshot_loader.py      (Create: baca snapshot terbaru → DataFrame + meta)
├── api.py                  (Create: FastAPI app, 4 endpoint)
└── tests/
    ├── test_cleaning.py    (Modify: pastikan tetap hijau setelah refactor)
    ├── test_snapshot_loader.py  (Create)
    └── test_api.py         (Create: TestClient)

Frontend/                   (Create: proyek Laravel)
├── .env                    (FASTAPI_URL)
├── app/Services/FastApiClient.php
├── app/Http/Controllers/DashboardController.php
├── routes/web.php
├── resources/views/layouts/app.blade.php
├── resources/views/dashboard/{ringkasan,saturasi,viral}.blade.php
└── tests/Feature/DashboardTest.php
```

**Dekomposisi:** Fase A (Task 1-4) = backend Python, teruji mandiri via TestClient tanpa Laravel. Fase B (Task 5-8) = Laravel, bergantung FastAPI. `snapshot_loader` dipisah dari `api` agar logika baca-DB teruji tanpa HTTP. `FastApiClient` dipisah dari controller agar jalur error teruji dengan `Http::fake()`.

---

# FASE A — Backend (FastAPI)

## Task 1: Refactor `derive_columns` di cleaning.py (DRY foundation)

**Files:**
- Modify: `Backend/cleaning.py`
- Modify: `Backend/tests/test_cleaning.py`

**Interfaces:**
- Produces: `derive_columns(df: pd.DataFrame) -> pd.DataFrame` — menerima df dengan `created`/`updated` (datetime) + `playing`, menambah `umur_hari` (int, setelah baris valid) dan `playing_per_hari` (float, 0 bila umur_hari 0). `clean_games` memanggil ini alih-alih menghitung inline.

- [ ] **Step 1: Tulis test baru untuk derive_columns**

Tambah ke `Backend/tests/test_cleaning.py`:
```python
from cleaning import derive_columns


def test_derive_columns_adds_umur_and_ppd():
    df = pd.DataFrame([_row(
        created="2020-01-01T00:00:00.000Z", updated="2020-01-11T00:00:00.000Z", playing=100
    )])
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)
    out = derive_columns(df)
    assert out.iloc[0]["umur_hari"] == 10
    assert out.iloc[0]["playing_per_hari"] == 10.0


def test_derive_columns_ppd_zero_when_umur_zero():
    df = pd.DataFrame([_row(
        created="2020-01-01T00:00:00.000Z", updated="2020-01-01T00:00:00.000Z", playing=100
    )])
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)
    out = derive_columns(df)
    assert out.iloc[0]["playing_per_hari"] == 0.0
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv/Scripts/python -m pytest tests/test_cleaning.py::test_derive_columns_adds_umur_and_ppd -v`
Expected: FAIL — `ImportError: cannot import name 'derive_columns'`

- [ ] **Step 3: Ekstrak derive_columns & pakai di clean_games**

Di `Backend/cleaning.py`, tambah fungsi baru dan panggil dari `clean_games`. `derive_columns` mengasumsikan `created`/`updated` sudah datetime dan baris tak-valid sudah dibuang (dipanggil setelah drop di clean_games):
```python
def derive_columns(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    df["umur_hari"] = (df["updated"] - df["created"]).dt.days.astype(int)
    df["playing_per_hari"] = (
        df["playing"] / df["umur_hari"].replace(0, pd.NA)
    ).fillna(0).round(2)
    return df
```
Lalu di `clean_games`, GANTI blok inline `umur_hari`/`playing_per_hari` yang ada sekarang. Pertahankan urutan: hitung `umur_hari` untuk filter negatif TETAP diperlukan sebelum drop. Pendekatan aman: hitung `umur_hari` mentah untuk filter negatif seperti sebelumnya, buang baris negatif, lalu panggil `derive_columns` untuk menghasilkan `umur_hari` (int) + `playing_per_hari` final. Pastikan hasil akhir identik dengan sebelum refactor (umur_hari int, ppd 0 saat umur 0).

- [ ] **Step 4: Jalankan SELURUH test cleaning, pastikan LULUS**

Run: `cd Backend && .venv/Scripts/python -m pytest tests/test_cleaning.py -v`
Expected: PASS — 9 passed (7 lama + 2 baru). Jika salah satu dari 7 lama gagal, refactor mengubah perilaku — perbaiki hingga semua hijau.

- [ ] **Step 5: Commit**

```bash
git add Backend/cleaning.py Backend/tests/test_cleaning.py
git commit -m "refactor: extract derive_columns for reuse (DRY)"
```

---

## Task 2: snapshot_loader.py

**Files:**
- Create: `Backend/snapshot_loader.py`
- Test: `Backend/tests/test_snapshot_loader.py`

**Interfaces:**
- Consumes: `db.get_connection`/`init_schema`; `ingest.save_snapshot` (untuk test fixture); `cleaning.derive_columns`.
- Produces:
  - `load_snapshot_meta(conn) -> dict | None` — `{"snapshot_id":int,"taken_at":str,"game_count":int}` untuk snapshot terbaru; `None` bila kosong.
  - `load_latest_as_df(conn) -> pd.DataFrame` — baris `games_snapshot` snapshot terbaru; parse `created`/`updated` ke datetime; panggil `derive_columns`; kembalikan df dengan kolom analysis (`genreL1,visits,playing,rating,umur_hari,playing_per_hari,name`). DataFrame kosong bila tak ada snapshot.

- [ ] **Step 1: Tulis test yang gagal**

Create `Backend/tests/test_snapshot_loader.py`:
```python
import pandas as pd
from db import init_schema
from ingest import save_snapshot
from cleaning import clean_games
from snapshot_loader import load_snapshot_meta, load_latest_as_df


def _seed(conn):
    df = pd.DataFrame([
        dict(uid="1", name="A", visits=1000, playing=100, likes=5, genre="g",
             genreL1="Adventure", created="2020-01-01T00:00:00.000Z",
             updated="2020-01-11T00:00:00.000Z", description="d", creator="c",
             playerCount=1, totalUpVotes=90, totalDownVotes=10),
    ])
    clean, _ = clean_games(df)
    return save_snapshot(conn, clean, "2026-07-18T10:00:00Z")


def test_meta_none_when_empty(conn):
    init_schema(conn)
    assert load_snapshot_meta(conn) is None


def test_meta_returns_latest(conn):
    init_schema(conn)
    _seed(conn)
    meta = load_snapshot_meta(conn)
    assert meta["game_count"] == 1
    assert meta["taken_at"] == "2026-07-18T10:00:00Z"


def test_load_df_reconstructs_derived_columns(conn):
    init_schema(conn)
    _seed(conn)
    df = load_latest_as_df(conn)
    assert len(df) == 1
    assert df.iloc[0]["umur_hari"] == 10
    assert df.iloc[0]["playing_per_hari"] == 10.0
    for col in ["genreL1", "visits", "playing", "rating", "umur_hari", "playing_per_hari", "name"]:
        assert col in df.columns


def test_load_df_empty_when_no_snapshot(conn):
    init_schema(conn)
    df = load_latest_as_df(conn)
    assert len(df) == 0
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv/Scripts/python -m pytest tests/test_snapshot_loader.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'snapshot_loader'`

- [ ] **Step 3: Implementasi snapshot_loader.py**

Create `Backend/snapshot_loader.py`:
```python
import pandas as pd
from cleaning import derive_columns


def load_snapshot_meta(conn) -> dict | None:
    row = conn.execute(
        "SELECT snapshot_id, taken_at, game_count FROM snapshots "
        "ORDER BY snapshot_id DESC LIMIT 1"
    ).fetchone()
    if row is None:
        return None
    return {"snapshot_id": row["snapshot_id"], "taken_at": row["taken_at"],
            "game_count": row["game_count"]}


def load_latest_as_df(conn) -> pd.DataFrame:
    meta = load_snapshot_meta(conn)
    if meta is None:
        return pd.DataFrame()
    rows = conn.execute(
        "SELECT * FROM games_snapshot WHERE snapshot_id = ?",
        (meta["snapshot_id"],),
    ).fetchall()
    df = pd.DataFrame([dict(r) for r in rows])
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)
    df = derive_columns(df)
    return df
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `cd Backend && .venv/Scripts/python -m pytest tests/test_snapshot_loader.py -v`
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add Backend/snapshot_loader.py Backend/tests/test_snapshot_loader.py
git commit -m "feat: add snapshot_loader to read latest snapshot as analysis DataFrame"
```

---

## Task 3: Install FastAPI + uvicorn

**Files:**
- Modify: `Backend/requirements.txt`

**Interfaces:**
- Produces: `fastapi` & `uvicorn` importable di `Backend/.venv`.

- [ ] **Step 1: Tambah dependency**

Tambah ke `Backend/requirements.txt`:
```
fastapi>=0.110
uvicorn>=0.29
httpx>=0.27
```
(`httpx` diperlukan oleh FastAPI `TestClient`.)

- [ ] **Step 2: Install**

Run: `cd Backend && .venv/Scripts/pip install -r requirements.txt`
Expected: fastapi, uvicorn, httpx terpasang tanpa error. Jika gagal di Python 3.14, laporkan error persisnya.

- [ ] **Step 3: Verifikasi import**

Run: `cd Backend && .venv/Scripts/python -c "import fastapi, uvicorn, httpx; from fastapi.testclient import TestClient; print('OK')"`
Expected: `OK`

- [ ] **Step 4: Commit**

```bash
git add Backend/requirements.txt
git commit -m "chore: add fastapi, uvicorn, httpx dependencies"
```

---

## Task 4: api.py — FastAPI endpoints

**Files:**
- Create: `Backend/api.py`
- Test: `Backend/tests/test_api.py`

**Interfaces:**
- Consumes: `db`, `snapshot_loader` (load_latest_as_df, load_snapshot_meta), `analysis` (genre_ranking, genre_share, saturation_flags, viral_muda).
- Produces: FastAPI `app` dengan 4 endpoint (lihat Global Constraints untuk bentuk JSON). Fungsi konversi numpy→native `_native(obj)` untuk membersihkan tipe.

- [ ] **Step 1: Tulis test yang gagal (TestClient)**

Create `Backend/tests/test_api.py`:
```python
import os
import json
import pandas as pd
import pytest
from fastapi.testclient import TestClient
from db import get_connection, init_schema
from ingest import save_snapshot
from cleaning import clean_games


@pytest.fixture
def client(tmp_path, monkeypatch):
    db_path = str(tmp_path / "test.db")
    monkeypatch.setenv("ROBLOX_DB", db_path)
    import importlib
    import api
    importlib.reload(api)  # re-read ROBLOX_DB
    return TestClient(api.app), db_path


def _seed(db_path):
    conn = get_connection(db_path)
    init_schema(conn)
    df = pd.DataFrame([
        dict(uid="1", name="A", visits=1000, playing=100, likes=5, genre="g",
             genreL1="Adventure", created="2020-01-01T00:00:00.000Z",
             updated="2020-06-01T00:00:00.000Z", description="d", creator="c",
             playerCount=1, totalUpVotes=90, totalDownVotes=10),
        dict(uid="2", name="B", visits=2000, playing=5, likes=9, genre="g",
             genreL1="Simulator", created="2021-01-01T00:00:00.000Z",
             updated="2021-06-01T00:00:00.000Z", description="d", creator="c",
             playerCount=2, totalUpVotes=50, totalDownVotes=50),
    ])
    clean, _ = clean_games(df)
    save_snapshot(conn, clean, "2026-07-18T10:00:00Z")
    conn.commit()


def test_snapshot_404_when_empty(client):
    c, db_path = client
    get_connection(db_path); init_schema(get_connection(db_path))
    r = c.get("/api/snapshot")
    assert r.status_code == 404


def test_snapshot_meta(client):
    c, db_path = client
    _seed(db_path)
    r = c.get("/api/snapshot")
    assert r.status_code == 200
    body = r.json()
    assert body["game_count"] == 2
    assert body["taken_at"] == "2026-07-18T10:00:00Z"


def test_genre_ranking_shape(client):
    c, db_path = client
    _seed(db_path)
    r = c.get("/api/genre/ranking")
    assert r.status_code == 200
    body = r.json()
    assert "ranking" in body and "share" in body
    # JSON serializable = tipe native (bukan numpy) — dibuktikan oleh .json() sukses
    assert isinstance(body["ranking"][0]["game_count"], int)


def test_saturation_has_status(client):
    c, db_path = client
    _seed(db_path)
    r = c.get("/api/genre/saturation")
    assert r.status_code == 200
    assert "status" in r.json()["data"][0]


def test_viral_muda_respects_param(client):
    c, db_path = client
    _seed(db_path)
    r = c.get("/api/viral-muda?max_umur=99999")
    assert r.status_code == 200
    assert r.json()["max_umur"] == 99999
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv/Scripts/python -m pytest tests/test_api.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'api'`

- [ ] **Step 3: Implementasi api.py**

Create `Backend/api.py`:
```python
import os
import numpy as np
import pandas as pd
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware

from db import get_connection
from snapshot_loader import load_latest_as_df, load_snapshot_meta
from analysis import genre_ranking, genre_share, saturation_flags, viral_muda

DB_PATH = os.environ.get("ROBLOX_DB", "roblox.db")

app = FastAPI(title="Roblox Trend API")
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://127.0.0.1:8001", "http://localhost:8001"],
    allow_methods=["GET"],
    allow_headers=["*"],
)


def _native(obj):
    if isinstance(obj, dict):
        return {k: _native(v) for k, v in obj.items()}
    if isinstance(obj, list):
        return [_native(v) for v in obj]
    if isinstance(obj, (np.integer,)):
        return int(obj)
    if isinstance(obj, (np.floating,)):
        return float(obj)
    return obj


def _df_records(df: pd.DataFrame) -> list:
    return _native(df.to_dict(orient="records"))


def _load_or_404():
    conn = get_connection(DB_PATH)
    meta = load_snapshot_meta(conn)
    if meta is None:
        raise HTTPException(status_code=404, detail="Belum ada snapshot. Jalankan ingest dulu.")
    return conn, meta


@app.get("/api/snapshot")
def snapshot():
    _, meta = _load_or_404()
    return _native(meta)


@app.get("/api/genre/ranking")
def genre_ranking_endpoint():
    conn, _ = _load_or_404()
    df = load_latest_as_df(conn)
    return {"ranking": _df_records(genre_ranking(df)), "share": _native(genre_share(df))}


@app.get("/api/genre/saturation")
def genre_saturation_endpoint():
    conn, _ = _load_or_404()
    df = load_latest_as_df(conn)
    return {"data": _df_records(saturation_flags(df))}


@app.get("/api/viral-muda")
def viral_muda_endpoint(max_umur: int = 90):
    conn, _ = _load_or_404()
    df = load_latest_as_df(conn)
    cols = ["name", "playing", "umur_hari", "playing_per_hari", "genreL1"]
    result = viral_muda(df, max_umur=max_umur)[cols]
    return {"max_umur": max_umur, "data": _df_records(result)}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `cd Backend && .venv/Scripts/python -m pytest tests/test_api.py -v`
Expected: PASS (5 passed)

- [ ] **Step 5: Jalankan seluruh suite backend**

Run: `cd Backend && .venv/Scripts/python -m pytest -v`
Expected: semua PASS (18 lama + 2 cleaning + 4 loader + 5 api).

- [ ] **Step 6: Bukti manual — jalankan server & cek satu endpoint**

Run (background):
```bash
cd Backend && ROBLOX_DB=roblox.db .venv/Scripts/python -m uvicorn api:app --port 8000 &
```
Lalu: `curl -s http://127.0.0.1:8000/api/snapshot`
Expected: JSON snapshot dengan `game_count` sekitar 781 (dari ingest nyata sebelumnya). Matikan server setelah verifikasi.

- [ ] **Step 7: Commit**

```bash
git add Backend/api.py Backend/tests/test_api.py
git commit -m "feat: add FastAPI endpoints for genre analysis with 404 + native JSON"
```

---

# FASE B — Frontend (Laravel)

## Task 5: Scaffold Laravel + BlatUI

**Files:**
- Create: `Frontend/` (proyek Laravel), `Frontend/.env` (append `FASTAPI_URL`)

**Interfaces:**
- Produces: proyek Laravel yang bisa `php artisan serve --port=8001` dan menampilkan halaman default; BlatUI terpasang.

- [ ] **Step 1: Buat proyek Laravel**

Run (dari root proyek):
```bash
cd Frontend && composer create-project laravel/laravel . 2>&1 | tail -5
```
Expected: proyek Laravel terbuat (folder `app/`, `routes/`, dll). Jika `Frontend/` tidak kosong dan menolak, laporkan.

- [ ] **Step 2: Set FASTAPI_URL**

Tambahkan baris ke `Frontend/.env`:
```
FASTAPI_URL=http://127.0.0.1:8000
```
Dan ke `Frontend/config/services.php` di dalam array `return [ ... ]`:
```php
'fastapi' => [
    'url' => env('FASTAPI_URL', 'http://127.0.0.1:8000'),
],
```

- [ ] **Step 3: Install BlatUI**

Run: `cd Frontend && composer require anousss007/blatui 2>&1 | tail -10 && php artisan blatui:init 2>&1 | tail -10`
Expected: paket terpasang, `blatui:init` mem-publish foundation. **Jika perintah/paket berbeda dari yang diharapkan** (nama paket salah, artisan command tak ada), STOP dan laporkan output persisnya sebagai BLOCKED — jangan menebak API BlatUI.

- [ ] **Step 4: Verifikasi server jalan**

Run: `cd Frontend && php artisan serve --port=8001 &` lalu `curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8001`
Expected: `200`. Matikan server setelah cek.

- [ ] **Step 5: Commit**

```bash
git add Frontend
git commit -m "feat: scaffold Laravel frontend with BlatUI"
```
> Catatan: pastikan root `.gitignore` atau `Frontend/.gitignore` (dibuat Laravel) mengabaikan `Frontend/vendor/` dan `Frontend/node_modules/`. Jangan commit vendor/node_modules.

---

## Task 6: FastApiClient service

**Files:**
- Create: `Frontend/app/Services/FastApiClient.php`
- Test: `Frontend/tests/Feature/FastApiClientTest.php`

**Interfaces:**
- Produces: `FastApiClient` dengan method `snapshot(): ?array`, `genreRanking(): array`, `genreSaturation(): array`, `viralMuda(int $maxUmur = 90): array`, dan status koneksi. Bila FastAPI tak terjangkau → melempar/menandai `unavailable`; bila 404 → menandai `no_data`.

- [ ] **Step 1: Tulis test dengan Http::fake()**

Create `Frontend/tests/Feature/FastApiClientTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Services\FastApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FastApiClientTest extends TestCase
{
    public function test_snapshot_parses_json(): void
    {
        Http::fake(['*/api/snapshot' => Http::response([
            'snapshot_id' => 3, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781,
        ], 200)]);
        $client = new FastApiClient();
        $this->assertSame(781, $client->snapshot()['game_count']);
    }

    public function test_404_marks_no_data(): void
    {
        Http::fake(['*/api/snapshot' => Http::response(['detail' => 'x'], 404)]);
        $client = new FastApiClient();
        $this->assertNull($client->snapshot());
        $this->assertSame('no_data', $client->status());
    }

    public function test_connection_error_marks_unavailable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $client = new FastApiClient();
        $this->assertNull($client->snapshot());
        $this->assertSame('unavailable', $client->status());
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Frontend && php artisan test --filter=FastApiClientTest`
Expected: FAIL — class `FastApiClient` tidak ada.

- [ ] **Step 3: Implementasi FastApiClient**

Create `Frontend/app/Services/FastApiClient.php`:
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class FastApiClient
{
    private string $base;
    private string $status = 'ok';

    public function __construct()
    {
        $this->base = rtrim(config('services.fastapi.url', 'http://127.0.0.1:8000'), '/');
    }

    public function status(): string
    {
        return $this->status;
    }

    private function get(string $path): ?array
    {
        try {
            $res = Http::timeout(5)->get($this->base . $path);
        } catch (ConnectionException $e) {
            $this->status = 'unavailable';
            return null;
        }
        if ($res->status() === 404) {
            $this->status = 'no_data';
            return null;
        }
        if (! $res->successful()) {
            $this->status = 'error';
            return null;
        }
        return $res->json();
    }

    public function snapshot(): ?array
    {
        return $this->get('/api/snapshot');
    }

    public function genreRanking(): array
    {
        return $this->get('/api/genre/ranking') ?? ['ranking' => [], 'share' => []];
    }

    public function genreSaturation(): array
    {
        return $this->get('/api/genre/saturation') ?? ['data' => []];
    }

    public function viralMuda(int $maxUmur = 90): array
    {
        return $this->get('/api/viral-muda?max_umur=' . $maxUmur) ?? ['max_umur' => $maxUmur, 'data' => []];
    }
}
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `cd Frontend && php artisan test --filter=FastApiClientTest`
Expected: PASS (3 passed)

- [ ] **Step 5: Commit**

```bash
git add Frontend/app/Services/FastApiClient.php Frontend/tests/Feature/FastApiClientTest.php
git commit -m "feat: add FastApiClient service with unavailable/no_data handling"
```

---

## Task 7: Routes, Controller, Layout

**Files:**
- Modify: `Frontend/routes/web.php`
- Create: `Frontend/app/Http/Controllers/DashboardController.php`
- Create: `Frontend/resources/views/layouts/app.blade.php`
- Test: `Frontend/tests/Feature/DashboardTest.php`

**Interfaces:**
- Consumes: `FastApiClient`.
- Produces: route `/`, `/saturasi`, `/viral` → 200. Tiap view menerima `$snapshot` (meta atau null), `$status`, dan data spesifik.

- [ ] **Step 1: Tulis feature test (smoke, dengan Http::fake)**

Create `Frontend/tests/Feature/DashboardTest.php`:
```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private function fakeAll(): void
    {
        Http::fake([
            '*/api/snapshot' => Http::response(['snapshot_id' => 1, 'taken_at' => '2026-07-18T10:00:00Z', 'game_count' => 781], 200),
            '*/api/genre/ranking' => Http::response(['ranking' => [['genreL1' => 'Adventure', 'game_count' => 10, 'avg_visits' => 1, 'avg_playing' => 1, 'avg_rating' => 90]], 'share' => ['Adventure' => 100.0]], 200),
            '*/api/genre/saturation' => Http::response(['data' => [['genreL1' => 'Adventure', 'game_count' => 10, 'avg_playing' => 1, 'status' => 'healthy']]], 200),
            '*/api/viral-muda*' => Http::response(['max_umur' => 90, 'data' => []], 200),
        ]);
    }

    public function test_ringkasan_page_ok(): void
    {
        $this->fakeAll();
        $this->get('/')->assertStatus(200)->assertsee('Adventure');
    }

    public function test_saturasi_page_ok(): void
    {
        $this->fakeAll();
        $this->get('/saturasi')->assertStatus(200);
    }

    public function test_viral_page_ok(): void
    {
        $this->fakeAll();
        $this->get('/viral')->assertStatus(200);
    }

    public function test_shows_banner_when_unavailable(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('refused');
        });
        $this->get('/')->assertStatus(200)->assertSee('uvicorn');
    }
}
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Frontend && php artisan test --filter=DashboardTest`
Expected: FAIL — route/view belum ada (404 atau view not found).

- [ ] **Step 3: Routes**

Ganti isi `Frontend/routes/web.php`:
```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'ringkasan']);
Route::get('/saturasi', [DashboardController::class, 'saturasi']);
Route::get('/viral', [DashboardController::class, 'viral']);
```

- [ ] **Step 4: Controller**

Create `Frontend/app/Http/Controllers/DashboardController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;

class DashboardController extends Controller
{
    public function ringkasan(FastApiClient $api)
    {
        $snapshot = $api->snapshot();
        $ranking = $api->genreRanking();
        return view('dashboard.ringkasan', [
            'snapshot' => $snapshot, 'status' => $api->status(), 'ranking' => $ranking,
        ]);
    }

    public function saturasi(FastApiClient $api)
    {
        $snapshot = $api->snapshot();
        $saturation = $api->genreSaturation();
        return view('dashboard.saturasi', [
            'snapshot' => $snapshot, 'status' => $api->status(), 'saturation' => $saturation,
        ]);
    }

    public function viral(FastApiClient $api)
    {
        $snapshot = $api->snapshot();
        $viral = $api->viralMuda(90);
        return view('dashboard.viral', [
            'snapshot' => $snapshot, 'status' => $api->status(), 'viral' => $viral,
        ]);
    }
}
```

- [ ] **Step 5: Layout dengan banner status + nav**

Create `Frontend/resources/views/layouts/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analisis Trend Roblox</title>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <nav>
        <a href="/">Ringkasan</a> |
        <a href="/saturasi">Saturasi</a> |
        <a href="/viral">Viral Muda</a>
    </nav>

    @if ($status === 'unavailable')
        <p style="color:red">Server analisis tidak aktif. Jalankan: <code>uvicorn api:app --port 8000</code> di folder Backend.</p>
    @elseif ($status === 'no_data')
        <p style="color:orange">Belum ada data snapshot. Jalankan ingest dulu.</p>
    @elseif ($snapshot)
        <p>Snapshot #{{ $snapshot['snapshot_id'] }} — {{ $snapshot['game_count'] }} game — {{ $snapshot['taken_at'] }}</p>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
```
> Catatan: ApexCharts di-load via CDN untuk kesederhanaan. BlatUI komponen (card/table) dipasang di Task 8; layout ini sengaja minimal agar smoke test lulus dulu.

- [ ] **Step 6: View minimal agar test lulus**

Create tiga view minimal supaya route mengembalikan 200 dan test `assertSee('Adventure')` lulus:

`Frontend/resources/views/dashboard/ringkasan.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1>Ringkasan Genre</h1>
    @foreach ($ranking['ranking'] as $row)
        <div>{{ $row['genreL1'] }} — {{ $row['game_count'] }} game</div>
    @endforeach
@endsection
```

`Frontend/resources/views/dashboard/saturasi.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1>Saturasi Genre</h1>
    @foreach ($saturation['data'] as $row)
        <div>{{ $row['genreL1'] }} — {{ $row['status'] }}</div>
    @endforeach
@endsection
```

`Frontend/resources/views/dashboard/viral.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1>Game Viral Muda</h1>
    @foreach ($viral['data'] as $row)
        <div>{{ $row['name'] }} — {{ $row['playing_per_hari'] }}/hari</div>
    @endforeach
@endsection
```

- [ ] **Step 7: Jalankan test, pastikan LULUS**

Run: `cd Frontend && php artisan test --filter=DashboardTest`
Expected: PASS (4 passed)

- [ ] **Step 8: Commit**

```bash
git add Frontend/routes/web.php Frontend/app/Http/Controllers/DashboardController.php Frontend/resources/views Frontend/tests/Feature/DashboardTest.php
git commit -m "feat: add dashboard routes, controller, layout with status banner"
```

---

## Task 8: Grafik ApexCharts + komponen BlatUI

**Files:**
- Modify: `Frontend/resources/views/dashboard/ringkasan.blade.php`
- Modify: `Frontend/resources/views/dashboard/saturasi.blade.php`
- Modify: `Frontend/resources/views/dashboard/viral.blade.php`

**Interfaces:**
- Consumes: data yang sama dari controller (Task 7). Tidak mengubah controller/route.

- [ ] **Step 1: Ringkasan — pie share + bar ranking + tabel**

Ganti `Frontend/resources/views/dashboard/ringkasan.blade.php` untuk menambah dua chart ApexCharts (share = pie, ranking playing = bar) plus tabel. Data dikirim ke JS via `@json`:
```blade
@extends('layouts.app')
@section('content')
    <h1>Ringkasan Genre</h1>
    <div id="sharechart"></div>
    <div id="rankingchart"></div>
    <table border="1">
        <thead><tr><th>Genre</th><th>Game</th><th>Avg Playing</th><th>Avg Rating</th></tr></thead>
        <tbody>
        @foreach ($ranking['ranking'] as $row)
            <tr><td>{{ $row['genreL1'] }}</td><td>{{ $row['game_count'] }}</td><td>{{ round($row['avg_playing']) }}</td><td>{{ round($row['avg_rating'], 1) }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <script>
        const share = @json($ranking['share']);
        const ranking = @json($ranking['ranking']);
        new ApexCharts(document.querySelector("#sharechart"), {
            chart: { type: 'pie', height: 320 },
            series: Object.values(share),
            labels: Object.keys(share),
            title: { text: 'Komposisi Genre (%)' }
        }).render();
        new ApexCharts(document.querySelector("#rankingchart"), {
            chart: { type: 'bar', height: 360 },
            series: [{ name: 'Avg Playing', data: ranking.map(r => Math.round(r.avg_playing)) }],
            xaxis: { categories: ranking.map(r => r.genreL1) },
            title: { text: 'Rata-rata Pemain Aktif per Genre' }
        }).render();
    </script>
@endsection
```

- [ ] **Step 2: Saturasi — bar diwarnai status + tabel**

Ganti `Frontend/resources/views/dashboard/saturasi.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1>Saturasi Genre</h1>
    <div id="satchart"></div>
    <table border="1">
        <thead><tr><th>Genre</th><th>Game</th><th>Avg Playing</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($saturation['data'] as $row)
            <tr><td>{{ $row['genreL1'] }}</td><td>{{ $row['game_count'] }}</td><td>{{ round($row['avg_playing']) }}</td><td>{{ $row['status'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <script>
        const sat = @json($saturation['data']);
        const colorFor = s => s === 'oversaturated' ? '#e74c3c' : (s === 'emerging' ? '#2ecc71' : '#95a5a6');
        new ApexCharts(document.querySelector("#satchart"), {
            chart: { type: 'bar', height: 360 },
            series: [{ name: 'Jumlah Game', data: sat.map(r => ({ x: r.genreL1, y: r.game_count, fillColor: colorFor(r.status) })) }],
            title: { text: 'Jumlah Game per Genre (warna = status saturasi)' }
        }).render();
    </script>
@endsection
```

- [ ] **Step 3: Viral — tabel + bar playing_per_hari**

Ganti `Frontend/resources/views/dashboard/viral.blade.php`:
```blade
@extends('layouts.app')
@section('content')
    <h1>Game Viral Muda (umur < 90 hari)</h1>
    <div id="viralchart"></div>
    <table border="1">
        <thead><tr><th>Nama</th><th>Playing</th><th>Umur (hari)</th><th>Playing/hari</th><th>Genre</th></tr></thead>
        <tbody>
        @foreach ($viral['data'] as $row)
            <tr><td>{{ $row['name'] }}</td><td>{{ $row['playing'] }}</td><td>{{ $row['umur_hari'] }}</td><td>{{ $row['playing_per_hari'] }}</td><td>{{ $row['genreL1'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <script>
        const viral = @json($viral['data']).slice(0, 15);
        new ApexCharts(document.querySelector("#viralchart"), {
            chart: { type: 'bar', height: 400 },
            plotOptions: { bar: { horizontal: true } },
            series: [{ name: 'Playing/hari', data: viral.map(r => r.playing_per_hari) }],
            xaxis: { categories: viral.map(r => r.name) },
            title: { text: 'Top 15 Kecepatan Pertumbuhan Pemain' }
        }).render();
    </script>
@endsection
```

- [ ] **Step 4: Jalankan feature test (tidak boleh regresi)**

Run: `cd Frontend && php artisan test --filter=DashboardTest`
Expected: PASS (4 passed) — chart tidak mengganggu smoke test karena `assertSee('Adventure')` tetap ada di tabel.

- [ ] **Step 5: Bukti manual end-to-end (dua server)**

Terminal 1: `cd Backend && ROBLOX_DB=roblox.db .venv/Scripts/python -m uvicorn api:app --port 8000`
Terminal 2: `cd Frontend && php artisan serve --port=8001`
Buka `http://127.0.0.1:8001/` — verifikasi pie/bar chart tampil, tabel terisi, header snapshot menunjukkan ~781 game. Cek `/saturasi` & `/viral`. **Ini bukti dashboard utuh bekerja.**

- [ ] **Step 6: Commit**

```bash
git add Frontend/resources/views/dashboard
git commit -m "feat: add ApexCharts visualizations to dashboard pages"
```

---

## Self-Review Notes

- **Spec §3 (arsitektur dua server + aliran):** Task 4 (FastAPI) + Task 5-7 (Laravel + FastApiClient) ✓.
- **Spec §4 (snapshot_loader, api, DRY derive_columns, 404, numpy→native, CORS):** Task 1 (derive_columns), Task 2 (loader), Task 4 (endpoints + _native + 404 + CORS) ✓.
- **Spec §5 (setup Laravel/BlatUI, routes, FastApiClient, views, ApexCharts):** Task 5 (scaffold), 6 (client), 7 (routes/controller/layout), 8 (charts) ✓.
- **Spec §6 (testing):** derive_columns re-run test Task 2 (Task 1 Step 4), snapshot_loader (Task 2), api TestClient + 404 + native (Task 4), FastApiClient Http::fake termasuk unavailable & 404 (Task 6), Laravel smoke (Task 7) ✓.
- **Spec §7 risiko:** dua-server error handling (banner, Task 7 Step 5/6), BlatUI setup eksplisit + STOP-jika-beda (Task 5 Step 3), refactor cleaning + gerbang test (Task 1 Step 4) ✓.
- **Placeholder scan:** semua step berisi kode nyata; tak ada TBD ✓.
- **Type consistency:** `derive_columns` (Task 1) dipakai loader (Task 2); struktur JSON endpoint (Task 4) cocok dengan yang di-fake & dibaca client/controller/view (Task 6-8): `ranking`/`share`, `data`+`status`, `max_umur`+`data` konsisten ✓.
- **Catatan ketidakpastian (bukan placeholder):** langkah persis BlatUI (`blatui:init`, `blatui:add`, kebutuhan `npm run build`) belum diverifikasi jalan; Task 5 Step 3 memerintahkan implementer STOP + lapor bila API BlatUI berbeda. Task 8 sengaja pakai ApexCharts via CDN (bukan komponen chart BlatUI) agar tidak bergantung detail BlatUI yang belum terverifikasi — komponen BlatUI (tabel/card) bisa ditambahkan sebagai polish setelah setup terbukti.
```
