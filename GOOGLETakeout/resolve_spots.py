#!/usr/bin/env python3
"""
Resolve Google Takeout Saved CSVs → spots JSON for WordPress import.

- Prefer lat/lon already present in /maps/search/URL
- Otherwise geocode place URLs via Nominatim (list-biased) then Google Maps tbm
- Reject London default-centroid false positives
- Deduplicate by rounded coordinates across lists
"""

from __future__ import annotations

import csv
import json
import math
import re
import time
import urllib.parse
import urllib.request
from pathlib import Path

BASE = Path(__file__).resolve().parent / "Saved"
OUT = Path(__file__).resolve().parent / "resolved-spots.json"

CSV_FILES = [
    "PK spots - London bridge.csv",
    "PK Spots - Old Street.csv",
    "PK Spots.csv",
    "Undercover.csv",
]

LIST_META = {
    "PK spots - London bridge.csv": {
        "slug": "london-bridge",
        "label": "London Bridge",
        "viewbox": (-0.13, 51.48, -0.04, 51.52),  # W,S,E,N
        "center": (51.504, -0.086),
        "max_km": 4.0,
    },
    "PK Spots - Old Street.csv": {
        "slug": "old-street",
        "label": "Old Street",
        "viewbox": (-0.13, 51.51, -0.06, 51.54),
        "center": (51.526, -0.088),
        "max_km": 4.0,
    },
    "PK Spots.csv": {
        "slug": "pk-spots",
        "label": "PK Spots",
        "viewbox": (-0.40, 51.30, 0.25, 51.70),
        "center": (51.51, -0.10),
        "max_km": 45.0,
    },
    "Undercover.csv": {
        "slug": "undercover",
        "label": "Undercover",
        "viewbox": (-0.13, 51.51, -0.07, 51.54),
        "center": (51.525, -0.094),
        "max_km": 5.0,
    },
}

SEARCH_RE = re.compile(r"/maps/search/(-?\d+\.?\d*),\s*(-?\d+\.?\d*)")
AT_RE = re.compile(r"@(-?\d+\.?\d*),(-?\d+\.?\d*)")
D3_RE = re.compile(r"!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)")
FTID_RE = re.compile(r"1s(0x[0-9a-fA-F]+:0x[0-9a-fA-F]+)")
DMS_RE = re.compile(
    r"""(\d+)°(\d+)'([\d.]+)\"?\s*([NS])\s+(\d+)°(\d+)'([\d.]+)\"?\s*([EW])"""
)

LONDON_DEFAULT = (51.5072178, -0.1275862)
UA = "LondonParkourSpotImport/1.0 (local resolve; contact site owner)"

GENERIC_TITLES = {
    "dropped pin",
    "parking lot",
    "athletic field",
    "london",
    "bermondonsey",
    "bermondsey",
    "whitechapel",
    "city of london",
}


def dms_to_decimal(m: re.Match) -> tuple[float, float]:
    def one(d, mi, s, hem):
        v = float(d) + float(mi) / 60 + float(s) / 3600
        return -v if hem in ("S", "W") else v

    return one(m[1], m[2], m[3], m[4]), one(m[5], m[6], m[7], m[8])


def expand_abbr(title: str) -> str:
    """Expand street abbreviations without eating letters inside words (Playground)."""
    replacements = (
        (r"\bSt\b", "Street"),
        (r"\bRd\b", "Road"),
        (r"\bCl\b", "Close"),
        (r"\bWy\b", "Way"),
        (r"\bLn\b", "Lane"),
        (r"\bPl\b", "Place"),
    )
    out = title
    for pattern, repl in replacements:
        out = re.sub(pattern, repl, out)
    return out.strip()


def haversine_km(a: tuple[float, float], b: tuple[float, float]) -> float:
    r = 6371.0
    lat1, lon1 = map(math.radians, a)
    lat2, lon2 = map(math.radians, b)
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    h = (
        math.sin(dlat / 2) ** 2
        + math.cos(lat1) * math.cos(lat2) * math.sin(dlon / 2) ** 2
    )
    return 2 * r * math.asin(math.sqrt(h))


def near_default(lat: float, lon: float, tol_m: float = 80) -> bool:
    return haversine_km((lat, lon), LONDON_DEFAULT) * 1000 <= tol_m


def in_greater_london(lat: float, lon: float) -> bool:
    return 51.25 <= lat <= 51.72 and -0.55 <= lon <= 0.35


