# VikoStream v1.0 — Mwongozo wa Kusakinisha

Theme ya WordPress kwa tovuti ya **Movies, TV Shows na Asian Drama** —
ikiwa na TMDB auto-importer, auto-embed players (DramaCool kwa dramas),
na homepage blocks za custom.

## 1. Install theme

1. Zip folder ya `vikostream` nzima (style.css iwe juu ndani ya zip)
2. `wp-admin` → **Appearance → Themes → Add New → Upload Theme** → zip → **Activate**

## 2. Weka Homepage

1. **Pages → Add New** → jina `Home` → Publish
2. **Settings → Reading** → *A static page* → Homepage: `Home` → Save

## 3. Weka TMDB API Key (MUHIMU kwa import tool)

1. Fungua akaunti ya bure: [themoviedb.org](https://www.themoviedb.org/signup)
2. **Settings → API → Request an API Key (v3)** — copy key
3. `wp-admin` → **Titles → ⚙ Settings** → bandika key → **Hifadhi**

## 4. Import movies / shows / dramas

`wp-admin` → **Titles → ⇩ Import**

- **🔎 Search** — tafuta kwa jina au IMDb ID (`tt0137523`); matokeo yana pagination
- **🎬 By Genre / Year** — discover kwa genre + mwaka + aina (ikiwemo *Asian Dramas* pekee)
- Kila result ina kitufe cha **Import** + checkbox — chagua nyingi → **Bulk Import**
- **📦 Bulk** — bandika orodha ya majina/IMDb IDs (moja kwa mstari) → resolve+import zote
- Kila import huleta: poster, backdrop, overview, genres, rating, year, runtime, IMDb ID, **trailer**
- Asian drama zinagunduliwa automatic (Korea/China/Japan…) na kupewa **DramaCool servers**
- **📜 Log** — historia ya imports zote

## 5. Players (servers)

- Server za kwanza: **VSEmbed** (`vsembed.ru`) na **VidSrc.me** (`vidsrcme.ru`) — zinafuatwa na MultiEmbed, AutoEmbed n.k.
- Kwa **Asian Drama**: DramaCool ndiye server ya kwanza, kisha VSEmbed/VidSrc.me
- TV/Drama: **Season chips + Episode grid** kwenye watch page + vitufe vya "Episode iliyopita/inayofuata" — kila episode ina player yake (URL hubadilishwa na `{season}`/`{episode}`)
- Badilisha/zima servers: **Titles → ⚙ Settings** (checkboxes + custom servers 3 kwa kila aina)
- Player maalum kwa title moja: metabox **"VikoStream — Title Data"** → `Label|URL` kwa kila mstari + **Test servers (ping)**
- Drama slug (kwa DramaCool) inajaza automatic; inaweza kurekebishwa kwenye metabox

## 5b. Seasons, Episodes na Waigizaji (auto)

- Ukipo-import TV show au Asian drama, theme huleta **seasons halisi na idadi ya episodes za kila season** kutoka TMDB
- Watch page huonesha season chips + episode grid — bofya episode yoyote, player inabadilika papo hapo
- **Waigizaji (cast)** top 12 hu-importiwa na picha zao — huoneshwa chini ya details kwenye watch page
- *Note:* titles zilizopo kabla ya v1.1 hazina cast/seasons data — futa na re-import kupata data kamili

## 6. Type / Genre ya title (MUHIMU)

- Chagua type (Movie / TV Show / Asian Drama) kwenye **metabox ya VikoStream** ndani ya edit page — ina-hifadhiwa **reliably** (core boxes za WP zimezimwa kwa sababu ziligongana na save)
- Genres pia zimehamishiwa kwenye metabox hiyo hiyo (checkboxes)
- ★ **Recommended** ikichaguliwa → title huonekana kwenye Slider + Recommended block

## 7. Homepage Blocks

`wp-admin` → **Titles → ▦ Homepage Blocks**

Mpangilio wa default: **Slider → A–Z → Recommended → Movies → TV Shows → Asian Dramas**

Ongeza block yoyote ya custom kwa rule ya:
- `★ Recommended`
- `Type` (movie / tvshow / asian-drama)
- `Genre` (Action, Romance, Melodrama…)

+ style (Slider / A–Z / Row / Grid), sort (latest / top rated / A–Z / random) na count.
Weka ★ Recommended kwenye title (metabox) ili ionekane kwenye slider + Recommended block.

## Screenshot ya theme (hiari, ya wp-admin)

Pakua picha hii → ihifadhi kama `screenshot.png` ndani ya folder ya `vikostream`:

```
https://image.qwenlm.ai/generated-images/6f5d1ffe-1b8d-4a29-bc4c-00a6216472cf/_result.png
```

## Mahitaji

- WordPress 6.0+, PHP 7.4+
- TMDB API key (bure) kwa import tool
- Hakuna plugins zinazohitajika

---

`$ vikostream --status` → *theme installed · players ready · karibu sinema* 🎬
