# UI-componenten — gedeeld kader voor alle satellieten

Vier UI-componenten leven in `_shared/` en werken automatisch op elke satelliet. Voor toekomstige satellieten (`p5`, `waves`, `export`, ...) gewoon `dispatch()` aanroepen vanuit `index.php` — al deze componenten worden geladen.

```
sebastien (hub)
└─ <satelliet>
   ├─ menu (links, sidepanel)         ← per-satelliet content via menu_items.php
   ├─ header (boven)                  ← gedeeld, automatisch
   ├─ constellation (rechts)          ← gedeeld, automatisch
   └─ main content                    ← per-satelliet pages/<key>.php
```

---

## 1. Top bar — `_shared/partials/header.php`

**Visuele zone**: vaste balk bovenaan, full-width.
**Inhoud**: hub-back-link `← sebastien` + satelliet-titel + tools (lang-switch + fontsize).

### Wat krijgt de satelliet gratis
- **Hub-back link**: `← sebastien · <title>` — werkt mode-aware (path-mode lokaal, host-mode productie) via `link_to()`.
- **Lang-tool**: drie chips `NL / EN / FR`. Klik → `?lang=<code>` → server-side redirect naar schone URL met cookie + sessie.
- **Fontsize-tool**: drie A-knoppen (klein/medium/groot). Klik → JS zet `data-fontsize` op `<html>`, schrijft `localStorage` + cookie.
- **FOUC-free**: een inline `<script>` in de `<head>` leest cookie+localStorage en zet `data-menu-state` + `data-fontsize` op `<html>` vóór de paint. Geen flash van foute state.
- **SEO/social meta**: `<title>`, `description`, `keywords`, `author`, OpenGraph (title, url, image, description), Facebook app-id — allemaal automatisch uit `$cfg` (config.php) met optionele per-pagina override via `$page['title' | 'description' | 'og_title' | 'og_image']`.
- **Favicon**: opgepikt als `<satelliet>/favicon.ico` bestaat, anders niets.
- **Per-satelliet `assets/site.css`**: automatisch ingeladen na shared.css (kan dus overschrijven).
- **CSS-variabelen op `:root`**: `--accent`, `--density`, `--frame-padding` — gegenereerd uit `$cfg` via `build_css_vars()`.

### Wat moet de satelliet aanleveren
Niets verplichts. In `_shared/config.php` config-entry zetten met velden `title`, `register`, `density`, `accent` (voor styling) en — optioneel — `tagline`, `keywords`, `author`, `og_title`, `og_image`, `fb_app_id` (voor SEO/social).

### Per-pagina aanpassingen
Vanuit `pages/<key>.php` kan je `$page['title']`, `$page['description']`, `$page['og_image']`, `$page['og_title']` zetten vóór de eerste output (let op: render_header is al gedaan via dispatch — voor huidige flow betekent dit dat per-pagina meta-overrides niet trivially werken; voor nu via `$cfg`-overrides per satelliet).

---

## 2. Right sidebar — `_shared/partials/constellation.php`

**Visuele zone**: rechts-offcanvas drawer (420px breed), opent vanuit een `▦▦`-knop rechtsboven.
**Inhoud**: linktree-stijl naar alle satellieten + externe links (Instagram, LinkedIn).

### Wat krijgt de satelliet gratis
- **Volledig automatisch geladen** voor elke satelliet (zit in `header.php` na `</header>`).
- **5 satelliet-kaarten** + 2 externe links — hardcoded in `constellation.php`. Volgorde matcht `sebastienvanblaere.be/index.php` (de linktree).
- **Current marker**: de huidige satelliet krijgt `.is-current` class — gedimd, niet klikbaar (`pointer-events: none`).
- **Cross-site lang-sync**: huidige `?lang=<code>` wordt meegeven aan de cross-site URL zodat taal blijft hangen tussen satellieten.
- **Cross-site fontsize-sync**: idem via cookie (`prjcts_fontsize`) — server-leesbaar door alle satellieten op dezelfde top-domain.
- **Register-typografie per kaart**: kaarten erven typografie van `[data-register="..."]` (poetic = serif italic, technical = mono, educational = sans-serif, personal = serif).
- **Push-effect**: open constellation duwt `.main-wrapper` 420px naar links via `html[data-constellation-state="open"]`-state.

### Wat moet de satelliet aanleveren
**Niets**. Constellation rendert identiek op elke satelliet. Wil je een satelliet toevoegen of de volgorde wijzigen → één plek: het `$items`-array in `constellation.php`.

