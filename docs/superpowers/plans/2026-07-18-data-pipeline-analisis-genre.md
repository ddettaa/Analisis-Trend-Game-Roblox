# Data Pipeline & Analisis Genre Roblox — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun pipeline yang menyimpan tiap run scraper Roblox sebagai snapshot berstempel waktu ke SQLite, lalu menghitung analisis deskriptif genre dari snapshot terbaru.

**Architecture:** `ingest.py` membaca CSV hasil scraper, membersihkan data, dan menyimpannya sebagai snapshot append-only di SQLite (dua tabel: `snapshots` + `games_snapshot`). `analysis.py` membaca snapshot terbaru dan mengembalikan agregasi genre sebagai struktur data siap-pakai (untuk FastAPI fase lanjutan). Fungsi murni dipisah dari I/O agar mudah dites.

**Tech Stack:** Python 3.14, pandas, sqlite3 (stdlib), pytest. Scraper: Playwright (sudah ada, di luar cakupan test).

## Global Constraints

- Python 3.14, venv di `Backend/.venv` (BARU — terpisah dari `Notebook/.venv`).
- Database: SQLite di `Backend/roblox.db`.
- Snapshot bersifat **append-only** — tidak pernah menimpa/menghapus snapshot lama.
- Kolom `description` TIDAK disimpan ke DB (sesuai spec §4).
- Semua fungsi analisis mengembalikan data (dict/DataFrame), bukan mencetak — agar dipakai ulang oleh FastAPI nanti.
- CSV sumber: `Dataset/Dataset.csv`, 14 kolom: `uid,name,visits,playing,likes,genre,genreL1,created,updated,description,creator,playerCount,totalUpVotes,totalDownVotes`.
- Baris dengan `updated` < `created` (umur negatif) = data rusak → dilaporkan & di-skip.
- `genreL1` kosong → `"Unknown"`. `rating` = upVotes/(up+down)*100, 0 bila tanpa vote.

---

## File Structure

```
Backend/
├── .venv/                    (venv baru untuk backend)
├── requirements.txt          (pandas, pytest, playwright)
├── db.py                     (koneksi + inisialisasi skema SQLite)
├── cleaning.py               (fungsi murni: bersihkan DataFrame — no I/O)
├── ingest.py                 (orkestrasi: CSV → clean → simpan snapshot)
├── analysis.py               (fungsi murni: agregasi genre dari DataFrame)
├── roblox.db                 (dibuat saat runtime)
└── tests/
    ├── conftest.py           (fixture: sample CSV & in-memory DB)
    ├── test_db.py
    ├── test_cleaning.py
    ├── test_ingest.py
    └── test_analysis.py
```

**Alasan dekomposisi:** `cleaning.py` dan `analysis.py` berisi fungsi murni (DataFrame masuk → DataFrame/dict keluar) yang paling mudah dites tanpa DB atau file. `db.py` mengisolasi skema. `ingest.py` cuma merangkai — logikanya dipinjam dari modul lain, jadi tesnya fokus ke integrasi (snapshot tersimpan benar).

---

## Task 0: Setup Environment Backend

**Files:**
- Create: `Backend/requirements.txt`

**Interfaces:**
- Produces: venv `Backend/.venv` dengan pandas, pytest, playwright terpasang.

- [ ] **Step 1: Buat requirements.txt**

Create `Backend/requirements.txt`:
```
pandas>=2.0
pytest>=8.0
playwright>=1.40
```

- [ ] **Step 2: Buat venv backend**

Run (PowerShell, dari root proyek):
```powershell
python -m venv Backend\.venv
Backend\.venv\Scripts\pip install -r Backend\requirements.txt
```
Expected: instalasi selesai tanpa error. Jika pandas/playwright gagal di Python 3.14, catat error dan laporkan — mungkin perlu turun ke Python 3.12 untuk backend.

- [ ] **Step 3: Verifikasi import**

Run:
```powershell
Backend\.venv\Scripts\python -c "import pandas, pytest, playwright; print('OK')"
```
Expected: `OK`

- [ ] **Step 4: Commit**

```bash
git add Backend/requirements.txt
git commit -m "chore: add backend requirements and venv setup"
```

> Catatan: `.venv/` sebaiknya masuk `.gitignore`. Jika belum ada `.gitignore` di root, buat berisi baris `.venv/` dan `*.db`.

---

## Task 1: Skema Database

