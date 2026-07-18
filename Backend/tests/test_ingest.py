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
