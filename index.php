<?php
/**
 * sebastienvanblaere.be — landing / linktree.
 * De zichtbare inhoud komt uit services/constellation.json.
 */
declare(strict_types=1);

$config = require __DIR__ . '/_shared/config.php';
if (!is_array($config)) $config = [];

$satellites = isset($config['satellites']) && is_array($config['satellites'])
    ? $config['satellites']
    : [];

$host     = (string) ($_SERVER['HTTP_HOST'] ?? '');
$hostname = strtolower((string) preg_replace('/:\d+$/', '', $host));
$hostname = trim($hostname, '[]');

$key_is_safe = static fn(string $k): bool =>
    preg_match('/^[A-Za-z0-9._-]+$/', $k) === 1 && !str_contains($k, '..');

if ($hostname !== '') {
    foreach ($satellites as $key => $sat) {
        if (!is_array($sat) || empty($sat['in_hub'])) continue;
        if (strtolower((string) ($sat['host'] ?? '')) !== $hostname) continue;
        if (!$key_is_safe((string) $key)) continue;
        header('Location: /sites/' . $key . '/', true, 302);
        exit;
    }
}

$is_local = in_array($hostname, ['localhost', '127.0.0.1', '::1'], true);
$hub_root = (string) ($config['hub_root'] ?? 'sebastienvanblaere.be');

$site_url = function (string $key) use ($satellites, $is_local, $hub_root): string {
    $sat = $satellites[$key] ?? null;
    if (!is_array($sat)) return '#';
    if (!empty($sat['external_url'])) return (string) $sat['external_url'];
    $sat_host = (string) ($sat['host'] ?? '');
    if ($sat_host === '') return '#';
    if ($is_local) return '/' . $hub_root . '/' . $sat_host . '/';
    return 'https://' . $sat_host . '/';
};

$constellation_path = __DIR__ . '/services/constellation.json';
if (!is_file($constellation_path)) {
    $constellation_path = __DIR__ . '/constellation.json';
}

$constellation_raw = is_file($constellation_path) ? file_get_contents($constellation_path) : false;
$constellation     = is_string($constellation_raw) ? json_decode($constellation_raw, true) : null;

if (!is_array($constellation)) {
    if ($is_local) {
        die('constellation.json niet geladen (' . $constellation_path . '): ' . json_last_error_msg());
    }
    $constellation = [];
}

$profile = isset($constellation['profile']) && is_array($constellation['profile'])
    ? $constellation['profile']
    : [];
$items = isset($constellation['items']) && is_array($constellation['items'])
    ? $constellation['items']
    : [];

// Expose the individual wave projects instead of the combined launcher card.
$wave_tools = [
    ['key' => 'waves_lab', 'label' => 'p5.waves_lab', 'url' => 'https://seb-prjcts-be.github.io/p5.waves_lab/', 'external' => true],
    ['key' => 'waves_snippets', 'label' => 'p5.waves_snippets', 'url' => 'https://seb-prjcts-be.github.io/p5.waves_snippets/', 'external' => true],
];
$wave_libraries = [
    ['key' => 'p5waves', 'label' => 'p5.waves', 'url' => 'https://seb-prjcts-be.github.io/p5.waves/', 'external' => true],
    // Lab and snippets sit directly under p5.waves as two half-width cards.
    ['type' => 'pair', 'items' => $wave_tools],
    ['key' => 'processingwaves', 'label' => 'processing.waves', 'url' => 'https://seb-prjcts-be.github.io/processing.waves/', 'external' => true],
    ['key' => 'vanillawaves', 'label' => 'vanilla.waves', 'url' => 'https://seb-prjcts-be.github.io/vanilla.waves/', 'external' => true],
];
$expanded_items = [];
foreach ($items as $item) {
    if (!is_array($item)) continue;
    if (($item['key'] ?? '') === 'p5waves') {
        array_push($expanded_items, ...$wave_libraries);
        continue;
    }
    // Update the homepage even when services still supplies the older FIDK entry.
    if (rtrim((string) ($item['url'] ?? ''), '/') === 'https://prjcts.be/services/fidk') {
        $item['label'] = 'Audiovisueel';
        $item['url'] = 'https://sebastienvanblaere.be/services/audiovisueel/';
        unset($item['localPath']);
    }
    // The section heading already states whether a project is a library or service.
    $item['label'] = preg_replace('/ \((?:library|service)\)$/', '', (string) ($item['label'] ?? ''));
    $expanded_items[] = $item;
    if (($item['key'] ?? '') === 'gysin') {
        $expanded_items[] = ['type' => 'pair', 'items' => [
            ['key' => 'vanillapenplotter', 'label' => 'vanilla.penplotter', 'url' => 'https://seb-prjcts-be.github.io/vanilla.penplotter/', 'external' => true],
            ['key' => 'p5penplotter', 'label' => 'p5.penplotter', 'url' => 'https://seb-prjcts-be.github.io/p5.penplotter/', 'external' => true],
        ]];
    }
    // The Chrome extension closes the services, after the SVG converter.
    if (rtrim((string) ($item['url'] ?? ''), '/') === 'https://prjcts.be/services/svg') {
        $expanded_items[] = [
            // Not "MD Block": that reads like one of the blocks projects.
            'key' => 'mdblocker',
            'label' => '.md blocker for Google Docs',
            'url' => 'https://seb-prjcts-be.github.io/google_docs_md_filter/',
            'external' => true,
        ];
    }
}
$items = $expanded_items;

// Apps travel with the hub, even when services supplies the live project list.
$apps_path = __DIR__ . '/apps/catalog.php';
$apps = is_file($apps_path) ? require $apps_path : [];
// Place apps just before the CV (its Resume heading when present).
$apps_position = count($items);
foreach ($items as $position => $item) {
    if (!is_array($item)) continue;
    $is_resume_header = ($item['type'] ?? '') === 'header' && strtolower((string) ($item['label'] ?? '')) === 'resume';
    $is_cv_link = str_contains((string) ($item['url'] ?? ''), '/services/cv');
    if ($is_resume_header || $is_cv_link) {
        $apps_position = $position;
        break;
    }
}
// Inspiration follows Education, ahead of the apps.
$inspiration = [
    ['type' => 'header', 'label' => 'Inspiration'],
    ['label' => 'Seb_prjcts_most loved', 'url' => 'https://www.youtube.com/playlist?list=PLbJ6hUkwmWttjtUlWWRQKycCGI6iFxFP0', 'external' => true],
];
$apps_section = $apps ? array_merge([['type' => 'header', 'label' => 'Apps']], $apps) : [];
array_splice($items, $apps_position, 0, array_merge($inspiration, $apps_section));