**Files:**
- Create: `Backend/db.py`
- Test: `Backend/tests/test_db.py`
- Create: `Backend/tests/conftest.py`

**Interfaces:**
- Produces:
  - `get_connection(db_path: str) -> sqlite3.Connection`
  - `init_schema(conn: sqlite3.Connection) -> None` — buat tabel `snapshots` & `games_snapshot` jika belum ada (idempoten).

- [ ] **Step 1: Buat conftest.py dengan fixture DB in-memory**

Create `Backend/tests/conftest.py`:
```python
import sqlite3
import pytest


@pytest.fixture
def conn():
    c = sqlite3.connect(":memory:")
    c.row_factory = sqlite3.Row
    yield c
    c.close()
```

- [ ] **Step 2: Tulis test yang gagal**

Create `Backend/tests/test_db.py`:
```python
from db import init_schema


def test_init_schema_creates_tables(conn):
    init_schema(conn)
    names = {r["name"] for r in conn.execute(
        "SELECT name FROM sqlite_master WHERE type='table'"
    )}
    assert "snapshots" in names
    assert "games_snapshot" in names


def test_init_schema_idempotent(conn):
    init_schema(conn)
    init_schema(conn)  # tidak error saat dipanggil dua kali
    names = {r["name"] for r in conn.execute(
        "SELECT name FROM sqlite_master WHERE type='table'"
    )}
    assert "snapshots" in names
```

- [ ] **Step 3: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_db.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'db'`

- [ ] **Step 4: Implementasi db.py**

Create `Backend/db.py`:
```python
import sqlite3


def get_connection(db_path: str) -> sqlite3.Connection:
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    return conn


def init_schema(conn: sqlite3.Connection) -> None:
    conn.executescript(
        """
        CREATE TABLE IF NOT EXISTS snapshots (
            snapshot_id INTEGER PRIMARY KEY AUTOINCREMENT,
            taken_at    TEXT NOT NULL,
            game_count  INTEGER NOT NULL,
            note        TEXT
        );

        CREATE TABLE IF NOT EXISTS games_snapshot (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            snapshot_id   INTEGER NOT NULL REFERENCES snapshots(snapshot_id),
            uid           TEXT,
            name          TEXT,
            visits        INTEGER,
            playing       INTEGER,
            likes         INTEGER,
            genre         TEXT,
            genreL1       TEXT,
            created       TEXT,
            updated       TEXT,
            creator       TEXT,
            playerCount   INTEGER,
            totalUpVotes  INTEGER,
            totalDownVotes INTEGER,
            rating        REAL
        );

        CREATE INDEX IF NOT EXISTS idx_gs_snapshot ON games_snapshot(snapshot_id);
        CREATE INDEX IF NOT EXISTS idx_gs_uid      ON games_snapshot(uid);
        CREATE INDEX IF NOT EXISTS idx_gs_genrel1  ON games_snapshot(genreL1);
        """
    )
    conn.commit()
```

- [ ] **Step 5: Jalankan test, pastikan LULUS**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_db.py -v`
Expected: PASS (2 passed)

- [ ] **Step 6: Commit**

```bash
git add Backend/db.py Backend/tests/test_db.py Backend/tests/conftest.py
git commit -m "feat: add SQLite schema for snapshots and games_snapshot"
```

---

## Task 2: Cleaning Data (fungsi murni)

**Files:**
- Create: `Backend/cleaning.py`
- Test: `Backend/tests/test_cleaning.py`

**Interfaces:**
- Consumes: pandas DataFrame mentah dari CSV.
- Produces:
  - `clean_games(df: pd.DataFrame) -> tuple[pd.DataFrame, dict]`
    - Return: (DataFrame bersih, laporan dict `{"dropped_duplicates": int, "dropped_negative_age": int, "filled_genre": int}`)
    - DataFrame bersih punya kolom tambahan: `rating` (float), `umur_hari` (int), `playing_per_hari` (float).
    - `genreL1` kosong → `"Unknown"`. `created`/`updated` → datetime. Duplikat `uid` & umur negatif dibuang.

- [ ] **Step 1: Tulis test yang gagal**

