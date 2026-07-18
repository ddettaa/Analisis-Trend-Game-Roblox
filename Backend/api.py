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
