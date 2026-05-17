# sebastienvanblaere.be — hub

## Missie
`sebastienvanblaere.be` heeft een **dubbele aard**:
- **Online** = de *schijnbare* overkoepelende site: één linktree-voordeur (`index.php`) die naar prjcts, kunstmijnoren én p5waves leidt. Bezoekers zien één geheel.
- **Offline** = een *letterlijke* paraplu-map: de hub-folder bevat álle satelliet-mappen + de canonieke `_shared/`.

Eén gedeeld skelet (`_shared/`) draagt alle satellieten met identieke code en uniform schema; verschil tussen satellieten = content (`.md` per taal), `menu_items.php`, `lib/*`, optionele `assets/site.css`. Lokaal draait alles genest via `localhost/sebastienvanblaere.be/<host>/`; productie draait host-based op `<host>` (elk domein eigen one.com-webspace).

**Kern-invariant:** elke satelliet is een *eigen git-repo met eigen GitHub-remote en eigen webspace*. Features worden offline uit `_shared/` geleend/hergebruikt, maar `_shared/` bestaat **dubbel**: canoniek in de hub-repo én als **kopie per satelliet** (`<host>/_shared/`). Een geleende feature is pas live als de satelliet-kopie mee-gecommit, mee-gepusht én mee-geftpt is.

## Boom
```
C:\server\htdocs\sebastienvanblaere.be\   ← HUB-map = de paraplu (offline)
├── index.php          landing/linktree (online: schijnbare overkoepelende site)
├── _shared\           CANONIEKE bron van het skelet (leeft in de hub-repo)
│   ├── bootstrap.php · config.php · helpers.php · content.php · thumb.php · Parsedown.php
│   ├── partials\      header, footer, menu-sidepanel, constellation, decoration
│   ├── assets\        shared.css · menu.css · menu.js · decoration.js · p5play.js · hub-injection.js
│   └── *.md           DECORATION.md · UI.md · SECURITY.md (referentie-docs)
├── assets\            hub-eigen assets (og-portrait, …)
├── services\          autonome services — NIET in hub-repo (.gitignore), elk eigen repo
├── prjcts.be\         satelliet ─┐
├── kunstmijnoren.be\  satelliet ─┤ elk: eigen repo + eigen .github/workflows/deploy.yml
└── p5waves.org\       satelliet ─┘        + eigen KOPIE van _shared\
```

Eigen GitHub-remote per map (alle branch `master`):

| Map | Repo |
|---|---|
| hub (`sebastienvanblaere.be/`) | `seb-prjcts-be/sebastienvanblaere_webinterface` |
| `prjcts.be/` | `seb-prjcts-be/sebastienvanblaere_prjcts.be` |
| `kunstmijnoren.be/` | `seb-prjcts-be/sebastienvanblaere_kunstmijnoren.be` |
| `p5waves.org/` | `seb-prjcts-be/p5.wave.org` |

Per satelliet uniform: `index.php` (3-regel bootstrap → `_shared/bootstrap.php`), `menu_items.php`, `pages/{home,about,projects,writings,guides,…}.php`, optioneel `lib/*` (satelliet-specifiek), `content/<sectie>/<NN-slug>/{nl,en,fr}.md + meta.json`, `assets/site.css`, `.github/workflows/deploy.yml` (SFTP → one.com `webroots/<hash>/`).

## Domeinen (productie)

| Domein | Rol | Routing |
|---|---|---|
| `sebastienvanblaere.be` | Persoon-hub (linktree) | `index.php` direct |
| `prjcts.be` (+ www) | Identiteit 1 | hub-`.htaccess` → `sites/prjcts/` |
| `kunstmijnoren.be` (+ www) | Identiteit 2 (alter ego) | hub-`.htaccess` → `sites/kunstmijnoren/` |
| `p5waves.org` (+ www) | p5.waves-vitrine (volwaardige satelliet) | host-based → `p5waves.org/` |
| `p5.prjcts.be` | Cursus (creative coding) | junction → `htdocs/LIVE_p5_cursus_site/` |
| `*.prjcts.be` (overige) | Libraries / utilities | wildcard, eigen DocumentRoots |
| `lab44.be` | Onderwijs (los, eigen identiteit) | direct, eigen kader, geen koppeling |