Create `Backend/tests/test_cleaning.py`:
```python
import pandas as pd
from cleaning import clean_games


def _row(**kw):
    base = dict(
        uid="1", name="Game A", visits=1000, playing=100, likes=50,
        genre="RPG", genreL1="Adventure",
        created="2020-01-01T00:00:00.000Z", updated="2020-04-10T00:00:00.000Z",
        description="x", creator="c", playerCount=10,
        totalUpVotes=90, totalDownVotes=10,
    )
    base.update(kw)
    return base


def test_rating_computed():
    df = pd.DataFrame([_row(totalUpVotes=90, totalDownVotes=10)])
    clean, _ = clean_games(df)
    assert clean.iloc[0]["rating"] == 90.0


def test_rating_zero_when_no_votes():
    df = pd.DataFrame([_row(totalUpVotes=0, totalDownVotes=0)])
    clean, _ = clean_games(df)
    assert clean.iloc[0]["rating"] == 0.0


def test_genrel1_empty_filled_unknown():
    df = pd.DataFrame([_row(genreL1="")])
    clean, report = clean_games(df)
    assert clean.iloc[0]["genreL1"] == "Unknown"
    assert report["filled_genre"] == 1


def test_duplicate_uid_dropped():
    df = pd.DataFrame([_row(uid="1"), _row(uid="1")])
    clean, report = clean_games(df)
    assert len(clean) == 1
    assert report["dropped_duplicates"] == 1


def test_negative_age_dropped():
    df = pd.DataFrame([_row(
        created="2020-04-10T00:00:00.000Z", updated="2020-01-01T00:00:00.000Z"
    )])
    clean, report = clean_games(df)
    assert len(clean) == 0
    assert report["dropped_negative_age"] == 1
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_cleaning.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'cleaning'`

- [ ] **Step 3: Implementasi cleaning.py**

Create `Backend/cleaning.py`:
```python
import pandas as pd


def clean_games(df: pd.DataFrame) -> tuple[pd.DataFrame, dict]:
    df = df.copy()
    report = {"dropped_duplicates": 0, "dropped_negative_age": 0, "filled_genre": 0}

    # genreL1 kosong -> Unknown
    empty_genre = df["genreL1"].isna() | (df["genreL1"].astype(str).str.strip() == "")
    report["filled_genre"] = int(empty_genre.sum())
    df.loc[empty_genre, "genreL1"] = "Unknown"

    # tanggal -> datetime
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)

    # buang duplikat uid
    before = len(df)
    df = df.drop_duplicates(subset="uid")
    report["dropped_duplicates"] = before - len(df)

    # rating
    total_votes = df["totalUpVotes"].fillna(0) + df["totalDownVotes"].fillna(0)
    df["rating"] = (df["totalUpVotes"].fillna(0) / total_votes * 100).round(2)
    df["rating"] = df["rating"].fillna(0.0)

    # umur_hari & buang umur negatif
    df["umur_hari"] = (df["updated"] - df["created"]).dt.days
    before = len(df)
    df = df[~(df["umur_hari"] < 0)]
    report["dropped_negative_age"] = before - len(df)

    # playing_per_hari (hindari bagi nol -> 0)
    df["playing_per_hari"] = (
        df["playing"] / df["umur_hari"].replace(0, pd.NA)
    ).fillna(0).round(2)

    return df, report
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_cleaning.py -v`
Expected: PASS (5 passed)

- [ ] **Step 5: Commit**

```bash
git add Backend/cleaning.py Backend/tests/test_cleaning.py
git commit -m "feat: add pure data cleaning function with report"
```

---

## Task 3: Ingest — Simpan Snapshot

**Files:**
- Create: `Backend/ingest.py`
- Test: `Backend/tests/test_ingest.py`

**Interfaces:**
- Consumes: `get_connection`, `init_schema` (Task 1); `clean_games` (Task 2).
- Produces:
  - `save_snapshot(conn, df_clean: pd.DataFrame, taken_at: str, note: str = "") -> int` — return `snapshot_id` baru. Menulis 1 baris `snapshots` + N baris `games_snapshot`.
  - `latest_snapshot_id(conn) -> int | None`
  - `ingest_csv(conn, csv_path: str, taken_at: str | None = None) -> dict` — orkestrasi: baca CSV → `clean_games` → `save_snapshot`. Return laporan gabungan. Raise `ValueError` jika CSV kosong / kolom wajib hilang.

- [ ] **Step 1: Tulis test yang gagal**

