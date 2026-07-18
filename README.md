# Analisis Trend Game Roblox

Pipeline data & analisis tren genre game Roblox: scraper → snapshot histori (SQLite) → analisis deskriptif genre, dengan fondasi untuk forecasting (ARIMA/STL) di fase lanjutan.

## Struktur

- `Dataset/` — scraper Playwright (`roblox_scraper_final.py`) & output CSV.
- `Backend/` — pipeline ingest + analisis (Python, SQLite).
- `Notebook/` — eksplorasi data (Jupyter).
- `Frontend/` — dashboard (Laravel — fase lanjutan).
- `docs/superpowers/` — spec & rencana implementasi.

## Status

Fase saat ini: data pipeline (Jalur B) + analisis deskriptif genre (Jalur A). Lihat `docs/superpowers/plans/`.

## Menjalankan

1. **Backend (FastAPI):**
   `cd Backend && .venv/Scripts/python -m uvicorn api:app --port 8000`
2. **Frontend (Laravel):** *(sekali saja: `cd Frontend && npm install && npm run build`)*
   `cd Frontend && php artisan serve --port=8001`
3. Buka `http://127.0.0.1:8001` — landing page; dashboard di `/dashboard`.