def http_json(url: str, headers: dict | None = None):
    req = urllib.request.Request(url, headers=headers or {"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=30) as resp:
        return json.loads(resp.read().decode("utf-8", "replace"))


def http_text(url: str, headers: dict | None = None) -> str:
    req = urllib.request.Request(
        url,
        headers=headers
        or {
            "User-Agent": (
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
                "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
            ),
            "Accept-Language": "en-GB,en;q=0.9",
        },
    )
    with urllib.request.urlopen(req, timeout=30) as resp:
        return resp.read().decode("utf-8", "replace")


def coords_from_url(url: str) -> tuple[float, float] | None:
    for rx in (SEARCH_RE, AT_RE, D3_RE):
        m = rx.search(url)
        if m:
            return float(m.group(1)), float(m.group(2))
    return None


def nominatim(query: str, viewbox: tuple[float, float, float, float] | None, bounded: bool):
    params = {
        "q": query,
        "format": "json",
        "limit": "5",
        "countrycodes": "gb",
    }
    if viewbox:
        w, s, e, n = viewbox
        params["viewbox"] = f"{w},{s},{e},{n}"
        params["bounded"] = "1" if bounded else "0"
    url = "https://nominatim.openstreetmap.org/search?" + urllib.parse.urlencode(params)
    time.sleep(1.05)
    return http_json(url)


def pick_nominatim(rows, meta) -> tuple[float, float, str] | None:
    center = meta["center"]
    max_km = meta["max_km"]
    best = None
    best_d = 1e9
    for row in rows or []:
        lat, lon = float(row["lat"]), float(row["lon"])
        if not in_greater_london(lat, lon) and max_km < 40:
            # allow Merstham etc on the wide PK Spots list
            if haversine_km((lat, lon), center) > max_km:
                continue
        d = haversine_km((lat, lon), center)
        if d <= max_km and d < best_d:
            best = (lat, lon, row.get("display_name", ""))
            best_d = d
    return best


def gmaps_tbm(query: str) -> tuple[float, float] | None:
    url = (
        "https://www.google.com/search?tbm=map&authuser=0&hl=en&gl=uk&q="
        + urllib.parse.quote(query)
    )
    time.sleep(0.45)
    text = http_text(url)
    if text.startswith(")]}'"):
        text = text[4:]
    ms = re.findall(r"\[null,null,(-?\d+\.\d+),(-?\d+\.\d+)\]", text)
    if not ms:
        return None
    lat, lon = float(ms[0][0]), float(ms[0][1])
    if near_default(lat, lon):
        return None
    return lat, lon


def resolve_place(title: str, meta: dict) -> tuple[float, float, str] | None:
    label = meta["label"]
    expanded = expand_abbr(title)
    queries = [
        f"{expanded}, {label}, London, UK",
        f"{expanded}, London, UK",
    ]
    if expanded.lower() != title.lower():
        queries.append(f"{title}, London, UK")

    for q in queries:
        try:
            rows = nominatim(q, meta["viewbox"], bounded=True)
        except Exception:
            rows = []
        hit = pick_nominatim(rows, meta)
        if hit:
            return hit[0], hit[1], f"nominatim:{hit[2][:80]}"

    for q in queries:
        try:
            rows = nominatim(q, meta["viewbox"], bounded=False)
        except Exception:
            rows = []
        hit = pick_nominatim(rows, meta)
        if hit:
            return hit[0], hit[1], f"nominatim-unbounded:{hit[2][:80]}"

    # Vague titles are too risky for Google centroid fallbacks.
    if title.strip().lower() in GENERIC_TITLES or not title.strip():
        return None

    for q in (
        f"{expanded} {label} London",
        f"{expanded} London",
    ):
        try:
            got = gmaps_tbm(q)
        except Exception:
            got = None
        if not got:
            continue
        lat, lon = got
        if haversine_km((lat, lon), meta["center"]) <= meta["max_km"] or (
            meta["slug"] == "pk-spots" and in_greater_london(lat, lon)
        ):
            # Merstham park is just outside the GL bbox south edge — allow ±0.1
            if meta["slug"] == "pk-spots" or haversine_km((lat, lon), meta["center"]) <= meta["max_km"]:
                return lat, lon, f"gmaps-tbm:{q}"
        # Merstham etc.
        if meta["slug"] == "pk-spots" and 51.20 <= lat <= 51.75 and -0.55 <= lon <= 0.35:
            return lat, lon, f"gmaps-tbm:{q}"

    return None


def display_title(title: str, note: str, lat: float, lon: float, list_label: str) -> str:
    t = (title or "").strip()
    if t and t.lower() != "dropped pin" and not DMS_RE.match(t.replace('""', '"')):
        return t
    if note.strip():
        return note.strip()[:80]
    return f"{list_label} spot ({lat:.5f}, {lon:.5f})"


def slugify(text: str) -> str:
    s = re.sub(r"[^a-z0-9]+", "-", text.lower()).strip("-")
    return s[:60] or "spot"


def main() -> None:
    raw_rows = []
    for fname in CSV_FILES:
        path = BASE / fname
        meta = LIST_META[fname]
        with path.open(newline="", encoding="utf-8") as fh:
            for i, row in enumerate(csv.DictReader(fh), start=2):
                title = (row.get("Title") or "").strip()
                note = (row.get("Note") or "").strip()
                tags = (row.get("Tags") or "").strip()
                url = (row.get("URL") or "").strip()
                if not url and not title:
                    continue
                raw_rows.append(
                    {
                        "list": fname,
                        "list_slug": meta["slug"],
                        "list_label": meta["label"],
                        "line": i,
                        "title": title,
                        "note": note,
                        "tags": tags,
                        "url": url,
                        "ftid": (FTID_RE.search(url).group(1) if url and FTID_RE.search(url) else None),
                    }
                )

    resolved = []
    unresolved = []

    for idx, row in enumerate(raw_rows, start=1):
        meta = LIST_META[row["list"]]
        lat = lon = None
        src = None

        if row["url"]:
            got = coords_from_url(row["url"])
            if got:
                lat, lon = got
                src = "url"

        if lat is None and row["title"]:
            m = DMS_RE.search(row["title"].replace('""', '"'))
            if m:
                lat, lon = dms_to_decimal(m)
                src = "dms-title"

        if lat is None and row["url"] and "/maps/place/" in row["url"]:
            print(f"[{idx}/{len(raw_rows)}] geocode {row['title'] or row['url'][:60]} …", flush=True)
            hit = resolve_place(row["title"], meta)
            if hit:
                lat, lon, src = hit
            else:
                unresolved.append(row)
                print("  FAIL", flush=True)
                continue

        if lat is None or lon is None:
            unresolved.append(row)
            continue

        title = display_title(row["title"], row["note"], lat, lon, row["list_label"])
        resolved.append(
            {
                **row,
                "lat": round(lat, 7),
                "lon": round(lon, 7),
                "source": src,
                "name": title,
                "slug": f"{slugify(title)}-{abs(hash((round(lat,5), round(lon,5)))) % 10_000_000:07d}",
            }
        )
        if src != "url":
            print(f"  OK {lat:.6f},{lon:.6f} via {src}", flush=True)

    # Dedupe by rounded coords; prefer named titles over Dropped-pin labels.
    by_key: dict[tuple[float, float], dict] = {}
    for row in resolved:
        key = (round(row["lat"], 5), round(row["lon"], 5))
        prev = by_key.get(key)
        if not prev:
            by_key[key] = row
            continue
        # Prefer a human title
        def score(r):
            n = r["name"].lower()
            s = 0
            if "spot (" not in n:
                s += 2
            if r["list_slug"] != "pk-spots":
                s += 1  # prefer area-specific list provenance
            if r.get("note"):
                s += 1
            if r.get("tags"):
                s += 1
            return s

        if score(row) > score(prev):
            # merge tags/lists
            lists = sorted(set([prev["list_slug"], row["list_slug"]]))
            row = {**row, "lists": lists}
            by_key[key] = row
        else:
            lists = sorted(set([prev.get("list_slug")] + prev.get("lists", []) + [row["list_slug"]]))
            prev["lists"] = [x for x in lists if x]
            by_key[key] = prev

    spots = list(by_key.values())
    for s in spots:
        s.setdefault("lists", [s["list_slug"]])
        s["streetview"] = (
            f"https://www.google.com/maps/@?api=1&map_action=pano&viewpoint={s['lat']},{s['lon']}"
        )

    payload = {
        "generated_from": CSV_FILES,
        "count": len(spots),
        "unresolved_count": len(unresolved),
        "spots": spots,
        "unresolved": unresolved,
    }
    OUT.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    print(
        f"\nWrote {OUT}\n"
        f"Resolved unique spots: {len(spots)}\n"
        f"Unresolved place URLs: {len(unresolved)}"
    )
    for u in unresolved:
        print(f"  - [{u['list_label']}] {u['title'] or '(no title)'} | {u['url']}")


if __name__ == "__main__":
    main()
