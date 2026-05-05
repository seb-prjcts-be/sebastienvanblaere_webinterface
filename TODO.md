# TODO — sebastienvanblaere.be ecosysteem

Living document. Strepen-of-verwijderen wat klaar is.

## Vandaag uitgevoerd (5 mei 2026) — grote aanpassingen

### Architectuur-pivot ×2

1. **Eerste pivot** (ochtend): van merged hub (alles in `htdocs/sebastienvanblaere.be/`) naar **plat per-domein** (`htdocs/<host>/` per site). Folders verhuisd, helpers omgezet, junctions per site naar canonical `_shared`.

2. **Tweede pivot** (namiddag): plat → **nested** terug onder `htdocs/sebastienvanblaere.be/`. Reden: gebruiker wilde lokaal alle sites visueel onder de hub-folder. Helpers nog eens omgezet voor `/<hub_root>/<host>/` URL-patroon in path-mode. Productie blijft host-mode (relatieve `/_shared/`).

### Productie-deploy bewezen werkend

3. **GitHub Actions FTPS → SFTP via wlixcc**. Iteratie: FTPS faalde (one.com ondersteunt geen FTPS), key-auth faalde (key-format invalid), password-auth werkte, leading-slash-pad faalde (read-only fs), relative-pad werkte, `webroots/<hash>/` was juiste pad (niet `kunstmijnoren.be/httpd.www/`).

4. **`kunstmijnoren.be` is LIVE** met content + assets via auto-deploy. Images manueel via FTP geupload (Option B gitignore: alle images uitgesloten van git).

### Code-aanpassingen

5. **Per-site `_shared` als kopie** in elk site-repo (was junction, werd kopie omdat productie eigen `_shared/` per webspace nodig heeft). Wijzigingen in canonical `_shared/` moeten manueel gesynct naar elke site-kopie — TODO: sync-script.

6. **Constellation in sync gebracht met linktree** — service-links + Education-sectie + bijgewerkte labels. Render-loop ondersteunt nu ook `header`-type.

7. **Helpers omgezet voor nested paths**: `link_to`, `shared_asset_url`, `thumb_url`, `dispatch`, `detect_satellite`, `header.php $path_pref`, `index.php $site_url`.

8. **`hub_url()` expliciet `https://`** in productie (was protocol-relative `//`).

### one.com inzichten

9. **Modern webroot-layout**: per domein een hash-folder onder `webroots/<hash>/` (bv. `webroots/0aac7cb1/` voor kunstmijnoren). Niet `<domein>/httpd.www/` zoals oudere accounts.

10. **`put -r ./*`-quirk**: SFTP overschrijft niet altijd default placeholder-files (one.com plaatst stub `index.php` 12 bytes + stub `.htaccess` 72 bytes bij webspace-activatie). Workaround: webspace eerst leeg maken via FileZilla, dan deployen.

## TODO — nog te doen

### FIDK header + sidebar integreren

- [ ] **`services/fidk/`** — header en constellation-sidebar toevoegen zodat het visueel matcht met prjcts.be / kunstmijnoren.be (top-bar "← sebastien", rechter-sidebar met cross-site nav). FIDK werkt nu functioneel maar staat los visueel.

### Sync `_shared` automatisch

- [ ] **Sync-script** schrijven (`sync-shared.sh` in hub-root). Eén commando dat canonical `_shared/` kopieert naar elke site-repo. Voor gebruik na elke `_shared/` wijziging.
  - Alternatieven: git submodule, of CI-driven cross-repo push (vergt `_shared` public OF PAT).

### Productie-deploy uitbreiden

- [ ] **GitHub Secrets** toevoegen in 3 nog-niet-deployable repos:
  - `seb-prjcts-be/sebastienvanblaere_webinterface` (hub)
  - `seb-prjcts-be/sebastienvanblaere_shared`
  - `seb-prjcts-be/sebastienvanblaere_prjcts.be`
  - `seb-prjcts-be/sebastienvanblaere_creative_coding_site`
  - Per repo: `SFTP_HOST`, `SFTP_USER`, `SFTP_PASS`. Of organisatie-secrets als seb-prjcts-be een org is.