Create `Backend/tests/test_ingest.py`:
```python
import pandas as pd
import pytest
from db import init_schema
from ingest import save_snapshot, latest_snapshot_id, ingest_csv
from cleaning import clean_games


def _sample_df():
    return pd.DataFrame([
        dict(uid="1", name="A", visits=1000, playing=100, likes=5, genre="g",
             genreL1="Adventure", created="2020-01-01T00:00:00.000Z",
             updated="2020-06-01T00:00:00.000Z", description="d", creator="c",
             playerCount=1, totalUpVotes=90, totalDownVotes=10),
        dict(uid="2", name="B", visits=2000, playing=200, likes=9, genre="g",
             genreL1="Simulator", created="2021-01-01T00:00:00.000Z",
             updated="2021-06-01T00:00:00.000Z", description="d", creator="c",
             playerCount=2, totalUpVotes=50, totalDownVotes=50),
    ])


def test_save_snapshot_writes_rows(conn):
    init_schema(conn)
    clean, _ = clean_games(_sample_df())
    sid = save_snapshot(conn, clean, "2026-07-18T10:00:00Z")
    snap = conn.execute("SELECT * FROM snapshots WHERE snapshot_id=?", (sid,)).fetchone()
    assert snap["game_count"] == 2
    rows = conn.execute("SELECT COUNT(*) c FROM games_snapshot WHERE snapshot_id=?", (sid,)).fetchone()
    assert rows["c"] == 2


def test_description_not_stored(conn):
    init_schema(conn)
    clean, _ = clean_games(_sample_df())
    save_snapshot(conn, clean, "2026-07-18T10:00:00Z")
    cols = {r["name"] for r in conn.execute("PRAGMA table_info(games_snapshot)")}
    assert "description" not in cols


def test_append_only_two_snapshots(conn):
    init_schema(conn)
    clean, _ = clean_games(_sample_df())
    sid1 = save_snapshot(conn, clean, "2026-07-18T10:00:00Z")
    sid2 = save_snapshot(conn, clean, "2026-07-25T10:00:00Z")
    assert sid1 != sid2
    count = conn.execute("SELECT COUNT(*) c FROM snapshots").fetchone()["c"]
    assert count == 2
    assert latest_snapshot_id(conn) == sid2


def test_ingest_csv_empty_raises(conn, tmp_path):
    init_schema(conn)
    p = tmp_path / "empty.csv"
    p.write_text("uid,name,visits,playing,likes,genre,genreL1,created,updated,description,creator,playerCount,totalUpVotes,totalDownVotes\n")
    with pytest.raises(ValueError):
        ingest_csv(conn, str(p))
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_ingest.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'ingest'`

- [ ] **Step 3: Implementasi ingest.py**

