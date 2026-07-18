import pandas as pd
from analysis import genre_ranking, genre_share, saturation_flags, viral_muda


def _df():
    return pd.DataFrame([
        dict(genreL1="Adventure", visits=1000, playing=100, rating=90.0, umur_hari=200, playing_per_hari=0.5),
        dict(genreL1="Adventure", visits=2000, playing=300, rating=80.0, umur_hari=50,  playing_per_hari=6.0),
        dict(genreL1="Simulator", visits=500,  playing=10,  rating=70.0, umur_hari=400, playing_per_hari=0.02),
    ])


def test_genre_ranking_counts():
    r = genre_ranking(_df())
    adv = r[r["genreL1"] == "Adventure"].iloc[0]
    assert adv["game_count"] == 2
    assert adv["avg_playing"] == 200.0


def test_genre_share_sums_100():
    s = genre_share(_df())
    assert round(sum(s.values()), 1) == 100.0


def test_saturation_has_status_column():
    r = saturation_flags(_df())
    assert "status" in r.columns


def test_viral_muda_filters_young():
    r = viral_muda(_df(), max_umur=90)
    assert (r["umur_hari"] < 90).all()
    assert r.iloc[0]["playing_per_hari"] == 6.0  # tertinggi di urutan pertama
