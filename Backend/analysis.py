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