### Toevoegen van een nieuwe satelliet aan de constellation
Edit `_shared/partials/constellation.php`, voeg een entry toe:
```php
['type' => 'sat', 'label' => 'jouw label', 'key' => '<satelliet-key>', 'desc' => 'kort'],
```
De `key` moet matchen met `_shared/config.php['satellites'][<key>]`.

---

## 3. Left menu — `_shared/partials/menu-sidepanel.php`

**Visuele zone**: links-offcanvas drawer (350px breed), **default open** op desktop.
**Inhoud**: data-driven per-satelliet vanuit `menu_items.php`.

### Wat krijgt de satelliet gratis
- **Open-by-default** + persisted via `localStorage['prjcts_menu']` → blijft open/dicht over reloads en cross-satelliet.
- **FOUC-free toggle** via `data-menu-state="open|closed"` op `<html>` (ingesteld vóór paint door inline init-script in header).
- **Push-effect**: open menu duwt `.main-wrapper` 350px naar rechts (mobile: full-screen overlay i.p.v. push).
- **Auto `aria-current="page"`** op de huidige link (matcht pathname + querystring zodat `/about?key=04-brein` ≠ `/about?key=05-deleuze`).
- **Render van item-types**: `home`, `header`, `link`, `category`, `divider`, `external`, `satellite` (cross-satelliet via `link_to()`).
- **Esc-toets** sluit het menu (én constellation).

### Wat moet de satelliet aanleveren
`sites/<key>/menu_items.php` retourneert een array van items. Voorbeeld:
```php
<?php
declare(strict_types=1);
$root = $satellite_root . '/content';
$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';

$items = [
    ['type' => 'home',     'label' => $cfg['home_label'] ?? $cfg['title']],
    ['type' => 'header',   'label' => 'Sebastien Vanblaere',
                            'href' => 'https://www.instagram.com/prjcts.be/', 'external' => true],
];

// Auto-discovery van abouts/projects:
foreach (discover_abouts($root, $lang) as $a) {
    $items[] = ['type' => 'link', 'label' => $a['title'] ?: $a['key'],
                'href' => '/about?key=' . $a['key']];
}

$items[] = ['type' => 'divider'];
$items[] = ['type' => 'external', 'label' => 'p5.waves library',
            'href' => 'https://github.com/seb-prjcts-be/p5.waves'];

// Eind-pagina's:
$items[] = ['type' => 'link', 'label' => 'Contact',  'href' => '/contact'];

return $items;
```

Geen `menu_items.php`? Het menu wordt overgeslagen (geen sidepanel-render, hamburger-toggler ontbreekt). Toggler verschijnt enkel als er items zijn.

---

## 4. Lightbox — `_shared/assets/menu.js`

Auto-bootstrapped overlay voor klik op elementen met `data-src` + `data-type`. Vijf types: `image`, `video`, `audio`, `pdf`, `youtube`.

| `data-type` | Render in lightbox |
|---|---|
| `image` | `<img src>` |
| `video` | `<video controls autoplay loop>` |
| `audio` | `<audio controls autoplay>` (gecentreerd, max-width 600px) |
| `pdf` | `<iframe>` (full-screen) |
| `youtube` | `<iframe>` met YouTube allow-attrs |

### Hoe gebruiken in pages
```html
<button class="thumbnail-container"
        data-src="/path/to/image.jpg"
        data-type="image"
        aria-label="...">
    <img src="/path/to/image.jpg">
</button>
```
Klik → fullscreen overlay opent, Esc/klik buiten/×-knop sluit. Geen extra JS nodig.

---

## 5. Persistence-keys (localStorage + cookies)

| Key | Storage | Default | Server-leesbaar | Doel |
|---|---|---|---|---|
| `prjcts_menu` | localStorage | `'open'` | ❌ JS-only | Sidepanel open/closed state |
| `prjcts_fontsize` | localStorage **+** cookie | `'medium'` | ✅ via cookie | Fontsize klein/medium/groot, cross-site sync |
| `prjcts_lang` | cookie + sessie | `'nl'` | ✅ | Taal-keuze, server gebruikt voor content-render |
| `ballsState` | localStorage | none | ❌ | p5-balletjes positie/snelheid (decoration.js) |

Alle cookies hebben `samesite=Lax`, `secure`-flag dynamisch op HTTPS, géén `httponly` (JS-readable nodig voor `prjcts_lang`/`prjcts_fontsize`). PHPSESSID heeft wel `httponly`.

---

## Content-types per pagina-pattern

