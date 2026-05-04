# Migratie 2026-05-03 — services + git-structuur

## Wat is gebeurd
Bestaande `htdocs/LIVE_*/`-folders verhuisd naar `htdocs/sebastienvanblaere.be/services/<naam>/`. Hub en `_shared/` zelf versioned. `htdocs/p5.prjcts.be/` is een Windows-junction naar de nieuwe locatie.

## Mapping (lokaal)

| Oude pad | Nieuwe pad | Git-remote (origin) |
|---|---|---|
| `htdocs/LIVE_p5_cursus_site/` | `htdocs/sebastienvanblaere.be/services/creative_coding_site/` | `https://github.com/seb-prjcts-be/p5js_Student_site.git` (behouden) |
| `htdocs/LIVE_fidk/` | `htdocs/sebastienvanblaere.be/services/fidk/` | nog niet versioned |
| `htdocs/LIVE_SVG_converter_multi/` | `htdocs/sebastienvanblaere.be/services/svg/` | nog niet versioned |

## Junctions (Apache-routing)

| Junction | Wijst naar | URL |
|---|---|---|
| `htdocs/p5.prjcts.be/` | `htdocs/sebastienvanblaere.be/services/creative_coding_site/` | `http://localhost/p5.prjcts.be/` (lokaal) + `https://p5.prjcts.be/` (productie) |

## Repo's (lokaal-status na deze migratie)

| Folder | Tracked files | Remote | Status |
|---|---|---|---|
| `htdocs/sebastienvanblaere.be/` | 839 | nog geen | initial commit lokaal, push later |
| `htdocs/_shared/` | 21 | nog geen | initial commit lokaal, push later |
| `services/creative_coding_site/` | (bestaand) | `seb-prjcts-be/p5js_Student_site` | **werkt al** — kan gewoon `git push` |
| `services/fidk/` | nog niet | — | git init nog te doen |
| `services/svg/` | nog niet | — | git init nog te doen |

## Manueel uit te voeren (door Seb, GitHub-side)

### 1. Maak twee nieuwe GitHub-repo's aan

Via webinterface (https://github.com/new) of `gh` CLI:

```sh
# Hub-repo (linktree + sites/{prjcts,kunstmijnoren} + content)
gh repo create seb-prjcts-be/sebastienvanblaere.be --private --description "Persoon-hub: linktree + identiteits-tweelingen prjcts + kunstmijnoren"

# Shared kader-repo (helpers, partials, decoration, thumb, parsedown)
gh repo create seb-prjcts-be/_shared --private --description "Gedeeld kader: header/menu/constellation, decoratie, thumb-generator, helpers"
```

### 2. Push lokale initial commits

```sh
# Hub
cd "C:/server/htdocs/sebastienvanblaere.be"
git remote add origin git@github.com:seb-prjcts-be/sebastienvanblaere.be.git
git branch -M main
git push -u origin main

# Shared
cd "C:/server/htdocs/_shared"
git remote add origin git@github.com:seb-prjcts-be/_shared.git
git branch -M main
git push -u origin main
```

### 3. (Optioneel) Repo's voor fidk + svg

Als je die wil versioneren:

```sh
# fidk
gh repo create seb-prjcts-be/fidk --private --description "fidk-service"
cd "C:/server/htdocs/sebastienvanblaere.be/services/fidk"
git init && git add . && git -c user.name="Seb Vanblaere" -c user.email="seb@prjcts.be" commit -m "init: from htdocs/LIVE_fidk/"
git remote add origin git@github.com:seb-prjcts-be/fidk.git
git branch -M main && git push -u origin main

# svg
gh repo create seb-prjcts-be/svg --private --description "SVG converter multi"
cd "C:/server/htdocs/sebastienvanblaere.be/services/svg"
git init && git add . && git -c user.name="Seb Vanblaere" -c user.email="seb@prjcts.be" commit -m "init: from htdocs/LIVE_SVG_converter_multi/"
git remote add origin git@github.com:seb-prjcts-be/svg.git
git branch -M main && git push -u origin main
```

## Niet-getrackte items in hub-repo (`/services/` is in .gitignore)
- `services/` zelf — elke service is eigen repo, niet meelopen in hub
- `**/.thumb/` — auto-gegenereerd door `_shared/thumb.php`
- `desktop.ini`, `.DS_Store`, etc.

## Productie-deploy nota's
Op productie verschijnen deze paden via:
- `prjcts.be/` → `htdocs/sebastienvanblaere.be/sites/prjcts/` (hub-`.htaccess` rewrite)
- `kunstmijnoren.be/` → `htdocs/sebastienvanblaere.be/sites/kunstmijnoren/` (idem)
- `p5.prjcts.be/` → `htdocs/p5.prjcts.be/` (= junction, dus effectief `services/creative_coding_site/`)
- (Toekomst) andere subdomains kunnen junctions of vhosts krijgen die naar `services/<naam>/` wijzen.

## Checks na manuele stappen
```sh
# Hub op GitHub bereikbaar?
gh repo view seb-prjcts-be/sebastienvanblaere.be

# Lokaal-naar-GitHub sync werkt?
cd "C:/server/htdocs/sebastienvanblaere.be"
git status   # → "your branch is up to date with origin/main"
```
