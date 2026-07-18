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