**Wildcard cert** (Let's Encrypt, DNS-01 challenge):
```sh
# Voorbeeld via certbot + Cloudflare-plugin (pas aan registrar):
certbot certonly --dns-cloudflare \
  --dns-cloudflare-credentials ~/.secrets/cf.ini \
  -d 'prjcts.be' -d '*.prjcts.be' \
  -d 'sebastienvanblaere.be' -d 'kunstmijnoren.be' -d 'lab44.be' \
  -d 'www.prjcts.be' -d 'www.kunstmijnoren.be' -d 'www.lab44.be' -d 'www.sebastienvanblaere.be'
```
DNS-record nodig: `*.prjcts.be` → A `<server-IP>` (één keer instellen, dekt alle toekomstige subdomeinen).

## Regels
- **Shared-invariant:** wijzig je de canonieke `_shared/` (hub-repo), sync dan naar elke `<host>/_shared/`-kopie en commit+push+ftp die mee — anders lopen satellieten uiteen. Sync-script (`sync-shared.sh`) staat op TODO. Elke satelliet = eigen repo + eigen one.com-webspace; nooit aannemen dat een hub-wijziging vanzelf live is.
- Alle dynamische output via `e()` (in helpers.php). `declare(strict_types=1)` bovenaan elk PHP-bestand.
- Content-isolatie: elke satelliet heeft eigen `content/`. Geen pool delen.
- p5play moet **lokaal** geserveerd via `_shared/assets/p5play.js`. CDN `p5play.org/v3/` kan stilzwijgend updaten en API-changes brengen → niet gebruiken.
- p5-canvas styling: brede selector `canvas.p5Canvas { position: fixed }` (niet `body > canvas` — p5play kan in wrapper plaatsen).
- p5play voegt automatisch een credit-link `<a href="https://p5play.org">` toe; verbergen via CSS, anders verlengt die de pagina.
- Naming: folder-prefix `NN-` voor manuele volgorde (sortering = `strnatcmp` op folder-naam). Slug `[a-z0-9-]` only. Display-naam komt uit `meta.json`.
- localStorage `ballsState` is per-origin → lokaal delen prjcts en kunstmijnoren dezelfde key. Acceptabel: count-mismatch triggert reset. Te overwegen: per-satelliet key indien echt storend.
- **Schrijfsels (`content/writings/*`) NOOIT inhoudelijk of vormelijk aanpassen zonder overleg.** Geen styling, classes, bold, italic, hr, kleurverschillen of paragraaf-herschikkingen toevoegen of verwijderen op eigen initiatief. Stijl-elementen alleen op expliciete vraag.

## Notities
- 2026-05-03: p5-decoratie van prjcts3.0 geport naar gemerdge `_shared/` — lokale p5play.js, CSS-fixes (canvas selector + credit-link suppression), `world.gravity.y = 10` hardcoded matching origineel.
- 2026-05-03: decoration.js uitgebreid met `spriteImage` + `mirrorEven` + `cursorVisible` config-keys voor kunstmijnoren oor-balletjes. Render via clipped circle + outline (zoals oude kunstmijnoren `Ball.draw()`).
- 2026-05-03: 04-brein in prjcts/abouts heeft volledige nl + en + fr + meta.json + brein.gif (niet, zoals een eerdere getrunceerde Glob suggereerde, alleen en.md). Alle 26 content-folders zijn taligvolledig.
- 2026-05-03: kunstmijnoren toont nu correct 2 oor-balletjes — eerder probleem opgelost.
- 2026-05-03: schema-uitbreiding — projects kunnen optioneel `aside_<lang>.md` droppen voor extra side-kolom content (drop-folder, fallback naar nl). Helper `read_aside()` in content.php; render bovenaan `prjcts-col-side` in projects.php. Eerste gebruik: entropy-of-thought met Wikipedia-snippet.
- 2026-05-03: gastenboek-pages voor prjcts + kunstmijnoren (Elfsight Comments-widget, app-id per satelliet); menu-link via `/guests`.
- 2026-05-03: security-audit + fixes — zie `_shared/SECURITY.md`. Verwijderd: dood `content/zoomfield/file_lister.php`. Toegevoegd: `display_errors`-toggle in bootstrap, secure-cookies, hub-`.htaccess` hardening (`Options -Indexes`, dotfiles geblokt, `X-Content-Type-Options`/`X-Frame-Options`/`Referrer-Policy`/`Permissions-Policy`/HSTS-headers), generieke 500-error op productie (geen path-disclosure).
- 2026-05-03: UI-componenten gedocumenteerd in `_shared/UI.md` — header (top bar), constellation (right sidebar), menu sidepanel (left), lightbox + persistence-keys + complete checklist voor het bouwen van een nieuwe satelliet (p5/waves/export).
- 2026-05-03: opslag-architectuur uitgebreid (zie plan in `~/.claude/plans/`). Identiteits-hiërarchie expliciet: persoon → prjcts + kunstmijnoren (tweelingen) + libraries (waves/p5/export). Nieuw in prjcts: `content/writings/` (essays/verhalen/onderzoek/brieven met `meta.type`-filter), `content/bio/` (CV-pagina), `pages/writings.php` + `pages/bio.php` + menu-links. Schema: `read_writing` + `discover_writings` in content.php, sortering desc op `meta.date`. Parsedown geïnstalleerd in `_shared/Parsedown.php` + `render_md()` helper (backward-compatible: HTML-in-md blijft werken). Audio + PDF nu volwaardig in works-grid (icon + label) + lightbox (audio-player / iframe). Voorbeeld-template in `content/writings/01-template/`. Domein-strategie: wildcard `*.prjcts.be` voorbereiden, lab44.be blijft los (eigen sessie later).
- 2026-05-03: Instagram-export geïntegreerd in `06-das-dunkelheit-prinzip` — 43 IG-posts (sept 2021 → sept 2022) toegevoegd aan de 12 bestaande hi-res images = 55 thumbs in works-grid. `_notes_instagram.md` (drop-folder hidden) bevat dates + captions + zusterconcept "In obscurum". 6 IG-files met `.webp` extension waren feitelijk JPEG (magic-bytes-check) — hernoemd naar `.jpg` voor thumb-generator. **IG-export-quirk** bewust te onthouden: check magic bytes vóór file rename op trust extension.
- 2026-05-03: **Satelliet 1 geactiveerd — `p5.prjcts.be`** via Windows-junction. Bron-folder is `services/creative_coding_site/` (zie services-migratie hieronder).
- 2026-05-03: **Services-structuur ingevoerd.** Naast `sites/{prjcts,kunstmijnoren}/` (identiteits-tweelingen) staat nu `services/<naam>/` voor elke autonome satelliet/service. Drie verhuisd uit `htdocs/LIVE_*/` naar `services/`: `creative_coding_site/` (uit LIVE_p5_cursus_site, behoudt git-remote `seb-prjcts-be/p5js_Student_site`), `fidk/`, `svg/` (laatste twee zonder git, nog op te zetten). Junction `htdocs/p5.prjcts.be/` wijst nu naar `services/creative_coding_site/`. **Beleid**: lokale folder-naam = service-naam (bv. `services/creative_coding_site/`), GitHub-repo behoudt z'n historische naam (geen rename op GitHub). Mapping wordt onderhouden via `git remote get-url origin` per service.
- 2026-05-03: hub-folder (`sebastienvanblaere.be/`) en `_shared/` zijn nu eigen git-repo's met initial commit. `services/` zit in hub-`.gitignore` (niet meelogt in hub-repo, want elke service heeft eigen repo).
- 2026-05-04: hub-repo heeft GitHub remote `sebastienvanblaere_webinterface`. Push faalde aanvankelijk door 9 mp4's >50MB; opgelost via fresh-init met `*.mp4` in gitignore en `01-graphiques-machiniques/` + `02-archief/03-experiments/` folder-exclusions. Hub-repo nu 196MB op GitHub. Drie deploy.yml-files klaar (FTPS via SamKirkland) maar nog zonder secrets.
- 2026-05-05: **Architectuur-pivot ×2.** Eerste: merged hub → plat per-domein (`htdocs/<host>/`) voor 1-op-1 mapping met one.com per-domein webspaces. Tweede: plat → nested terug onder `htdocs/sebastienvanblaere.be/<host>/` (gebruikersvoorkeur voor visuele structuur). Helpers (link_to/shared_asset_url/thumb_url/dispatch + header.php $path_pref) twee keer omgezet. Productie blijft host-mode met relatieve `/_shared/` (geen cross-domain).
- 2026-05-05: **kunstmijnoren.be is LIVE** via GitHub Actions auto-deploy. Stack: wlixcc/SFTP-Deploy-Action met password-auth (one.com ondersteunt geen FTPS). Lessen: SFTP path moet relatief (geen leading `/`), one.com plaatst placeholder `index.php`/`.htaccess` bij webspace-activatie die SFTP niet altijd kan overschrijven (workaround: webspace eerst leeg maken via FileZilla).
- 2026-05-05: **one.com modern webroot-layout**: per-domein hash-folder onder `webroots/<hash>/` (bv. `webroots/0aac7cb1/` voor kunstmijnoren). Niet `<domein>/httpd.www/` zoals bij oudere accounts. Hash moet manueel uit FileZilla gelezen worden + in deploy.yml `remote_path` gezet.
- 2026-05-05: **`_shared` als kopie binnen elk site-repo** (was junction). Reden: productie-deploy heeft eigen `_shared/` per webspace nodig. Trade-off: lokale wijzigingen aan canonical `_shared/` moeten gesynct worden naar elke site-kopie. Sync-script (`sync-shared.sh`) staat op TODO.
- 2026-05-05: **Constellation gesynct met linktree** (services + Education-sectie + bijgewerkte labels). Render-loop ondersteunt nu `header`-type voor section-dividers. CSS class `.constellation-header` toegevoegd in menu.css.
- 2026-05-05: Optie B gitignore in alle site-repos: alle images + videos uitgesloten van git. Visuele content uploaden via FileZilla per content-batch. Logo's, icons, kleine assets ook → manuele upload.
- 2026-05-17: **p5waves.org is een volwaardige satelliet** (apex-domein, géén `external_url`, echt via `_shared/` geserveerd) — coexist naast de externe GitHub-Pages library-docs. Verweven "nooit out of date"-laag: `lib/manifest.php` leest de canonieke `p5.waves.manifest.json` mode-aware (path-mode → lokaal `htdocs/[library] … p5.waves/docs/…`; host-mode → GitHub-Pages-URL) + cache + last-good fallback. Nieuw: `pages/lib.php` ("De library") rendert versie/34 waves/API-changes/examples/ecosysteem live uit de manifest, met canonieke deeplinks afgeleid uit `pages_url` (geen hardcoded URL's). §0-check: canoniek v3.3.0. Boom/Missie hierboven gecorrigeerd naar de geneste realiteit (oude versie beschreef nog `sites/<key>/` van vóór de pivot).
