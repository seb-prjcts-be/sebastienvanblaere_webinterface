<?php
/**
 * Content-discovery helpers — uniform schema voor alle satellieten.
 *
 * Mappings:
 *   content/abouts/<key>/         → abouts (een per onderwerp)
 *       meta.json                   {title_nl, title_en, title_fr, image, logo, order}
 *       nl.md / en.md / fr.md       body per taal
 *
 *   content/projects/<artform>/   → artform (categorie)
 *       artform.json                {name_nl, name_en, order}
 *       <project>/                  → project binnen artform
 *           meta.json               {name_nl, name_en, subtitle_*, date, order}
 *           nl.md / en.md           body per taal
 *           *.{jpg,png,gif,mp4,...} → works (drop-folder, auto-discovered)
 *
 *   content/home/                 → home-tekst per taal
 *       nl.md / en.md
 */
declare(strict_types=1);

const MEDIA_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'mov', 'webm', 'mp3', 'ogg', 'pdf'];

function read_meta(string $path): array
{
    if (!is_file($path)) return [];
    $raw = file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function read_body(string $dir, string $lang, string $fallback = 'nl'): string
{
    $tries = [$dir . '/' . $lang . '.md'];
    if ($lang !== $fallback) $tries[] = $dir . '/' . $fallback . '.md';
    foreach ($tries as $p) {
        if (is_file($p)) {
            $raw = file_get_contents($p);
            return $raw !== false ? $raw : '';
        }
    }
    return '';
}

/** Optionele side-kolom content voor projects: leest `aside_<lang>.md`
 * uit de drop-folder. Leeg als bestand ontbreekt. */
function read_aside(string $dir, string $lang, string $fallback = 'nl'): string
{
    $tries = [$dir . '/aside_' . $lang . '.md'];
    if ($lang !== $fallback) $tries[] = $dir . '/aside_' . $fallback . '.md';
    foreach ($tries as $p) {
        if (is_file($p)) {
            $raw = file_get_contents($p);
            return $raw !== false ? $raw : '';
        }
    }
    return '';
}

/**
 * Render een .md-body als HTML.
 *  - Als de input al HTML lijkt (begint met `<` na trim), wordt het ongewijzigd teruggegeven
 *    (backward-compatible met bestaande HTML-in-md-bestanden).
 *  - Anders: door Parsedown — markdown → HTML.
 */
function render_md(string $raw): string
{
    $trimmed = ltrim($raw);
    if ($trimmed === '') return '';
    if ($trimmed[0] === '<') return $raw;     // Reeds HTML

    static $parser = null;
    if ($parser === null) {
        if (!class_exists('Parsedown')) {
            require_once __DIR__ . '/Parsedown.php';
        }
        $parser = new Parsedown();
        $parser->setSafeMode(false);          // Vertrouw admin-content (voor evt. inline HTML/iframes)
        $parser->setBreaksEnabled(true);      // Single-newline → <br> (Obsidian-stijl)
    }
    return $parser->text($raw);
}

function localised(array $meta, string $field, string $lang, string $fallback = 'nl'): string
{
    $key1 = $field . '_' . $lang;
    $key2 = $field . '_' . $fallback;
    if (isset($meta[$key1]) && $meta[$key1] !== '') return (string) $meta[$key1];
    if (isset($meta[$key2])) return (string) $meta[$key2];
    return '';
}

/**
 * Discover all abouts in a satellite's content folder.
 * Returns array of items, sorted by 'order' (then folder name).
 */
function discover_abouts(string $content_root, string $lang = 'nl'): array
{
    $base = $content_root . '/abouts';
    if (!is_dir($base)) return [];
    $items = [];
    foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $key  = basename($dir);
        $meta = read_meta($dir . '/meta.json');
        $items[] = [
            'key'   => $key,
            'dir'   => $dir,
            'title' => localised($meta, 'title', $lang),
            'media' => discover_works($dir),
            'meta'  => $meta,
        ];
    }
    usort($items, fn($a, $b) => strnatcmp($a['key'], $b['key']));
    return $items;
}

