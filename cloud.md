# sebastienvanblaere.be — hub

## Missie
Eén gedeeld skelet (`_shared/`) draagt 5 satellieten met identieke code en uniform schema; verschil tussen satellieten = content (`.md` per taal), `menu_items.php`, optionele `assets/site.css`. Lokaal draait alles via `localhost/sebastienvanblaere.be/sites/<key>/`; productie draait host-based op `<host>`.

## Boom
```
C:\server\htdocs\
├── _shared\
│   ├── bootstrap.php           host+folder-detectie, lang-redirect, mode-detect
│   ├── config.php              5 satellieten (host, title, register, density, accent, decoration)
│   ├── helpers.php             e(), link_to(), dispatch(), detect_satellite()
│   ├── content.php             discover_abouts/projects/works(), read_meta/body()
│   ├── partials\               header, footer, menu-sidepanel, constellation, decoration
│   ├── DECORATION.md           ← p5-balletjes technische doc
│   ├── UI.md                   ← header/constellation/menu/lightbox reference voor nieuwe satellieten
│   ├── SECURITY.md             ← audit-log + checklist
│   ├── Parsedown.php           ← markdown→HTML parser (vendor)
│   ├── thumb.php               ← on-demand cached thumbnail-generator
│   └── assets\
│       ├── shared.css          baseline + register-overrides + canvas styling
│       ├── menu.css            sidepanel + push-effect
│       ├── menu.js             togglers + state-persistence
│       ├── decoration.js       p5play-balletjes (cfg-driven, optionele image-sprite)
│       └── p5play.js           lokale kopie (uit LIVE_prjcts3.0/js/, 277 KB)
│
└── sebastienvanblaere.be\
    ├── index.php               landing (linktree)
    └── sites\
        ├── prjcts\             3 rode bouncing balls
        ├── kunstmijnoren\      2 oor-balletjes (image-sprite, één gespiegeld)
        └── (p5/waves/export)   geen decoratie
```

Per satelliet uniform: `index.php` (3-regel bootstrap), `menu_items.php`, `pages/{home,about,projects,...}.php`, `content/<sectie>/<NN-slug>/{nl,en,fr}.md + meta.json`, `assets/site.css`.

## Domeinen (productie)

| Domein | Rol | Routing |
|---|---|---|
| `sebastienvanblaere.be` | Persoon-hub (linktree) | `index.php` direct |
| `prjcts.be` (+ www) | Identiteit 1 | hub-`.htaccess` → `sites/prjcts/` |
| `kunstmijnoren.be` (+ www) | Identiteit 2 (alter ego) | hub-`.htaccess` → `sites/kunstmijnoren/` |
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
- Alle dynamische output via `e()` (in helpers.php). `declare(strict_types=1)` bovenaan elk PHP-bestand.
- Content-isolatie: elke satelliet heeft eigen `content/`. Geen pool delen.
- p5play moet **lokaal** geserveerd via `_shared/assets/p5play.js`. CDN `p5play.org/v3/` kan stilzwijgend updaten en API-changes brengen → niet gebruiken.
- p5-canvas styling: brede selector `canvas.p5Canvas { position: fixed }` (niet `body > canvas` — p5play kan in wrapper plaatsen).
- p5play voegt automatisch een credit-link `<a href="https://p5play.org">` toe; verbergen via CSS, anders verlengt die de pagina.
- Naming: folder-prefix `NN-` voor manuele volgorde (sortering = `strnatcmp` op folder-naam). Slug `[a-z0-9-]` only. Display-naam komt uit `meta.json`.
- localStorage `ballsState` is per-origin → lokaal delen prjcts en kunstmijnoren dezelfde key. Acceptabel: count-mismatch triggert reset. Te overwegen: per-satelliet key indien echt storend.

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