Create `Backend/ingest.py`:
```python
from datetime import datetime, timezone
import pandas as pd
from db import get_connection, init_schema
from cleaning import clean_games

REQUIRED_COLS = [
    "uid", "name", "visits", "playing", "likes", "genre", "genreL1",
    "created", "updated", "description", "creator", "playerCount",
    "totalUpVotes", "totalDownVotes",
]

DB_COLS = [
    "uid", "name", "visits", "playing", "likes", "genre", "genreL1",
    "created", "updated", "creator", "playerCount",
    "totalUpVotes", "totalDownVotes", "rating",
]


def save_snapshot(conn, df_clean: pd.DataFrame, taken_at: str, note: str = "") -> int:
    cur = conn.execute(
        "INSERT INTO snapshots (taken_at, game_count, note) VALUES (?, ?, ?)",
        (taken_at, len(df_clean), note),
    )
    sid = cur.lastrowid
    for _, r in df_clean.iterrows():
        vals = [sid] + [
            (str(r[c]) if c in ("created", "updated") else r[c])
            for c in DB_COLS
        ]
        conn.execute(
            f"INSERT INTO games_snapshot (snapshot_id, {', '.join(DB_COLS)}) "
            f"VALUES ({', '.join(['?'] * (len(DB_COLS) + 1))})",
            vals,
        )
    conn.commit()
    return sid


def latest_snapshot_id(conn):
    row = conn.execute("SELECT MAX(snapshot_id) m FROM snapshots").fetchone()
    return row["m"] if row and row["m"] is not None else None


def ingest_csv(conn, csv_path: str, taken_at: str | None = None) -> dict:
    df = pd.read_csv(csv_path)
    missing = [c for c in REQUIRED_COLS if c not in df.columns]
    if missing:
        raise ValueError(f"Kolom wajib hilang: {missing}")
    if len(df) == 0:
        raise ValueError("CSV kosong — snapshot dibatalkan.")

    clean, report = clean_games(df)
    if len(clean) == 0:
        raise ValueError("Tidak ada baris valid setelah cleaning — snapshot dibatalkan.")

    taken_at = taken_at or datetime.now(timezone.utc).isoformat()
    sid = save_snapshot(conn, clean, taken_at)
    report["snapshot_id"] = sid
    report["saved_rows"] = len(clean)
    return report


if __name__ == "__main__":
    import sys
    csv = sys.argv[1] if len(sys.argv) > 1 else "../Dataset/Dataset.csv"
    conn = get_connection("roblox.db")
    init_schema(conn)
    rep = ingest_csv(conn, csv)
    print(f"Snapshot #{rep['snapshot_id']}: {rep['saved_rows']} game disimpan. Detail: {rep}")
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_ingest.py -v`
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add Backend/ingest.py Backend/tests/test_ingest.py
git commit -m "feat: add append-only snapshot ingest with CSV validation"
```

---

## Task 4: Analisis Deskriptif Genre (fungsi murni)

**Files:**
- Create: `Backend/analysis.py`
- Test: `Backend/tests/test_analysis.py`

**Interfaces:**
- Consumes: DataFrame hasil `clean_games` (punya kolom `genreL1`, `visits`, `playing`, `rating`, `umur_hari`, `playing_per_hari`).
- Produces:
  - `genre_ranking(df) -> pd.DataFrame` — per `genreL1`: `game_count`, `avg_visits`, `avg_playing`, `avg_rating`, terurut `game_count` desc.
  - `genre_share(df) -> dict[str, float]` — share % jumlah game per genre.
  - `saturation_flags(df) -> pd.DataFrame` — per genre: `game_count`, `avg_playing`, kolom `status` ("oversaturated" bila game_count di atas median DAN avg_playing di bawah median; selain itu "healthy"/"emerging").
  - `viral_muda(df, max_umur=90) -> pd.DataFrame` — game `umur_hari < max_umur` terurut `playing_per_hari` desc.

- [ ] **Step 1: Tulis test yang gagal**

Create `Backend/tests/test_analysis.py`:
```python
import pandas as pd
from analysis import genre_ranking, genre_share, saturation_flags, viral_muda


def _df():
    return pd.DataFrame([
        dict(genreL1="Adventure", visits=1000, playing=100, rating=90.0, umur_hari=200, playing_per_hari=0.5),
        dict(genreL1="Adventure", visits=2000, playing=300, rating=80.0, umur_hari=50,  playing_per_hari=6.0),
        dict(genreL1="Simulator", visits=500,  playing=10,  rating=70.0, umur_hari=400, playing_per_hari=0.02),
    ])


def test_genre_ranking_counts():
    r = genre_ranking(_df())
    adv = r[r["genreL1"] == "Adventure"].iloc[0]
    assert adv["game_count"] == 2
    assert adv["avg_playing"] == 200.0


def test_genre_share_sums_100():
    s = genre_share(_df())
    assert round(sum(s.values()), 1) == 100.0


def test_saturation_has_status_column():
    r = saturation_flags(_df())
    assert "status" in r.columns


def test_viral_muda_filters_young():
    r = viral_muda(_df(), max_umur=90)
    assert (r["umur_hari"] < 90).all()
    assert r.iloc[0]["playing_per_hari"] == 6.0  # tertinggi di urutan pertama
```

- [ ] **Step 2: Jalankan test, pastikan GAGAL**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_analysis.py -v`
Expected: FAIL — `ModuleNotFoundError: No module named 'analysis'`

- [ ] **Step 3: Implementasi analysis.py**

Create `Backend/analysis.py`:
```python
import pandas as pd


def genre_ranking(df: pd.DataFrame) -> pd.DataFrame:
    g = df.groupby("genreL1").agg(
        game_count=("genreL1", "size"),
        avg_visits=("visits", "mean"),
        avg_playing=("playing", "mean"),
        avg_rating=("rating", "mean"),
    ).reset_index()
    return g.sort_values("game_count", ascending=False).reset_index(drop=True)


def genre_share(df: pd.DataFrame) -> dict:
    counts = df["genreL1"].value_counts()
    total = counts.sum()
    return {k: round(v / total * 100, 2) for k, v in counts.items()}


def saturation_flags(df: pd.DataFrame) -> pd.DataFrame:
    g = df.groupby("genreL1").agg(
        game_count=("genreL1", "size"),
        avg_playing=("playing", "mean"),
    ).reset_index()
    med_count = g["game_count"].median()
    med_playing = g["avg_playing"].median()

    def _status(row):
        if row["game_count"] > med_count and row["avg_playing"] < med_playing:
            return "oversaturated"
        if row["game_count"] <= med_count and row["avg_playing"] >= med_playing:
            return "emerging"
        return "healthy"

    g["status"] = g.apply(_status, axis=1)
    return g.sort_values("game_count", ascending=False).reset_index(drop=True)


def viral_muda(df: pd.DataFrame, max_umur: int = 90) -> pd.DataFrame:
    young = df[df["umur_hari"] < max_umur].copy()
    return young.sort_values("playing_per_hari", ascending=False).reset_index(drop=True)
```

