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
