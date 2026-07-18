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