- [ ] **Step 4: Jalankan test, pastikan LULUS**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_analysis.py -v`
Expected: PASS (4 passed)

- [ ] **Step 5: Commit**

```bash
git add Backend/analysis.py Backend/tests/test_analysis.py
git commit -m "feat: add descriptive genre analysis functions"
```

---

## Task 5: Integrasi End-to-End dengan Data Nyata

**Files:**
- Test: `Backend/tests/test_ingest.py` (tambah test integrasi)

**Interfaces:**
- Consumes: semua modul sebelumnya + `Dataset/Dataset.csv` nyata.

- [ ] **Step 1: Tambah test integrasi dengan CSV nyata**

Append ke `Backend/tests/test_ingest.py`:
```python
import os


def test_real_csv_ingest_and_analyze(conn):
    csv = os.path.join(os.path.dirname(__file__), "..", "..", "Dataset", "Dataset.csv")
    if not os.path.exists(csv):
        import pytest
        pytest.skip("Dataset.csv tidak ditemukan")
    init_schema(conn)
    report = ingest_csv(conn, csv)
    assert report["saved_rows"] > 500        # ~782 dikurangi baris rusak
    assert report["snapshot_id"] == 1
```

- [ ] **Step 2: Jalankan test integrasi**

Run: `cd Backend && .venv\Scripts\python -m pytest tests/test_ingest.py::test_real_csv_ingest_and_analyze -v`
Expected: PASS (atau SKIP bila CSV tak ada)

- [ ] **Step 3: Jalankan ingest sungguhan & verifikasi DB**

Run:
```powershell
cd Backend
.venv\Scripts\python ingest.py ..\Dataset\Dataset.csv
.venv\Scripts\python -c "import db; c=db.get_connection('roblox.db'); print('snapshots:', c.execute('SELECT COUNT(*) FROM snapshots').fetchone()[0]); print('games:', c.execute('SELECT COUNT(*) FROM games_snapshot').fetchone()[0])"
```
Expected: `snapshots: 1`, `games: >500`. **Ini bukti pipeline bekerja end-to-end pada data nyata.**

- [ ] **Step 4: Jalankan seluruh test suite**

Run: `cd Backend && .venv\Scripts\python -m pytest -v`
Expected: semua PASS.

- [ ] **Step 5: Commit**

```bash
git add Backend/tests/test_ingest.py
git commit -m "test: add end-to-end integration test with real dataset"
```

---

## Self-Review Notes

- **Spec §4 (skema):** Task 1 ✓. `description` dikecualikan ✓ (diuji di Task 3 Step 1).
- **Spec §5 (ingest, cleaning, error handling):** Task 2 (cleaning) + Task 3 (ingest, validasi CSV kosong/kolom hilang) ✓.
- **Spec §6 (analisis A):** Task 4 — ranking, share, saturation heuristik, viral_muda ✓. (Poin "genre rating tinggi" tercakup di `avg_rating` pada `genre_ranking`.)
- **Spec §7 (testing):** tiap task punya unit test; Task 5 integrasi + snapshot ganda (Task 3 `test_append_only_two_snapshots`) ✓.
- **Append-only (Global Constraint):** diuji `test_append_only_two_snapshots` ✓.
- **Placeholder scan:** tak ada TBD/TODO; semua step berisi kode nyata ✓.
- **Type consistency:** `clean_games` return `(df, dict)` dipakai konsisten di Task 3 & referensi kolom (`umur_hari`, `playing_per_hari`, `rating`) cocok antar Task 2/4 ✓.
- **Di luar cakupan (dicatat):** scraper flag `--scrape` tidak diimplementasikan di plan ini (spec menandai scraper dipanggil terpisah; `ingest.py` fokus CSV→DB). ARIMA/STL/FastAPI/Laravel = fase lanjutan.