| Content-folder | Helper(s) | Page | URL | Drop-folder media |
|---|---|---|---|---|
| `home/` | `read_home_body()` | `pages/home.php` | `/` | nee (home.md alleen) |
| `abouts/<slug>/` | `discover_abouts()`, `read_about()` | `pages/about.php` | `/about?key=<slug>` | ja (`about-media`) |
| `projects/<artform>/<slug>/` | `discover_artforms()`, `discover_projects()`, `read_project()` | `pages/projects.php` | `/projects?artform=<a>&project=<p>` | ja (works-grid + lightbox) |
| `writings/<slug>/` | `discover_writings()`, `read_writing()` | `pages/writings.php` | `/writings` + `?key=<slug>` + `?type=<essay\|story\|research\|letter>` | ja (drop in folder) |
| `bio/` | `read_body()` | `pages/bio.php` | `/bio` | nee |
| `inspiration/` | `read_body()` + `youtube.json` | `pages/inspiration.php` | `/inspiration` | optioneel |
| `contact/` | `read_body()` + `discover_works()` | `pages/contact.php` | `/contact` | optioneel (foto) |
| `guests/` | optionele intro `<lang>.md` | `pages/guests.php` | `/guests` | nee (Elfsight widget) |

**Markdown-rendering**: alle bodies kunnen door `render_md()` als ze met Markdown beginnen. HTML-bodies (oude stijl, `<p>...`) worden ongewijzigd doorgegeven. Backward-compatible.

**Aside-content**: `aside_<lang>.md` per item-folder werkt automatisch voor projects (en writings), gerendered in `prjcts-col-side` of onderaan article.

**Media-types in works-grid**: image (thumb via `thumb_url`), video (`<video preload=metadata>`), audio (icon + filename → lightbox `<audio>`), pdf (icon + filename → lightbox `<iframe>`).

## Een nieuwe satelliet bouwen — checklist

Stel je gaat `waves` (p5.waves library showcase) bouwen.

1. **Config-entry toevoegen** aan `_shared/config.php` onder `satellites`:
   ```php
   'waves' => [
       'host'        => 'waves.prjcts.be',
       'title'       => 'p5.waves',
       'description' => 'p5.js wave-formules',
       'register'    => 'technical',     // → mono-typografie
       'density'     => 0.7,
       'accent'      => '#ff0000',
       // optioneel: 'in_hub' => true (als deel van hub-folder, niet solo-satelliet)
       // optioneel: 'decoration' => [...] (voor p5-balletjes — zie DECORATION.md)
   ],
   ```

2. **Maak `sites/waves/`** (als hub-satelliet) of `htdocs/waves.prjcts.be/` (solo):
   ```
   sites/waves/
   ├── .htaccess              # RewriteRule ^ index.php [L]
   ├── index.php              # 3 regels: bootstrap + dispatch
   ├── menu_items.php         # data-array (zie boven)
   ├── pages/
   │   ├── home.php
   │   └── 404.php
   ├── assets/
   │   └── site.css           # (optioneel) satelliet-specifieke tweaks
   └── content/
       └── home/
           ├── nl.md
           └── en.md
   ```

3. **`index.php`** (boilerplate, identiek voor elke satelliet):
   ```php
   <?php
   declare(strict_types=1);
   require __DIR__ . '/../../../_shared/bootstrap.php';
   dispatch(__DIR__, $current, $config);
   ```

4. **`.htaccess`** (front-controller pattern):
   ```
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^ index.php [L]
   ```

5. **Hub-`.htaccess`** (`sebastienvanblaere.be/.htaccess`) voor host-routing in productie:
   ```
   RewriteCond %{HTTP_HOST} ^(www\.)?waves\.prjcts\.be$ [NC]
   RewriteCond %{REQUEST_URI} !^/sites/
   RewriteRule ^(.*)$ sites/waves/$1 [L]
   ```

6. **Constellation-link** (optioneel, in `_shared/partials/constellation.php`): de waves-satelliet zit er al in. Voor een nieuwe satelliet daar toevoegen.

7. **Smoke-tests**:
   - `localhost/sebastienvanblaere.be/sites/waves/` toont home + sidepanel + tools
   - `?lang=en` redirect → schone URL + content vertaald
   - Constellation-knop opent rechts → 5 satellieten zichtbaar, current = waves gedimd
   - `/?fontsize=groot` schaalt rem-waarden mee

Klaar. De UI is identiek over alle satellieten — verschillen leven in `register`/`accent`/`density` (CSS-vars + register-overrides) en in de menu_items.php + content.
