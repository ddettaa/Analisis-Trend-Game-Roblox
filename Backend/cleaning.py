import pandas as pd


def derive_columns(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    df["umur_hari"] = (df["updated"] - df["created"]).dt.days.astype(int)
    df["playing_per_hari"] = (
        df["playing"] / df["umur_hari"].replace(0, pd.NA)
    ).fillna(0).round(2)
    return df


def clean_games(df: pd.DataFrame) -> tuple[pd.DataFrame, dict]:
    df = df.copy()
    report = {
        "dropped_duplicates": 0,
        "dropped_negative_age": 0,
        "filled_genre": 0,
        "dropped_invalid_date": 0,
    }

    # genreL1 kosong -> Unknown
    empty_genre = df["genreL1"].isna() | (df["genreL1"].astype(str).str.strip() == "")
    report["filled_genre"] = int(empty_genre.sum())
    df.loc[empty_genre, "genreL1"] = "Unknown"

    # tanggal -> datetime
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)

    # buang duplikat uid
    before = len(df)
    df = df.drop_duplicates(subset="uid")
    report["dropped_duplicates"] = before - len(df)

    # buang baris dengan created/updated yang gagal diparse (NaT)
    before = len(df)
    invalid_date = df["created"].isna() | df["updated"].isna()
    df = df[~invalid_date]
    report["dropped_invalid_date"] = before - len(df)

    # rating
    total_votes = df["totalUpVotes"].fillna(0) + df["totalDownVotes"].fillna(0)
    df["rating"] = (df["totalUpVotes"].fillna(0) / total_votes * 100).round(2)
    df["rating"] = df["rating"].fillna(0.0)

    # umur_hari mentah & buang umur negatif (perlu sebelum drop)
    raw_umur_hari = (df["updated"] - df["created"]).dt.days
    before = len(df)
    df = df[~(raw_umur_hari < 0)]
    report["dropped_negative_age"] = before - len(df)

    # umur_hari (int) & playing_per_hari final, dihitung ulang lewat derive_columns
    # setelah baris NaT & negatif dibuang (aman untuk di-cast ke int)
    df = derive_columns(df)

    return df, report