function read_about(string $content_root, string $key, string $lang = 'nl'): ?array
{
    $dir = $content_root . '/abouts/' . $key;
    if (!is_dir($dir)) return null;
    $meta = read_meta($dir . '/meta.json');
    return [
        'key'   => $key,
        'dir'   => $dir,
        'title' => localised($meta, 'title', $lang),
        'media' => discover_works($dir),
        'body'  => read_body($dir, $lang),
        'meta'  => $meta,
    ];
}

/**
 * Discover artforms (project-categories).
 */
function discover_artforms(string $content_root, string $lang = 'nl'): array
{
    $base = $content_root . '/projects';
    if (!is_dir($base)) return [];
    $items = [];
    foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $key  = basename($dir);
        $meta = read_meta($dir . '/artform.json');
        $items[] = [
            'key'   => $key,
            'dir'   => $dir,
            'name'  => localised($meta, 'name', $lang),
            'meta'  => $meta,
        ];
    }
    usort($items, fn($a, $b) => strnatcmp($a['key'], $b['key']));
    return $items;
}

/**
 * Discover projects within an artform.
 */
function discover_projects(string $artform_dir, string $lang = 'nl'): array
{
    if (!is_dir($artform_dir)) return [];
    $items = [];
    foreach (glob($artform_dir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $key  = basename($dir);
        $meta = read_meta($dir . '/meta.json');
        $items[] = [
            'key'      => $key,
            'dir'      => $dir,
            'name'     => localised($meta, 'name', $lang),
            'subtitle' => localised($meta, 'subtitle', $lang),
            'date'     => $meta['date'] ?? '',
            'meta'     => $meta,
        ];
    }
    usort($items, fn($a, $b) => strnatcmp($a['key'], $b['key']));
    return $items;
}

function read_project(string $content_root, string $artform_key, string $project_key, string $lang = 'nl'): ?array
{
    $dir = $content_root . '/projects/' . $artform_key . '/' . $project_key;
    if (!is_dir($dir)) return null;
    $meta = read_meta($dir . '/meta.json');

    // Optionele YouTube-lijst (sidebar-items) — youtube.json met items[{code,title}]
    $yt_file = $dir . '/youtube.json';
    $youtube = [];
    if (is_file($yt_file)) {
        $yt_data = json_decode((string) file_get_contents($yt_file), true);
        $youtube = $yt_data['items'] ?? [];
    }

    return [
        'key'      => $project_key,
        'dir'      => $dir,
        'name'     => localised($meta, 'name', $lang),
        'subtitle' => localised($meta, 'subtitle', $lang),
        'date'     => $meta['date'] ?? '',
        'body'     => read_body($dir, $lang),
        'aside'    => read_aside($dir, $lang),
        'works'    => discover_works($dir),
        'youtube'  => $youtube,
        'meta'     => $meta,
    ];
}

/**
 * Discover media files (works) within a project folder. Drop-folder principe:
 * elk media-bestand verschijnt automatisch.
 */
function discover_works(string $project_dir): array
{
    if (!is_dir($project_dir)) return [];
    $items = [];
    foreach (glob($project_dir . '/*') ?: [] as $path) {
        if (!is_file($path)) continue;
        $name = basename($path);
        if ($name[0] === '.' || $name[0] === '_') continue; // hidden / draft
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, MEDIA_EXT, true)) continue;
        $items[] = [
            'filename' => $name,
            'path'     => $path,
            'ext'      => $ext,
            'mtime'    => filemtime($path) ?: 0,
            'is_video' => in_array($ext, ['mp4', 'mov', 'webm'], true),
            'is_audio' => in_array($ext, ['mp3', 'ogg'], true),
            'is_pdf'   => $ext === 'pdf',
        ];
    }
    // sorteer op naam (drop een file met prefix 01-, 02-, ... om volgorde te bepalen)
    usort($items, fn($a, $b) => strnatcmp($a['filename'], $b['filename']));
    return $items;
}

function read_home_body(string $content_root, string $lang = 'nl'): string
{
    return read_body($content_root . '/home', $lang);
}

/**
 * Long-form geschriften — essays, verhalen, onderzoek, brieven, fysiolostrofieën.
 * content/writings/<NN-slug>/ met meta.json {title_*, subtitle_*, date, type}.
 *
 * Returns array gesorteerd op meta.date desc (recente bovenaan), fallback strnatcmp folder-naam desc.
 * Optioneel filter op `type` (essay|story|research|letter|physiolostrophy); leeg = alle.
 */