// Group the existing leading links without rewriting the shared catalog.
$section_starts = ['prjcts' => 'Art', 'p5waves' => 'Libraries', 'export' => 'Services'];
$grouped_items = [];
foreach ($items as $item) {
    if (!is_array($item)) continue;
    if (($item['type'] ?? '') === 'header' && ($item['label'] ?? '') === 'Education') {
        $grouped_items[] = ['type' => 'header', 'label' => 'Play'];
        $grouped_items[] = [
            'key' => 'checkboxdance',
            'label' => 'Checkboxdance',
            'url' => 'https://prjcts.be/services/checkboxdance/',
            'external' => true,
        ];
    }
    $section = $section_starts[(string) ($item['key'] ?? '')] ?? null;
    if ($section !== null) {
        $grouped_items[] = ['type' => 'header', 'label' => $section];
    }
    $grouped_items[] = $item;
}
$items = $grouped_items;

$library_listings = [
    'p5waves' => ['label' => 'Listed in p5.js Libraries', 'url' => 'https://p5js.org/libraries/directory/'],
    'gysin' => ['label' => 'Listed in p5.js Libraries', 'url' => 'https://p5js.org/libraries/directory/'],
    'processingwaves' => ['label' => 'Listed in Processing Contributions', 'url' => 'https://github.com/processing/processing-contributions/blob/main/contributions.yaml'],
];

$socials = isset($profile['socials']) && is_array($profile['socials'])
    ? $profile['socials']
    : [];

$hub = isset($config['hub']) && is_array($config['hub']) ? $config['hub'] : [];

$page_title = (string) ($profile['name'] ?? $hub['title'] ?? 'Sebastien Vanblaere');
$page_desc  = (string) ($hub['description'] ?? '');
// Keep the hub's introductory copy independent of the shared project catalog.
$page_tag   = (string) ($hub['tagline'] ?? $profile['tagline'] ?? '');
$page_auth  = (string) ($hub['author'] ?? '');
$og_title   = (string) ($hub['og_title'] ?? $page_title);
$og_image   = (string) ($hub['og_image'] ?? '');
$canonical  = $is_local ? '/' . $hub_root . '/' : 'https://' . $hub_root . '/';

$page_kws = '';
if (isset($hub['keywords'])) {
    $page_kws = is_array($hub['keywords'])
        ? implode(', ', array_map('strval', $hub['keywords']))
        : (string) $hub['keywords'];
}

if ($og_image !== '' && !preg_match('#^https?://#', $og_image)) {
    $abs_base = $is_local ? '/' . $hub_root : 'https://' . $hub_root;
    $og_image = $abs_base . '/' . ltrim($og_image, '/');
}

$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$render_label = static fn(string $s): string => nl2br($esc($s), false);

// Projects with their own visual language get an image slot beside the card on hover.
// Services, education, apps and the CV keep the plain hover. See docs/hover-handtekeningen.md.
$vak_keys = [
    'prjcts', 'kunstmijnoren',
    'p5waves', 'waves_lab', 'waves_snippets', 'processingwaves', 'vanillawaves',
    'gysin', 'vanillapenplotter', 'p5penplotter', 'blocks.system',
    'checkboxdance',
];
$vak_attr = static function (array $item) use ($vak_keys, $esc): string {
    $key = (string) ($item['key'] ?? '');
    if (!in_array($key, $vak_keys, true)) return '';
    return ' data-vak="' . $esc($key) . '"';
};

$item_url = function (array $item) use ($is_local, $site_url): string {
    if ($is_local && !empty($item['localPath'])) return (string) $item['localPath'];
    if ($is_local && !empty($item['key'])) {
        $local = $site_url((string) $item['key']);
        if ($local !== '#') return $local;
    }
    $url = $item['url'] ?? '#';
    return is_string($url) ? $url : '#';
};

$prod_base = 'https://' . $hub_root;
$prod_canonical = $prod_base . '/';
$prod_og_image = $og_image;
if ($prod_og_image !== '' && !preg_match('#^https?://#', $prod_og_image)) {
    $prod_og_image = $prod_base . '/' . ltrim((string) ($hub['og_image'] ?? ''), '/');
}

$same_as = isset($hub['same_as']) && is_array($hub['same_as']) ? array_values(array_map('strval', $hub['same_as'])) : [];
foreach ($socials as $social) {
    if (is_array($social) && !empty($social['url'])) {
        $s_url = (string) $social['url'];
        if (!in_array($s_url, $same_as, true)) {
            $same_as[] = $s_url;
        }
    }
}

$knows_about = isset($hub['knows_about']) && is_array($hub['knows_about'])
    ? array_values(array_map('strval', $hub['knows_about']))
    : [];
$job_title = (string) ($hub['job_title'] ?? 'Digital Artist, Creative Technologist & Educator');
$email = (string) ($profile['email'] ?? '');

// Build ItemList for JSON-LD Knowledge Graph (excluding non-clickable app descriptions).
$list_elements = [];
$current_section = '';
$pos = 1;
$push_schema_item = static function (array $entry, string $section) use (&$list_elements, &$pos, $prod_canonical, $library_listings): void {
    $lbl = trim(preg_replace('/\s+/', ' ', (string) ($entry['label'] ?? '')));
    $u   = (string) ($entry['url'] ?? '');
    if ($lbl === '' || $u === '' || $u === '#') return;
    $key = (string) ($entry['key'] ?? '');

    $entity_type = 'CreativeWork';
    if ($section === 'Libraries') {
        $entity_type = 'SoftwareSourceCode';
    } elseif ($section === 'Education') {
        $entity_type = 'LearningResource';
    } elseif ($section === 'Art') {
        $entity_type = 'WebSite';
    }

    $thing = [
        '@type'  => $entity_type,
        'name'   => $lbl,
        'url'    => $u,
        'author' => ['@id' => $prod_canonical . '#person'],
    ];
    if ($entity_type === 'SoftwareSourceCode') {
        $thing['programmingLanguage'] = str_starts_with($lbl, 'processing.') ? 'Processing' : 'JavaScript';
        if (isset($library_listings[$key]['url'])) {
            $thing['sameAs'] = $library_listings[$key]['url'];
        }
    }
    $list_elements[] = [
        '@type'    => 'ListItem',
        'position' => $pos++,
        'item'     => $thing,
    ];
};

