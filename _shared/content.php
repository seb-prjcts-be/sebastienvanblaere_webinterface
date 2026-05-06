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