function discover_writings(string $content_root, string $lang = 'nl', string $type_filter = ''): array
{
    $base = $content_root . '/writings';
    if (!is_dir($base)) return [];
    $items = [];
    foreach (glob($base . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $key  = basename($dir);
        if ($key[0] === '.' || $key[0] === '_') continue; // hidden / draft
        $meta = read_meta($dir . '/meta.json');
        $type = $meta['type'] ?? '';
        if ($type_filter !== '' && $type !== $type_filter) continue;
        $items[] = [
            'key'      => $key,
            'dir'      => $dir,
            'title'    => localised($meta, 'title', $lang),
            'subtitle' => localised($meta, 'subtitle', $lang),
            'date'     => $meta['date'] ?? '',
            'type'     => $type,
            'meta'     => $meta,
        ];
    }
    // Sort: date desc → folder-key desc als tie-breaker
    usort($items, function ($a, $b) {
        $cmp = strcmp((string) $b['date'], (string) $a['date']);
        if ($cmp !== 0) return $cmp;
        return strnatcmp($b['key'], $a['key']);
    });
    return $items;
}

function read_writing(string $content_root, string $key, string $lang = 'nl'): ?array
{
    $dir = $content_root . '/writings/' . $key;
    if (!is_dir($dir)) return null;
    $meta = read_meta($dir . '/meta.json');
    return [
        'key'      => $key,
        'dir'      => $dir,
        'title'    => localised($meta, 'title', $lang),
        'subtitle' => localised($meta, 'subtitle', $lang),
        'date'     => $meta['date'] ?? '',
        'type'     => $meta['type'] ?? '',
        'body'     => read_body($dir, $lang),
        'aside'    => read_aside($dir, $lang),
        'media'    => discover_works($dir),
        'meta'     => $meta,
    ];
}

/**
 * Haal een schone plain-text meta-description (max ~$max_len tekens) uit een Markdown- of HTML-body.
 */
function extract_seo_description(string $raw, int $max_len = 160): string
{
    $trimmed = trim($raw);
    if ($trimmed === '') return '';
    $html = render_md($trimmed);
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));
    if ($text === '') return '';

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $max_len) return $text;
        $cut = mb_substr($text, 0, $max_len - 1, 'UTF-8');
        $last_space = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($last_space !== false && $last_space > (int) ($max_len * 0.6)) {
            $cut = mb_substr($cut, 0, $last_space, 'UTF-8');
        }
        return rtrim($cut, " \t\n\r\0\x0B.,;:-") . '…';
    }

    if (strlen($text) <= $max_len) return $text;
    $cut = substr($text, 0, $max_len - 3);
    $last_space = strrpos($cut, ' ');
    if ($last_space !== false && $last_space > (int) ($max_len * 0.6)) {
        $cut = substr($cut, 0, $last_space);
    }
    return rtrim($cut, " \t\n\r\0\x0B.,;:-") . '...';
}

/**
 * Vind het eerste geschikte afbeeldingspad (relatief t.o.v. $satellite_root) uit een lijst works.
 */
function first_seo_image(array $works, string $satellite_root): string
{
    $img_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $sat_real = realpath($satellite_root);
    if ($sat_real === false) return '';
    foreach ($works as $w) {
        $ext = strtolower((string) ($w['ext'] ?? ''));
        if (!in_array($ext, $img_exts, true)) continue;
        $abs = realpath((string) ($w['path'] ?? ''));
        if ($abs !== false && strncmp($abs, $sat_real . DIRECTORY_SEPARATOR, strlen($sat_real) + 1) === 0) {
            return '/' . ltrim(str_replace('\\', '/', substr($abs, strlen($sat_real))), '/');
        }
    }
    return '';
}

/**
 * Automatische diepe SEO-detectie vóórdat render_header() wordt aangeroepen in dispatch().
 * Leest (indien beschikbaar) de specifieke about, project of writing voor rijke titels,
 * meta-descriptions, Open Graph images en JSON-LD schema types.
 */