foreach ($items as $item) {
    if (!is_array($item)) continue;
    $t = (string) ($item['type'] ?? 'link');
    if ($t === 'header') {
        $current_section = (string) ($item['label'] ?? '');
        continue;
    }
    if ($t === 'description') continue;
    if ($t === 'pair') {
        foreach (($item['items'] ?? []) as $pair_item) {
            if (is_array($pair_item)) {
                $push_schema_item($pair_item, $current_section);
            }
        }
        continue;
    }
    $push_schema_item($item, $current_section);
}

$person_node = [
    '@type'         => 'Person',
    '@id'           => $prod_canonical . '#person',
    'name'          => $page_title,
    'alternateName' => ['Seb Vanblaere', 'prjcts', 'kunstmijnoren'],
    'jobTitle'      => $job_title,
    'description'   => $page_desc,
    'url'           => $prod_canonical,
    'nationality'   => ['@type' => 'Country', 'name' => 'Belgium'],
    'knowsLanguage' => ['nl', 'en', 'fr'],
];
if ($prod_og_image !== '') $person_node['image'] = $prod_og_image;
if ($email !== '')         $person_node['email'] = 'mailto:' . $email;
if ($same_as)              $person_node['sameAs'] = $same_as;
if ($knows_about)          $person_node['knowsAbout'] = $knows_about;

