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
