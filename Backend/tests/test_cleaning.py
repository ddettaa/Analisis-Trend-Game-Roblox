import pandas as pd
from cleaning import clean_games, derive_columns


def _row(**kw):
    base = dict(
        uid="1", name="Game A", visits=1000, playing=100, likes=50,
        genre="RPG", genreL1="Adventure",
        created="2020-01-01T00:00:00.000Z", updated="2020-04-10T00:00:00.000Z",
        description="x", creator="c", playerCount=10,
        totalUpVotes=90, totalDownVotes=10,
    )
    base.update(kw)
    return base


def test_rating_computed():
    df = pd.DataFrame([_row(totalUpVotes=90, totalDownVotes=10)])
    clean, _ = clean_games(df)
    assert clean.iloc[0]["rating"] == 90.0


def test_rating_zero_when_no_votes():
    df = pd.DataFrame([_row(totalUpVotes=0, totalDownVotes=0)])
    clean, _ = clean_games(df)
    assert clean.iloc[0]["rating"] == 0.0


def test_genrel1_empty_filled_unknown():
    df = pd.DataFrame([_row(genreL1="")])
    clean, report = clean_games(df)
    assert clean.iloc[0]["genreL1"] == "Unknown"
    assert report["filled_genre"] == 1


def test_duplicate_uid_dropped():
    df = pd.DataFrame([_row(uid="1"), _row(uid="1")])
    clean, report = clean_games(df)
    assert len(clean) == 1
    assert report["dropped_duplicates"] == 1


def test_negative_age_dropped():
    df = pd.DataFrame([_row(
        created="2020-04-10T00:00:00.000Z", updated="2020-01-01T00:00:00.000Z"
    )])
    clean, report = clean_games(df)
    assert len(clean) == 0
    assert report["dropped_negative_age"] == 1


def test_invalid_date_dropped():
    df = pd.DataFrame([_row(updated="")])  # empty updated -> NaT
    clean, report = clean_games(df)
    assert len(clean) == 0
    assert report["dropped_invalid_date"] == 1


def test_umur_hari_is_int_dtype():
    df = pd.DataFrame([_row(), _row(uid="2")])
    clean, _ = clean_games(df)
    assert clean["umur_hari"].dtype.kind == "i"  # integer


def test_derive_columns_adds_umur_and_ppd():
    df = pd.DataFrame([_row(
        created="2020-01-01T00:00:00.000Z", updated="2020-01-11T00:00:00.000Z", playing=100
    )])
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)
    out = derive_columns(df)
    assert out.iloc[0]["umur_hari"] == 10
    assert out.iloc[0]["playing_per_hari"] == 10.0


def test_derive_columns_ppd_zero_when_umur_zero():
    df = pd.DataFrame([_row(
        created="2020-01-01T00:00:00.000Z", updated="2020-01-01T00:00:00.000Z", playing=100
    )])
    df["created"] = pd.to_datetime(df["created"], errors="coerce", utc=True)
    df["updated"] = pd.to_datetime(df["updated"], errors="coerce", utc=True)
    out = derive_columns(df)
    assert out.iloc[0]["playing_per_hari"] == 0.0