$json_ld = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'      => 'ProfilePage',
            '@id'        => $prod_canonical . '#profilepage',
            'url'        => $prod_canonical,
            'name'       => $og_title,
            'description'=> $page_desc,
            'inLanguage' => 'en',
            'isPartOf'   => ['@id' => $prod_canonical . '#website'],
            'mainEntity' => ['@id' => $prod_canonical . '#person'],
        ],
        [
            '@type'       => 'WebSite',
            '@id'         => $prod_canonical . '#website',
            'url'         => $prod_canonical,
            'name'        => $page_title,
            'description' => $page_desc,
            'inLanguage'  => ['en', 'nl', 'fr'],
            'publisher'   => ['@id' => $prod_canonical . '#person'],
        ],
        $person_node,
        [
            '@type'           => 'ItemList',
            '@id'             => $prod_canonical . '#projects',
            'name'            => $page_title . ' — Art, Libraries, Services & Education',
            'numberOfItems'   => count($list_elements),
            'itemListElement' => $list_elements,
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#faf9f5">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<title><?= $esc($page_title) ?></title>

<?php if ($page_desc): ?>
<meta name="description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>
<?php if ($page_kws): ?>
<meta name="keywords" content="<?= $esc($page_kws) ?>">
<?php endif; ?>
<?php if ($page_auth): ?>
<meta name="author" content="<?= $esc($page_auth) ?>">
<?php endif; ?>

<meta property="og:type" content="profile">
<meta property="og:site_name" content="<?= $esc($page_title) ?>">
<meta property="og:locale" content="en_GB">
<meta property="og:locale:alternate" content="nl_BE">
<meta property="og:locale:alternate" content="fr_BE">
<meta property="og:url" content="<?= $esc($canonical) ?>">
<meta property="og:title" content="<?= $esc($og_title) ?>">
<?php if ($page_desc): ?>
<meta property="og:description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>
<?php if ($og_image): ?>
<meta property="og:image" content="<?= $esc($og_image) ?>">
<meta property="og:image:alt" content="<?= $esc($page_title) ?>">
<?php endif; ?>

<meta name="twitter:card" content="<?= $og_image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= $esc($og_title) ?>">
<?php if ($page_desc): ?>
<meta name="twitter:description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>
<?php if ($og_image): ?>
<meta name="twitter:image" content="<?= $esc($og_image) ?>">
<meta name="twitter:image:alt" content="<?= $esc($page_title) ?>">
<?php endif; ?>

<link rel="canonical" href="<?= $esc($canonical) ?>">
<link rel="alternate" type="text/plain" href="/llms.txt" title="LLM Context">
<?php foreach ($same_as as $me_url): ?>
<link rel="me" href="<?= $esc($me_url) ?>">
<?php endforeach; ?>
<script type="application/ld+json"><?= json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=IBM+Plex+Mono:wght@300;400&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { min-height: 100%; }
    body {
        background: #faf9f5;
        color: #080808;
        font-family: 'IBM Plex Mono', 'Courier New', monospace;
        font-weight: 300;
        line-height: 1.6;
        padding: 4rem clamp(1.5rem, 5vw, 3rem);
        max-width: 560px;
        margin: 0 auto;
    }
    header { text-align: center; margin-bottom: 2.5rem; }
    h1 {
        font-family: 'Oswald', sans-serif;
        font-weight: 400;
        font-size: clamp(2rem, 6vw, 3rem);
        line-height: 1.1;
        letter-spacing: -0.005em;
        margin-bottom: 0.75rem;
    }
    .tagline {
        color: #505050;
        font-size: 0.92rem;
        line-height: 1.55;
        max-width: 32ch;
        margin: 0 auto 1.5rem;
    }
    .socials {
        display: flex;
        justify-content: center;
        gap: 1.25rem;
        font-size: 1.4rem;
    }
    .socials a {
        color: #080808;
        transition: color 0.15s;
    }
    .socials a:hover { color: #ff0000; }
    nav { display: flex; flex-direction: column; gap: 0.75rem; width: 100%; }
    .nav-header {
        font-family: 'IBM Plex Mono', 'Courier New', monospace;
        font-size: 0.7rem;
        font-weight: 400;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: #777;
        margin-top: 1rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid #ddd;
        text-align: center;
    }
    nav a, .app-summary {
        color: #080808;
        text-decoration: none;
        font-size: 1rem;
        padding: 1rem 1.1rem;
        border: 1px solid #d0d0d0;
        background: #fdfcf9;
        text-align: center;
        transition: border-color 0.15s, color 0.15s, background 0.15s;
    }
    nav a:hover {
        color: #ff0000;
        border-color: #ff0000;
        background: #ffffff;
    }
    .project-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .project-pair a { font-size: 0.85rem; padding: 0.8rem 0.5rem; overflow-wrap: anywhere; }
    .app-description { display: block; margin-top: 0.35rem; font-size: 0.75rem; color: #505050; }
    .listed-project {
        border: 1px solid #d0d0d0;
        background: #fdfcf9;
        text-align: center;
        transition: border-color 0.15s, background 0.15s;
    }
    .listed-project > a { display: block; border: 0; background: transparent; }
    .listed-project > .project-link { padding-bottom: 0.25rem; }
    .listed-project > .listing-link { padding: 0.25rem 1rem 0.85rem; font-size: 0.65rem; color: #080808; }
    .listed-project > .listing-link:hover { color: #ff0000; text-decoration: underline; }
    .listed-project:hover, .listed-project:focus-within {
        border-color: #ff0000;
        background: #ffffff;
    }
    .listed-project:hover > a, .listed-project:focus-within > a { color: #ff0000; }
    a:focus-visible {
        outline: 2px solid #ff0000;
        outline-offset: 3px;
    }
    footer {
        margin-top: 2.5rem;
        text-align: center;
        font-size: 0.78rem;
        color: #808080;
    }
    footer a { color: inherit; text-decoration: none; }
    footer a:hover { color: #ff0000; }
    nav { position: relative; }
    /* On a wide screen with a mouse the column sits on the left, so the image
       slot beside the cards has room. Phones and touch screens stay centred. */
    @media (min-width: 900px) and (hover: hover) {
        body { margin-left: clamp(2rem, 7vw, 7rem); margin-right: 0; }
    }
    .vak { display: none; }
    /* Only with a pointer that can hover. The script sizes the slot to the free
       margin and leaves it hidden when there is not enough room. */
    @media (hover: hover) {
        .vak.is-ready {
            display: block;
            position: absolute;
            left: calc(100% + 2rem);
            top: 0;
            width: 150px;
            height: 150px;
            /* White, like a card on hover; the drawings leave it showing. */
            background: #ffffff;
            border: 1px solid #080808;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
            /* --vak-y is set by the script; the slot glides between cards. */
            transform: translate(-8px, var(--vak-y, 0px));
            transition:
                opacity 150ms cubic-bezier(0.7, 0, 0.84, 0),
                transform 300ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vak.is-on {
            opacity: 1;
            transform: translate(0, var(--vak-y, 0px));
            transition-duration: 200ms, 300ms;
            transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vak.no-slide { transition: none; }
        .vak canvas {
            display: block;
            width: 100%;
            height: 100%;
            transition: opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        /* A 600 x 400 CGA animation; the script sets the crop per GIF. */
        .vak img {
            position: absolute;
            height: auto;
            transition: opacity 200ms cubic-bezier(0.16, 1, 0.3, 1);
        }
        .vak img[hidden] { display: none; }
        .vak.is-swapping canvas,
        .vak.is-swapping img { opacity: 0; transition: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; }
    }
</style>
</head>
<body>
    <header>
        <h1><?= $esc($page_title) ?></h1>
        <?php if ($page_tag): ?>
        <p class="tagline"><?= $render_label($page_tag) ?></p>
        <?php endif; ?>
        <?php if ($socials): ?>
        <div class="socials">
            <?php foreach ($socials as $social):
                if (!is_array($social)) continue;
                $label = (string) ($social['label'] ?? 'Link');
                $url   = (string) ($social['url'] ?? '#');
                $icon  = preg_replace('/[^a-z0-9-]/i', '', (string) ($social['icon'] ?? 'link-45deg'));
                $external = !empty($social['external']);
            ?>
            <a href="<?= $esc($url) ?>"<?= $external ? ' target="_blank" rel="me noopener"' : '' ?> aria-label="<?= $esc($label) ?>" title="<?= $esc($label) ?>"><i class="bi bi-<?= $esc((string) $icon) ?>" aria-hidden="true"></i></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>

    <nav aria-label="Projects">
        <?php foreach ($items as $item):
            if (!is_array($item)) continue;
            $type  = (string) ($item['type'] ?? 'link');
            $label = (string) ($item['label'] ?? '');
            if ($type === 'header'): ?>
                <h2 class="nav-header"><?= $esc($label) ?></h2>
            <?php elseif ($type === 'pair'): ?>
                <div class="project-pair">
                    <?php foreach (($item['items'] ?? []) as $pair_item):
                        if (!is_array($pair_item)) continue; ?>
                    <a href="<?= $esc($item_url($pair_item)) ?>"<?= !empty($pair_item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $vak_attr($pair_item) ?>><?= $render_label((string) ($pair_item['label'] ?? '')) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($type === 'description'): ?>
                <div class="app-summary"><?= $render_label($label) ?><span class="app-description"><?= $esc((string) ($item['description'] ?? '')) ?></span></div>
            <?php else:
                $url = $item_url($item);
                $external = !empty($item['external']);
                $description = (string) ($item['description'] ?? '');
                $listing = $library_listings[(string) ($item['key'] ?? '')] ?? null;
            ?>
                <?php if ($listing): ?><div class="listed-project"><?php endif; ?>
                <a class="project-link" href="<?= $esc($url) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?><?= $vak_attr($item) ?>><?= $render_label($label) ?><?php if ($description !== ''): ?><span class="app-description"><?= $esc($description) ?></span><?php endif; ?></a>
                <?php if ($listing): ?>
                    <a class="listing-link" href="<?= $esc($listing['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $esc($label . ': ' . $listing['label']) ?>"><?= $esc($listing['label']) ?> <span aria-hidden="true">↗</span></a>
                </div><?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <div class="vak" aria-hidden="true"><canvas></canvas></div>
    </nav>

    <?php if ($email): ?>
    <footer><a href="mailto:<?= $esc($email) ?>"><?= $esc($email) ?></a></footer>
    <?php endif; ?>
    <script>
    // Image slot beside the hovered card. Cards without an image yet show an empty frame.
    (function () {
        var nav = document.querySelector('nav');
        var vak = nav && nav.querySelector('.vak');
        if (!vak) return;
        var canvas = vak.querySelector('canvas');
        var ctx = canvas.getContext('2d');
        var still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var size = 0;
        var active = null;
        var raf = 0;

        // p5.waves needs no p5: its sampler is plain maths. Loaded on the first hover only.
        var WAVES_SRC = 'https://cdn.jsdelivr.net/gh/seb-prjcts-be/p5.waves@v3.6.0/p5.waves.min.js';
        var wavesRequested = false;
        function loadWaves() {
            if (wavesRequested || window.Waves) return;
            wavesRequested = true;
            var script = document.createElement('script');
            script.src = WAVES_SRC;
            script.async = true;
            // The animation loop picks the library up by itself; a still frame needs a redraw.
            script.onload = function () { if (active && still) draw(performance.now()); };
            document.head.appendChild(script);
        }

        // The hero of p5.waves and processing.waves: grey silhouettes under five
        // coloured ribbons. Same seeds and settings as those pages, scaled to the slot.
        var HERO_BG = [
            { yPct: 0.60, amp: 0.10,  freq: 0.006, phase: 0,   seed: 10, gray: 225, alpha: 18 },
            { yPct: 0.70, amp: 0.09,  freq: 0.009, phase: 1.4, seed: 11, gray: 205, alpha: 22 },
            { yPct: 0.80, amp: 0.08,  freq: 0.012, phase: 2.8, seed: 12, gray: 180, alpha: 28 },
            { yPct: 0.90, amp: 0.055, freq: 0.016, phase: 0.6, seed: 13, gray: 150, alpha: 40 }
        ];
        var HERO_FG = [
            { yPct: 0.55, amp: 0.20,  freq: 0.007, phase: 0.3, seed: 20, alpha: 140 },
            { yPct: 0.63, amp: 0.18,  freq: 0.010, phase: 1.8, seed: 21, alpha: 150 },
            { yPct: 0.72, amp: 0.155, freq: 0.013, phase: 3.2, seed: 22, alpha: 160 },
            { yPct: 0.80, amp: 0.145, freq: 0.015, phase: 0.9, seed: 23, alpha: 170 },
            { yPct: 0.88, amp: 0.12,  freq: 0.018, phase: 2.1, seed: 24, alpha: 180 }
        ];
        var HERO_THICK = 0.17;
        var HERO_WIDTH = 1440;   // the slot shows the full width of a desktop hero
        var HERO_TOP = 0.4;      // skip the empty band where the hero has its title
        var P5_COLOURS = ['#174cff', '#ff3b2f', '#d7ff22', '#ff4fb3', '#00c7ff'];
        var LOOM_COLOURS = ['#0647ff', '#ff3d2e', '#c7ff2d', '#8e51ff', '#00bdd6'];

        function heroSampler(layer, i) {
            return Waves.createSampler({
                shift: true, shiftInterval: 3 + i, shiftDuration: 1.3,
                seed: layer.seed, range: [-1, 1], frequency: layer.freq, phase: layer.phase
            });
        }

        function ribbons(colours) {
            var back = null;
            var front = null;
            return function (t) {
                if (!back) {
                    if (!window.Waves) return;
                    back = HERO_BG.map(heroSampler);
                    front = HERO_FG.map(heroSampler);
                }
                var heroT = t * 0.54;   // the hero adds 0.018 per frame at 30 fps
                var stretch = HERO_WIDTH / size * 0.4;
                var k = size / (1 - HERO_TOP);
                function baseY(yPct) { return (yPct - HERO_TOP) * k; }
                var x;

                HERO_BG.forEach(function (b, i) {
                    ctx.fillStyle = 'rgba(' + b.gray + ',' + b.gray + ',' + b.gray + ',' + (b.alpha / 255) + ')';
                    ctx.beginPath();
                    ctx.moveTo(0, size);
                    for (x = 0; x <= size; x += 2) {
                        ctx.lineTo(x, baseY(b.yPct) + back[i].sample(x * stretch, heroT) * b.amp * k);
                    }
                    ctx.lineTo(size, size);
                    ctx.closePath();
                    ctx.fill();
                });
                HERO_FG.forEach(function (f, i) {
                    var bottom = baseY(f.yPct) + HERO_THICK * k;
                    ctx.globalAlpha = f.alpha / 255;
                    ctx.fillStyle = colours[i];
                    ctx.beginPath();
                    for (x = 0; x <= size; x += 2) {
                        ctx.lineTo(x, baseY(f.yPct) + front[i].sample(x * stretch, heroT) * f.amp * k);
                    }
                    ctx.lineTo(size, bottom);
                    ctx.lineTo(0, bottom);
                    ctx.closePath();
                    ctx.fill();
                });
                ctx.globalAlpha = 1;
            };
        }

        // The hero of vanilla.waves: stacked wave lines in the coffeehouse tones,
        // espresso through teal to sand.
        var COFFEE = ['#453020', '#6a5138', '#8a6f4a', '#b39c7d', '#72bab8', '#a9cfc8', '#dbcdad'];
        function coffee() {
            var sampler = null;
            return function (t) {
                if (!sampler) {
                    if (!window.Waves) return;
                    sampler = Waves.createSampler({
                        seed: 13, amplitude: 1, shift: true, shiftInterval: 4, shiftDuration: 2
                    });
                }
                var rows = 9;
                var steps = 60;
                ctx.lineWidth = 1.3;
                for (var r = 0; r < rows; r++) {
                    var base = size * (0.12 + r * 0.76 / (rows - 1));
                    ctx.strokeStyle = COFFEE[Math.round(r / (rows - 1) * (COFFEE.length - 1))];
                    ctx.beginPath();
                    for (var i = 0; i <= steps; i++) {
                        var v = Math.max(-1.2, Math.min(1.2, sampler.sample((i / steps) * 46 + r * 7, t + r * 0.35)));
                        var y = base + v * size * 0.06;
                        if (i === 0) ctx.moveTo(0, y); else ctx.lineTo((i / steps) * size, y);
                    }
                    ctx.stroke();
                }
            };
        }

        // The Binary Field scene of p5.waves_lab, with the lab's default settings:
        // one sampler per axis, a cell is on when row + col rises above the threshold.
        function binaryField() {
            var rowS = null;
            var colS = null;
            var CELLS = 30;
            return function (t) {
                if (!rowS) {
                    if (!window.Waves) return;
                    rowS = Waves.createSampler({
                        seed: 13, amplitude: 1, group: 'gentle', wave: 1,
                        shift: true, shiftInterval: 3, shiftDuration: 1.2
                    });
                    colS = Waves.createSampler({
                        seed: 13 + 137, amplitude: 1, group: 'gentle', wave: 14,
                        shift: true, shiftInterval: 3, shiftDuration: 1.2
                    });
                }
                // Draw in device pixels, so neighbouring cells meet without seams.
                var cell = canvas.width / CELLS;
                var offset = t * 0.4;
                ctx.save();
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.fillStyle = '#000';
                for (var r = 0; r < CELLS; r++) {
                    var rv = rowS.sample(r * 5 + offset, t);
                    var y0 = Math.round(r * cell);
                    var y1 = Math.round((r + 1) * cell);
                    for (var c = 0; c < CELLS; c++) {
                        if (rv + colS.sample(c * 2.5 - offset, t) <= 0) continue;
                        var x0 = Math.round(c * cell);
                        ctx.fillRect(x0, y0, Math.round((c + 1) * cell) - x0, y1 - y0);
                    }
                }
                ctx.restore();
            };
        }

        // p5.waves_snippets: the "Live Connector" specimen. Three nodes are wired to a
        // moving node B. Calm, the edges ripple with a gentle wave; the faster B moves,
        // the more they mix towards a harsh wave, and a reckless dart tears them (peach).
        // Rules and numbers from the specimen in the snippets script.js, scaled to the slot.
        var CONNECTOR_NODES = [[0.12, 0.2], [0.09, 0.5], [0.13, 0.82]];
        function connector() {
            var calm = null;
            var rush = null;
            var mix = 0;
            var snapT = -10;
            var last = null;
            function lerp(a, b, u) { return a + (b - a) * u; }
            return function (t) {
                if (!calm) {
                    if (!window.Waves) return;
                    calm = Waves.createSampler({ shift: true, group: 'gentle', range: [-1, 1] });
                    rush = Waves.createSampler({ shift: true, group: 'harsh', range: [-1, 1] });
                }
                var w = size, h = size;
                var k = size / 300;   // the specimen's card is about twice the slot

                // Autopilot pointer: cruises, then darts. Its speed is the tension.
                var px = w * (0.62 + 0.26 * Math.cos(t * 0.7 + Math.sin(t * 0.37) * 2.2));
                var py = h * (0.45 + 0.3 * Math.sin(t * 1.15 + Math.cos(t * 0.5)));
                var speed = last ? Math.hypot(px - last[0], py - last[1]) : 0;
                last = [px, py];
                mix += (Math.min(1, speed / (14 * k)) - mix) * 0.07;
                var rejected = t - snapT < 1.2;
                if (mix > 0.85 && !rejected) { snapT = t; mix = 0.4; }

                var AMP = h * 0.11;
                function wobble(u, phase) {
                    return lerp(calm.sample(u * 9 + t * 3 + phase, t), rush.sample(u * 9 + t * 3 + phase, t), mix) * AMP;
                }
                function edge(ax, ay, bx, by, phase) {
                    var len = Math.hypot(bx - ax, by - ay) || 1;
                    var nx = -(by - ay) / len, ny = (bx - ax) / len;
                    function strand(u0, u1, pinEnd) {
                        ctx.beginPath();
                        for (var i = 0; i <= 36; i++) {
                            var u = lerp(u0, u1, i / 36);
                            var env = pinEnd ? Math.sin((u - u0) / (u1 - u0) * Math.PI / 2) : Math.sin(u * Math.PI);
                            var off = wobble(u, phase) * env;
                            if (i === 0) ctx.moveTo(lerp(ax, bx, u) + nx * off, lerp(ay, by, u) + ny * off);
                            else ctx.lineTo(lerp(ax, bx, u) + nx * off, lerp(ay, by, u) + ny * off);
                        }
                        ctx.stroke();
                    }
                    if (rejected) { strand(0, 0.42, true); strand(1, 0.58, true); }   // torn in two
                    else strand(0, 1, false);
                }

                ctx.strokeStyle = rejected || mix > 0.5 ? '#f6a796' : '#111213';
                ctx.lineWidth = Math.max(1, 1.8 * k);
                CONNECTOR_NODES.forEach(function (n, i) { edge(w * n[0], h * n[1], px, py, i * 7); });

                // The node cards, without their numbers: too small to read here.
                ctx.lineWidth = Math.max(1, 1.5 * k);
                ctx.strokeStyle = '#111213';
                ctx.fillStyle = '#ffffff';
                function node(x, y) {
                    var nw = 28 * k, nh = 18 * k;
                    ctx.fillRect(x - nw / 2, y - nh / 2, nw, nh);
                    ctx.strokeRect(x - nw / 2, y - nh / 2, nw, nh);
                }
                CONNECTOR_NODES.forEach(function (n) { node(w * n[0], h * n[1]); });
                node(px, py);
            };
        }

        // A still photo, cropped to a square: x, y and side in the photo's own pixels.
        function picture(src, x, y, side) {
            var img = null;
            return function () {
                if (!img) {
                    img = new Image();
                    img.src = src;
                }
                if (img.complete && img.naturalWidth) ctx.drawImage(img, x, y, side, side, 0, 0, size, size);
            };
        }

        // blocks.system: windows with a black bar that snap to a 3 x 3 grid and move on
        // every two seconds: add, span, place. Colours from blocks.system.css.
        var BLOCK_LAYOUTS = [
            [[0, 0, 2, 1], [2, 0, 1, 1], [1, 2, 2, 1]],
            [[0, 1, 2, 1], [2, 0, 1, 2], [0, 2, 1, 1]],
            [[1, 0, 2, 1], [0, 0, 1, 2], [1, 2, 2, 1]],
            [[0, 2, 3, 1], [1, 0, 1, 1], [2, 0, 1, 2]]
        ];
        var BLOCK_FILLS = ['#efeee8', '#bdbcb6', '#efeee8'];
        function blocks() {
            return function (t) {
                var cell = size / 3;
                var hold = 2;
                var step = Math.floor(t / hold);
                var from = BLOCK_LAYOUTS[step % BLOCK_LAYOUTS.length];
                var to = BLOCK_LAYOUTS[(step + 1) % BLOCK_LAYOUTS.length];
                // Rest for most of the step, then move in the last 0.6 s, easing out.
                var m = Math.max(0, Math.min(1, (t - step * hold - (hold - 0.6)) / 0.6));
                m = 1 - Math.pow(1 - m, 3);

                ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                for (var i = 1; i < 3; i++) {
                    ctx.moveTo(i * cell, 0);
                    ctx.lineTo(i * cell, size);
                    ctx.moveTo(0, i * cell);
                    ctx.lineTo(size, i * cell);
                }
                ctx.stroke();

                var bar = Math.max(3, Math.round(cell * 0.12));
                from.forEach(function (a, w) {
                    var b = to[w];
                    var x = (a[0] + (b[0] - a[0]) * m) * cell + 2;
                    var y = (a[1] + (b[1] - a[1]) * m) * cell + 2;
                    var wd = (a[2] + (b[2] - a[2]) * m) * cell - 4;
                    var ht = (a[3] + (b[3] - a[3]) * m) * cell - 4;
                    ctx.fillStyle = BLOCK_FILLS[w];
                    ctx.fillRect(x, y, wd, ht);
                    ctx.fillStyle = '#111';
                    ctx.fillRect(x, y, wd, bar);
                    ctx.strokeStyle = '#111';
                    ctx.strokeRect(x + 0.5, y + 0.5, wd - 1, ht - 1);
                });
            };
        }

        // Checkboxdance: a grid of checkboxes; the ticked ones dance, joined by one closed
        // curve with red points. The motion follows Node.update() in its sketch.js.
        var DANCERS = [3, 17, 30, 9, 41];
        function checkboxdance() {
            var N = 7;
            return function (t) {
                var cell = size / N;
                var box = Math.max(4, Math.round(cell * 0.38));
                var i;

                ctx.lineWidth = 1;
                ctx.strokeStyle = '#8a8a8a';
                ctx.fillStyle = '#111';
                for (i = 0; i < N * N; i++) {
                    var gx = Math.round((i % N + 0.5) * cell - box / 2);
                    var gy = Math.round((Math.floor(i / N) + 0.5) * cell - box / 2);
                    ctx.strokeRect(gx + 0.5, gy + 0.5, box, box);
                    if (DANCERS.indexOf(i) >= 0) ctx.fillRect(gx, gy, box + 1, box + 1);
                }

                // Same sines as the sketch, scaled from its canvas to the slot.
                var s = size / 600;
                var pts = DANCERS.map(function (n) {
                    var off = (n * 2.399) % (Math.PI * 2);
                    var tt = t * 0.6 + off;
                    return [
                        size * (0.5 + 0.28 * Math.sin(tt)) + (Math.sin(tt + off) * 50 + Math.sin(tt * 0.5 + off) * 30) * s,
                        (Math.floor(n / N) + 0.5) * cell + (Math.cos(tt * 0.7 + off) * 20 + Math.cos(tt * 0.3 + off) * 15) * s
                    ];
                });

                // A closed Catmull-Rom curve, like curveVertex(), written as Bézier segments.
                var n = pts.length;
                ctx.beginPath();
                ctx.moveTo(pts[0][0], pts[0][1]);
                for (i = 0; i < n; i++) {
                    var p0 = pts[(i - 1 + n) % n], p1 = pts[i], p2 = pts[(i + 1) % n], p3 = pts[(i + 2) % n];
                    ctx.bezierCurveTo(
                        p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6,
                        p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6,
                        p2[0], p2[1]
                    );
                }
                ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
                ctx.fill();
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 0.75;
                ctx.stroke();

                ctx.fillStyle = 'rgb(255, 70, 70)';
                pts.forEach(function (p) {
                    ctx.beginPath();
                    ctx.arc(p[0], p[1], 1.5, 0, Math.PI * 2);
                    ctx.fill();
                });
            };
        }

        // prjcts.be and kunstmijnoren.be: their own work, one square thumbnail per
        // project, each fading into the next. The thumbnails come from the site's
        // own thumb.php, so nothing has to be uploaded here.
        function work(base, files) {
            var imgs = [];
            var HOLD = 2.4;
            var FADE = 0.6;
            function get(i) {
                if (!imgs[i]) {
                    imgs[i] = new Image();
                    imgs[i].src = base + files[i];
                }
                return imgs[i];
            }
            function ready(img) { return img.complete && img.naturalWidth; }
            return function (t) {
                var k = Math.floor(t / HOLD);
                var now = get(k % files.length);
                var next = get((k + 1) % files.length);   // loads while the current one shows
                var m = still ? 0 : Math.max(0, (t - k * HOLD - (HOLD - FADE)) / FADE);
                if (ready(now)) ctx.drawImage(now, 0, 0, size, size);
                if (m > 0 && ready(next)) {
                    ctx.globalAlpha = m;
                    ctx.drawImage(next, 0, 0, size, size);
                    ctx.globalAlpha = 1;
                }
            };
        }

        var PRJCTS_THUMBS = 'https://prjcts.be/_shared/thumb.php?w=300&h=300&p=content%2Fprojects%2F';
        var KMO_THUMBS = 'https://kunstmijnoren.be/_shared/thumb.php?w=300&h=300&p=content%2Fprojects%2F01-foliotheek%2F';

        // The penplotters have a CGA animation as their hero. A GIF cannot play on the
        // canvas, so it gets its own image, loaded from the library site on first hover.
        // x, y and side give the square crop in the 600 x 400 GIF.
        var GIFS = {
            // The plotter on the right, with the pen crossing the sheet.
            p5penplotter: {
                src: 'https://seb-prjcts-be.github.io/p5.penplotter/docs/images/animations/hero.gif',
                x: 300, y: 50, side: 300
            }
        };

        // vanilla.penplotter: the plotting part of its CGA hero, drawn here. In the GIF the
        // sheet stays empty for the first ten seconds, and the wider frame is dark espresso,
        // so the slot draws only the sheet: the teal bar and ochre pen plot a spiral, then
        // start over. Four colours from overview.gif, drawn on a 60 x 60 pixel grid.
        var VANILLA_CGA = { ink: '#2b1f1a', sheet: '#ecddb8', bar: '#8fa595', pen: '#c4874b' };
        function vanillaPlot() {
            var GRID = 60;
            var DRAW = 6;
            var HOLD = 1.5;
            var pixels = document.createElement('canvas');
            pixels.width = GRID;
            pixels.height = GRID;
            var px = pixels.getContext('2d');

            // A wobbly spiral, out from the centre, like the one in the GIF.
            var path = [];
            var TURNS = 7, STEPS = 1100;
            for (var i = 0; i <= STEPS; i++) {
                var a = i / STEPS * TURNS * Math.PI * 2;
                var r = 1.5 + a * (20 / (TURNS * Math.PI * 2)) * (1 + 0.1 * Math.sin(6 * a));
                path.push([Math.round(GRID / 2 + Math.cos(a) * r), Math.round(GRID / 2 + Math.sin(a) * r)]);
            }

            return function (t) {
                var p = still ? 1 : Math.min(1, (t % (DRAW + HOLD)) / DRAW);
                var n = Math.floor(p * STEPS);
                px.fillStyle = VANILLA_CGA.sheet;
                px.fillRect(0, 0, GRID, GRID);
                px.fillStyle = VANILLA_CGA.ink;
                for (var k = 0; k <= n; k++) px.fillRect(path[k][0], path[k][1], 1, 1);
                if (p < 1) {
                    var pen = path[n];
                    px.fillStyle = VANILLA_CGA.bar;
                    px.fillRect(0, pen[1] - 1, GRID, 2);
                    px.fillStyle = VANILLA_CGA.pen;
                    px.fillRect(pen[0] - 2, pen[1] - 2, 5, 5);
                    px.fillStyle = VANILLA_CGA.ink;
                    px.fillRect(pen[0] - 1, pen[1] - 1, 3, 3);
                }
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(pixels, 0, 0, size, size);
                ctx.imageSmoothingEnabled = true;
            };
        }
        var gif = document.createElement('img');
        gif.alt = '';
        gif.hidden = true;
        vak.appendChild(gif);

        var beelden = {
            p5waves: ribbons(P5_COLOURS),
            processingwaves: ribbons(LOOM_COLOURS),
            vanillawaves: coffee(),
            waves_lab: binaryField(),
            waves_snippets: connector(),
            // A real plotted sheet, "I LOVE YOU" worn and permuted, from the p5.gysin site.
            gysin: picture('https://seb-prjcts-be.github.io/p5.gysin/docs/assets/plotted-sheet-love-you.jpg', 0, 240, 720),
            'blocks.system': blocks(),
            vanillapenplotter: vanillaPlot(),
            checkboxdance: checkboxdance(),
            // One image per project, works first, then the archive.
            prjcts: work(PRJCTS_THUMBS, [
                '01-projects%2F02-form-repetition%2F20191003_graphic02.jpg',
                '01-projects%2F03-entrench-enshrine%2Fa7_03813.jpg',
                '01-projects%2F06-das-dunkelheit-prinzip%2F20211211_230936-01.jpeg',
                '02-archief%2F01-membering-inneren%2F20250329_114925.jpg',
                '02-archief%2F02-something-from-nothing%2F20180331_swoosh1.jpg',
                '02-archief%2F03-experiments%2F3a26198_mirror_optimized-1.jpg',
                '02-archief%2F05-tree%2Fsq1.jpg',
                '02-archief%2F06-walks%2Fwalks01.jpg'
            ]),
            // Zinneroersels and Folio an sich, taking turns.
            kunstmijnoren: work(KMO_THUMBS, [
                '01-inner-tremors%2F20250617_170915.jpg',
                '02-beautyfolio%2F20250620_123337.jpg',
                '01-inner-tremors%2F20250621_085649.jpg',
                '02-beautyfolio%2Faltaar.jpg',
                '01-inner-tremors%2F20250629_180419.jpg',
                '02-beautyfolio%2Ffam01.jpg',
                '01-inner-tremors%2F20251012_172006.jpg',
                '02-beautyfolio%2F20250711_124630-0.jpg'
            ])
        };

        function fit() {
            var ratio = window.devicePixelRatio || 1;
            size = canvas.clientWidth;
            canvas.width = size * ratio;
            canvas.height = size * ratio;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        }

        function draw(now) {
            ctx.clearRect(0, 0, size, size);
            if (!active) return;
            beelden[active](now / 1000);
            if (!still) raf = requestAnimationFrame(draw);
        }

        var SIDE_MAX = 150;
        var SIDE_MIN = 72;
        var GAP = 32 + 16;   // the 2rem gap beside the list, plus a little air at the window edge

        // How large the slot can be in the margin right of the list; 0 if it does not fit.
        function room() {
            var side = Math.min(SIDE_MAX, Math.floor(window.innerWidth - nav.getBoundingClientRect().right - GAP));
            return side >= SIDE_MIN ? side : 0;
        }

        function show(card, link) {
            var side = room();
            if (!side) {
                vak.classList.remove('is-ready');
                return;
            }
            vak.classList.add('is-ready');
            if (vak.offsetWidth !== side) {
                vak.style.width = side + 'px';
                vak.style.height = side + 'px';
                size = 0;
            }
            var top = card.getBoundingClientRect().top - nav.getBoundingClientRect().top;
            var y = Math.max(0, top + card.offsetHeight / 2 - side / 2) + 'px';
            var visible = vak.classList.contains('is-on');
            clearTimeout(hideTimer);

            if (visible) {
                // Already open: glide to the new card, and fade the new image in.
                vak.style.setProperty('--vak-y', y);
                if (vak.dataset.vak !== link.dataset.vak) {
                    vak.classList.add('is-swapping');
                    void vak.offsetWidth;
                    vak.classList.remove('is-swapping');
                }
            } else {
                // Coming from hidden: appear beside the card, without sliding in from elsewhere.
                vak.classList.add('no-slide');
                vak.style.setProperty('--vak-y', y);
                void vak.offsetWidth;
                vak.classList.remove('no-slide');
                vak.classList.add('is-on');
            }
            vak.dataset.vak = link.dataset.vak;

            cancelAnimationFrame(raf);
            active = beelden[link.dataset.vak] ? link.dataset.vak : null;
            // A GIF cannot be paused, so with reduced motion the slot stays empty.
            var crop = still ? null : GIFS[link.dataset.vak];
            gif.hidden = !crop;
            if (crop && gif.getAttribute('src') !== crop.src) {
                gif.src = crop.src;
                gif.style.width = (600 / crop.side * 100) + '%';
                gif.style.left = (-crop.x / crop.side * 100) + '%';
                gif.style.top = (-crop.y / crop.side * 100) + '%';
                gif.style.imageRendering = crop.pixelated ? 'pixelated' : '';
            }
            if (!size) fit();
            draw(performance.now());
        }
        // A short pause, so moving across the gap between two cards keeps the slot open.
        var hideTimer = 0;
        function hide() {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                vak.classList.remove('is-on');
                cancelAnimationFrame(raf);
            }, 150);
        }
        // A narrower window must never get a sideways scroll from the hidden slot.
        window.addEventListener('resize', function () {
            if (vak.offsetWidth > room()) vak.classList.remove('is-ready');
        });
        nav.addEventListener('mouseenter', loadWaves);
        nav.addEventListener('focusin', loadWaves);
        nav.querySelectorAll('a[data-vak]').forEach(function (link) {
            var card = link.closest('.listed-project') || link;
            card.addEventListener('mouseenter', function () { show(card, link); });
            card.addEventListener('mouseleave', hide);
            link.addEventListener('focus', function () { show(card, link); });
            link.addEventListener('blur', hide);
        });
    })();
    </script>
</body>
</html>
