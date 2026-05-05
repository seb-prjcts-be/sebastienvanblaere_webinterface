# TODO — sebastienvanblaere.be ecosysteem

Living document. Strepen-of-verwijderen wat klaar is.

## Direct (voor eerste live-deploy)

- [ ] **GitHub Secrets** toevoegen in alle 3 repos:
  - `seb-prjcts-be/sebastienvanblaere_webinterface` (hub)
  - `seb-prjcts-be/sebastienvanblaere_shared`
  - `seb-prjcts-be/sebastienvanblaere_creative_coding_site`
  - Per repo: `SFTP_HOST`, `SFTP_USER`, `SFTP_PASS`
- [ ] **Services-repos koppelen** (allemaal gemapt — zie tabel onderaan)
- [ ] **Cursus-repo pushen** (staat op feature-branch, oude commit-history). Strategie: zelfde fresh-init als hub als history te zwaar is.
- [ ] **Eerste deploy testen** na secrets — controleer of GitHub Actions groen wordt en de site daadwerkelijk leeft.
- [ ] **Server-pad bevestigen** op one.com — `/sebastienvanblaere.be/httpd.www/` is nu in alle drie de YAML-files. Check eerste deploy of dat werkt.
- [ ] **Manuele SFTP-upload** voor video-folders (gitignored): `01-graphiques-machiniques/`, `02-archief/03-experiments/`, alle `*.mp4` elders. Zijn al online → enkel bij toekomstige nieuwe video's manueel uploaden.

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
