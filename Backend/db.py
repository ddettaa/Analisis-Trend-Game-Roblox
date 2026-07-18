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