function resolve_page_seo(string $satellite_root, string $page_key, array $cfg, string $lang = 'nl'): array
{
    $content_root = $satellite_root . '/content';
    $seo = [
        'title'          => $page_key === 'home' ? null : $page_key,
        'description'    => null,
        'og_title'       => null,
        'og_image'       => null,
        'og_type'        => 'website',
        'schema_type'    => 'WebPage',
        'published_time' => null,
        'is_404'         => ($page_key === '404'),
    ];

    if ($page_key === '404') {
        $seo['title'] = '404';
        return $seo;
    }

    $sanitize_slug = static fn(string $v): string => (string) preg_replace('/[^a-zA-Z0-9_-]/', '', $v);

    if ($page_key === 'home') {
        $home_body = read_home_body($content_root, $lang);
        $excerpt = extract_seo_description($home_body);
        if ($excerpt !== '') {
            $seo['description'] = $excerpt;
        }
        return $seo;
    }

    if ($page_key === 'about') {
        $about_key = $sanitize_slug((string) ($_GET['key'] ?? ''));
        if ($about_key !== '') {
            $about = read_about($content_root, $about_key, $lang);
            if ($about !== null) {
                $seo['title'] = $about['title'] !== '' ? $about['title'] : $about_key;
                $desc = extract_seo_description($about['body']);
                if ($desc !== '') $seo['description'] = $desc;
                $img = first_seo_image($about['media'], $satellite_root);
                if ($img !== '') $seo['og_image'] = $img;
                $seo['schema_type'] = 'AboutPage';
            }
        }
        return $seo;
    }

    if ($page_key === 'projects') {
        $artform_key = $sanitize_slug((string) ($_GET['artform'] ?? ''));
        $project_key = $sanitize_slug((string) ($_GET['project'] ?? ''));
        if ($artform_key !== '' && $project_key !== '') {
            $proj = read_project($content_root, $artform_key, $project_key, $lang);
            if ($proj !== null) {
                $name = $proj['name'] !== '' ? $proj['name'] : $project_key;
                $sub  = $proj['subtitle'];
                $seo['title'] = $sub !== '' ? ($name . ' — ' . $sub) : $name;
                $desc = extract_seo_description($proj['body']);
                if ($desc === '' && $sub !== '') $desc = $sub;
                if ($desc !== '') $seo['description'] = $desc;
                $img = first_seo_image($proj['works'], $satellite_root);
                if ($img !== '') $seo['og_image'] = $img;
                $seo['og_type'] = 'article';
                $seo['schema_type'] = 'VisualArtwork';
                if ($proj['date'] !== '') $seo['published_time'] = (string) $proj['date'];
            }
        } elseif ($artform_key !== '') {
            $meta = read_meta($content_root . '/projects/' . $artform_key . '/artform.json');
            $art_name = localised($meta, 'name', $lang);
            if ($art_name !== '') $seo['title'] = $art_name;
            $seo['schema_type'] = 'CollectionPage';
        }
        return $seo;
    }

    if ($page_key === 'writings') {
        $writing_key = $sanitize_slug((string) ($_GET['key'] ?? ''));
        if ($writing_key !== '') {
            $w = read_writing($content_root, $writing_key, $lang);
            if ($w !== null) {
                $title = $w['title'] !== '' ? $w['title'] : $writing_key;
                $sub   = $w['subtitle'];
                $seo['title'] = $sub !== '' ? ($title . ' — ' . $sub) : $title;
                $desc = extract_seo_description($w['body']);
                if ($desc === '' && $sub !== '') $desc = $sub;
                if ($desc !== '') $seo['description'] = $desc;
                $img = first_seo_image($w['media'], $satellite_root);
                if ($img !== '') $seo['og_image'] = $img;
                $seo['og_type'] = 'article';
                $seo['schema_type'] = 'Article';
                if ($w['date'] !== '') $seo['published_time'] = (string) $w['date'];
            }
        } else {
            $seo['schema_type'] = 'CollectionPage';
        }
        return $seo;
    }

    if (in_array($page_key, ['bio', 'contact', 'inspiration', 'guests'], true)) {
        $body = read_body($content_root . '/' . $page_key, $lang);
        $desc = extract_seo_description($body);
        if ($desc !== '') $seo['description'] = $desc;
        if ($page_key === 'bio') $seo['schema_type'] = 'ProfilePage';
        if ($page_key === 'contact') $seo['schema_type'] = 'ContactPage';
    }

    return $seo;
}