- [ ] **Webroot-hash ophalen** via FileZilla voor elk domein: `prjcts.be`, `sebastienvanblaere.be`, `creativecoding.prjcts.be`. Update `remote_path` in elke `deploy.yml`.
- [ ] **Webspace activeren** op one.com voor `prjcts.be` als die nog op legacy/forward staat (zoals kunstmijnoren in 't begin).
- [ ] **Eerste deploys uitvoeren** voor prjcts.be + linktree + cursus.

### Services-repos koppelen
- [ ] **Cursus-repo pushen** (staat op feature-branch, oude commit-history). Strategie: zelfde fresh-init als hub als history te zwaar is.
- [ ] **Manuele SFTP-upload** voor afbeeldingen + video's (gitignored). Optie B-keuze: alle images los van git → bij elke nieuwe content-batch via FileZilla.

## Domein-config te beslissen / verifiëren

- [ ] **`p5.prjcts.be` ↔ `creativecoding.prjcts.be`** mismatch — code verwijst naar `p5.prjcts.be`, one.com heeft webforward op `creativecoding.prjcts.be`. Kies één en sync.
- [ ] **`export.prjcts.be`** — bestaat dit subdomein al op one.com? Zo niet → broken link in linktree of toevoegen.
- [ ] **`prjcts.be` + `kunstmijnoren.be` op one.com** — aparte webroots (Setup B) of alias van `sebastienvanblaere.be` (domain-alias)? Verifiëren via SFTP-tree op productie.
- [ ] **Wildcard DNS `*.prjcts.be`** voorbereiden voor toekomstige subdomeinen zonder DNS-aanpassing.
- [ ] **HTTPS cert** uitbreiden met wildcard `*.prjcts.be` (Let's Encrypt DNS-01 challenge).

## Linktree polish (optioneel)

- [ ] Extra section-headers — bv. `WORK`, `TOOLS`, `EDUCATION` (al er), `ELSEWHERE`
- [ ] Eigen subdomein voor `services/svg/`, `services/p5.blocks/`, `services/fidk/` als die richting evolueren naar zelfstandige sites.

## Site-fixes

- [ ] **Zoomfield link werkt niet** — `menu_items.php` r.29 wijst naar `/zoomfield/index.html` maar bestand zit in `content/zoomfield/`. Drie opties:
  - A. Link aanpassen → `/content/zoomfield/index.html` (lelijk)
  - B. Folder verplaatsen → `sites/prjcts/zoomfield/` (cleaner, voorgesteld)
  - C. Junction/symlink (one.com staat geen symlinks toe → enkel lokaal)

## Backup / recovery

- [x] Oude `.git_BACKUP_pre-fresh-init` opgeruimd (2 GB vrij)
- [ ] **Productie-backup-strategie** — wat als one.com server crasht? Heeft one.com automatische backups? Of zelf periodiek SFTP-pull doen?

## Services → GitHub-repo mapping

Elk service onder `services/` krijgt eigen repo. Zo te koppelen morgen:

| Local folder | GitHub repo | Status morgen-actie |
|---|---|---|
| `creative_coding_site` | `sebastienvanblaere_creative_coding_site` | ✓ remote OK · pushen (mogelijks fresh-init) |
| `fidk` | `fidk` | leeg op GitHub (1 KB) → init lokaal + push veilig |
| `p5.blocks` | `p5_blocks` | 91 KB op GitHub → vergelijk met lokaal vooraleer init |
| `p5.export` | `p5.export` | 76 KB op GitHub → vergelijk met lokaal vooraleer init |
| `svg` | `svg_export` | leeg op GitHub (juist aangemaakt) → init lokaal + push veilig |

**Aparte fork (geen service):** `seb-prjcts-be/p5.js-website` (550 MB) — fork van officiële p5.js-website voor p5.waves library bijdrage. Geen actie nodig.

## Plan-residue (uit eerdere sessie, lange termijn)

Zie `~/.claude/plans/ok-terug-hier-bij-stateful-seal.md` voor het volledige opslagplan.

- [x] Audio-rendering in works-grid + lightbox
- [x] PDF-support in MEDIA_EXT
- [x] Parsedown + `render_md()` helper
- [x] `writings/` content-type + page + menu-link
- [x] Bio-page
- [ ] Lab44.be — eigen sessie/plan (los van hub-paar)
- [ ] Eerste echte content-migratie naar `writings/` (eens vault publiek-rijp materiaal heeft)
