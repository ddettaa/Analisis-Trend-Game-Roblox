#!/usr/bin/env python3
"""SUPERAGENT Roblox Scraper — FINAL STABLE
Merge-aware: loads existing CSV, adds new, enriches all.
Multi-run safe — never loses data.
"""
import json, csv, time, os
from collections import OrderedDict
from playwright.sync_api import sync_playwright

OUTPUT = "Dataset.csv"

# ─── LOAD EXISTING ───
all_games = OrderedDict()
if os.path.exists(OUTPUT):
    with open(OUTPUT) as f:
        for row in csv.DictReader(f):
            uid = row.get("uid", "")
            if uid:
                all_games[uid] = row
    print(f"📂 Loaded {len(all_games)} existing games")

# ─── SCRAPE ───
print("🚀 Scraping...")
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    page = browser.new_page(
        user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    )

    responses = []
    page.on("response", lambda r: responses.append(r.text())
            if "explore-api" in r.url and r.status == 200 else None)

    page.goto("https://www.roblox.com/discover/", timeout=20000, wait_until="networkidle")
    page.wait_for_timeout(3000)
    for _ in range(30):
        page.mouse.wheel(0, 2500)
        time.sleep(0.25)
    page.wait_for_timeout(2000)

    keywords = ["", "obby", "tycoon", "simulator", "rpg", "adventure", "horror",
                "anime", "parkour", "fighting", "racing", "fps", "survival",
                "dragon", "ninja", "school", "city", "battle", "puzzle", "indonesia"]
    
    for kw in keywords:
        try:
            url = f"https://www.roblox.com/discover/{'?Keyword='+kw if kw else ''}"
            page.goto(url, timeout=8000, wait_until="networkidle")
            page.wait_for_timeout(1500)
            for _ in range(4):
                page.mouse.wheel(0, 2000)
                time.sleep(0.2)
        except:
            continue

    page.wait_for_timeout(2000)

    # Parse
    print(f"📊 Parsing {len(responses)} API responses...")
    new_count = 0
    for body in responses:
        try:
            data = json.loads(body)
        except:
            continue

        stack = [data]
        while stack:
            obj = stack.pop()
            if isinstance(obj, dict):
                uid = obj.get("universeId")
                if uid:
                    uid = str(uid)
                    if uid not in all_games:
                        name = obj.get("name", "")
                        if name and len(name) > 2:
                            all_games[uid] = {
                                "uid": uid, "name": name,
                                "playerCount": obj.get("playerCount", 0),
                                "totalUpVotes": obj.get("totalUpVotes", 0),
                                "totalDownVotes": obj.get("totalDownVotes", 0),
                                "genreL1": obj.get("genreL1", ""),
                                "visits": "", "playing": "", "likes": "",
                                "genre": "", "created": "", "updated": "",
                                "description": "", "creator": "",
                            }
                            new_count += 1
                stack.extend(obj.values())
            elif isinstance(obj, list):
                stack.extend(obj)

    print(f"   +{new_count} new (total: {len(all_games)})")

    # Enrich
    needs = [uid for uid, g in all_games.items()
             if not g.get("visits") or str(g.get("visits","")).strip() in ("", "0")]

    print(f"🔄 Enriching {len(needs)}/{len(all_games)}...")
    
    e_ok = e_fail = 0
    for i in range(0, len(needs), 50):
        batch = needs[i:i+50]
        bstr = ",".join(batch)

        for attempt in range(4):
            try:
                result = page.evaluate(f"""async () => {{
                    const r = await fetch('https://games.roblox.com/v1/games?universeIds={bstr}');
                    const t = await r.text();
                    return {{status: r.status, body: t}};
                }}""")
                if result["status"] == 429:
                    time.sleep(5 * (attempt + 1))
                    continue
                if result["status"] != 200:
                    break

                resp = json.loads(result["body"])
                for g in (resp.get("data") or []):
                    uid = str(g["id"])
                    if uid in all_games:
                        v = g.get("visits", 0) or 0
                        if v > 0:
                            all_games[uid]["visits"] = str(v)
                            all_games[uid]["playing"] = str(g.get("playing", 0) or 0)
                            all_games[uid]["likes"] = str(g.get("favoritedCount", 0) or 0)
                            all_games[uid]["genre"] = g.get("genre", "")
                            all_games[uid]["created"] = g.get("created", "")
                            all_games[uid]["updated"] = g.get("updated", "")
                            all_games[uid]["description"] = (g.get("description") or "")[:200]
                            all_games[uid]["creator"] = (g.get("creator") or {}).get("name", "")
                            if g.get("name") and len(g.get("name", "")) > 3:
                                all_games[uid]["name"] = g["name"]
                e_ok += len(batch)
                break
            except:
                if attempt == 3:
                    e_fail += len(batch)
                time.sleep(3)

        time.sleep(0.3)
        if i % 500 == 0:
            print(f"   {min(i+50, len(needs))}/{len(needs)}")

    browser.close()
    print(f"   ✅ ok={e_ok} fail={e_fail}")

# ─── SAVE ───
fields = ["uid","name","visits","playing","likes","genre","genreL1","created",
          "updated","description","creator","playerCount","totalUpVotes","totalDownVotes"]
games_list = sorted(all_games.values(), key=lambda x: int(x.get("visits", 0) or 0), reverse=True)

with open(OUTPUT, "w", newline="", encoding="utf-8") as f:
    w = csv.DictWriter(f, fieldnames=fields, extrasaction="ignore")
    w.writeheader()
    w.writerows(games_list)

# ─── REPORT ───
total_v = sum(int(g.get("visits", 0) or 0) for g in games_list)
active = sum(1 for g in games_list if int(g.get("playing", 0) or 0) > 0)
unpopulated = sum(1 for g in games_list if not g.get("visits") or str(g.get("visits","")).strip() in ("", "0"))

print(f"\n✅ {len(games_list)} games | {total_v:,} visits | {active} live | {unpopulated} no-data")
print(f"   {OUTPUT}")

print(f"\n🏆 TOP 15:")
for i, g in enumerate(games_list[:15], 1):
    v = int(g.get("visits", 0) or 0)
    p = int(g.get("playing", 0) or 0)
    print(f"  {i:>2}. {g['name'][:50]:<55} {v:>15,} v | {p:>8,} p")
