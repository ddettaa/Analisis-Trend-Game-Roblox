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
