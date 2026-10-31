<?php
/**
 * sebastienvanblaere.be : landing / linktree.
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
    // The three.js course is no longer listed (Seb, 2026-10-30); it stays online at its own address.
    if (str_contains((string) ($item['url'] ?? ''), '/services/creative_coding_three.js')) continue;
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

// One line under each card: what you find there. Keyed by key, or by url for cards without one.
$descriptions = [
    'prjcts'            => 'A collection of seemingly useless fragments of imagery and small procedural animations. Gentle on the brain and with love for code.',
    'kunstmijnoren'     => 'A folio collection of scratches, drawings, photos, scans and halftones, with the philosophy to go with it.',
    'p5waves'           => '35 wave shapes for p5.js. Pass a number in, get a number back. Motion, patterns, morphing.',
    'waves_lab'         => 'Turn the knobs, watch the wave. The fastest way to see what the library does.',
    'waves_snippets'    => 'Small copy-paste sketches, one wave call each. Open any of them in the p5 editor.',
    'processingwaves'   => 'The same 35 waves for Processing 4. One call, one float.',
    'vanillawaves'      => 'The same 35 waves for plain HTML: they drive a bar\'s height, a colour or a position.',
    'gysin'             => 'Worn words, permuted language and plotter-ready SVG and HPGL, after Brion Gysin.',
    'vanillapenplotter' => 'Paths in millimetres, plotted straight from the browser. No SVG, no Inkscape.',
    'p5penplotter'      => 'Write plot.line() instead of line() and the same line goes to the pen. Straight from the sketch, no SVG, no Inkscape.',
    'blocks.system'     => 'A small browser core that gives every part of a page its own address. No dependencies.',
    'export'            => 'Lossless capture of a p5 sketch, one call. From the editor, a zip or a folder.',
    'mdblocker'         => 'A Chrome extension that hides the Markdown files your coding tools leave in Drive. Docs again looks like Docs.',
    'checkboxdance'     => 'A grid of checkboxes and one curve that dances through the ones you tick.',
    'p5'                => 'The p5.js course I teach at KHSL, in Dutch, with a live editor on every page. Take what you can use.',
    'https://prjcts.be/services/svg'                          => 'A black-and-white image in, a halftone, sawtooth or isotope SVG out, sized for a 0.4 mm pen.',
    'https://prjcts.be/services/p5.blocks'                    => 'Visual programming for p5.js: blocks in, running sketch out. A concept, not a product.',
    'https://sebastienvanblaere.be/services/audiovisueel'     => 'Notes and research on photography, film, image and sound. A notebook, in Dutch.',
    'https://www.youtube.com/playlist?list=PLbJ6hUkwmWttjtUlWWRQKycCGI6iFxFP0' => 'A playlist of the things I keep coming back to.',
    'https://sebastienvanblaere.be/services/cv'               => 'Who I am on paper.',
];
$describe = static function (array $item) use ($descriptions): array {
    if (!empty($item['description'])) return $item;
    $key = (string) ($item['key'] ?? '');
    $url = rtrim((string) ($item['url'] ?? ''), '/');
    $text = $descriptions[$key] ?? $descriptions[$url] ?? '';
    if ($text !== '') $item['description'] = $text;
    return $item;
};
foreach ($items as $i => $item) {
    if (!is_array($item)) continue;
    if (($item['type'] ?? '') === 'pair') {
        $items[$i]['items'] = array_map($describe, $item['items'] ?? []);
    } else {
        $items[$i] = $describe($item);
    }
}

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

// Projects with their own visual language open an image drawer above the card on hover.
// Education, apps, the CV and most services keep the plain hover. See docs/hover-handtekeningen.md.
$vak_keys = [
    'prjcts', 'kunstmijnoren',
    'p5waves', 'waves_lab', 'waves_snippets', 'processingwaves', 'vanillawaves',
    'gysin', 'vanillapenplotter', 'p5penplotter', 'blocks.system',
    'mdblocker',
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
    if (!empty($entry['description'])) {
        $thing['description'] = (string) $entry['description'];
    }
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
            'name'            => $page_title . ': Art, Libraries, Services & Education',
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
    /* .is-playing: the card stays selected while its drawer shows the animation. */
    nav a:hover, nav a.is-playing {
        color: #ff0000;
        border-color: #ff0000;
        background: #ffffff;
    }
    .project-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; align-items: start; }
    /* Each half card has a column of its own, so a drawer can open above one half
       while the other half stays where it is. */
    .pair-cell { display: flex; flex-direction: column; gap: 0.75rem; min-width: 0; }
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
    .listed-project:hover, .listed-project:focus-within, .listed-project.is-playing {
        border-color: #ff0000;
        background: #ffffff;
    }
    .listed-project:hover > a, .listed-project:focus-within > a, .listed-project.is-playing > a { color: #ff0000; }
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
    .vak, .vak-drawer { display: none; }
    /* Only with a pointer that can hover: a drawer above the hovered card slides open,
       as wide as the card, 3:2. Slower than the rest (700 ms), on purpose: the card
       makes way for the image. */
    @media (hover: hover) {
        .vak-drawer {
            display: grid;
            grid-template-rows: 0fr;
            /* Closed, it must not add a gap of its own between the cards. */
            margin-bottom: -0.75rem;
            transition: grid-template-rows 700ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .vak-drawer.is-open { grid-template-rows: 1fr; }
        .vak-drawer-inner {
            display: flex;
            justify-content: center;
            min-height: 0;
            overflow: hidden;
        }
        .vak-drawer .vak,
        .vak-ghost {
            display: block;
            position: relative;
            flex: none;
            margin: 0 0 0.75rem;
        }
        .vak-drawer .vak {
            /* White, like a card on hover; the drawings leave it showing. */
            background: #ffffff;
            border: 1px solid #080808;
            overflow: hidden;
        }
        .vak canvas {
            display: block;
            width: 100%;
            height: 100%;
        }
        /* A penplotter's whole 600 x 400 CGA animation, filling the 3:2 slot. */
        .vak img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
        }
        .vak img[hidden] { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; }
    }
</style>
<?php if (!empty($config['teller'])): ?>
<script data-goatcounter="https://<?= $esc((string) $config['teller']) ?>.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
<?php endif; ?>
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
                    <div class="pair-cell"><a href="<?= $esc($item_url($pair_item)) ?>"<?= !empty($pair_item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $vak_attr($pair_item) ?>><?= $render_label((string) ($pair_item['label'] ?? '')) ?><?php if (!empty($pair_item['description'])): ?><span class="app-description"><?= $esc((string) $pair_item['description']) ?></span><?php endif; ?></a></div>
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
        // The drawing area: square beside a half card, 3:2 in a full card's drawer.
        // size is its width, high its height.
        var size = 0;
        var high = 0;
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

        // The hero of p5.waves: grey silhouettes under five
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
                var k = high / (1 - HERO_TOP);
                function baseY(yPct) { return (yPct - HERO_TOP) * k; }
                var x;

                HERO_BG.forEach(function (b, i) {
                    ctx.fillStyle = 'rgba(' + b.gray + ',' + b.gray + ',' + b.gray + ',' + (b.alpha / 255) + ')';
                    ctx.beginPath();
                    ctx.moveTo(0, high);
                    for (x = 0; x <= size; x += 2) {
                        ctx.lineTo(x, baseY(b.yPct) + back[i].sample(x * stretch, heroT) * b.amp * k);
                    }
                    ctx.lineTo(size, high);
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

        // processing.waves: the "Not So Random Walker" from the p5.waves site. Fifteen walkers
        // in the p5.waves colours, steered by a cold and a warm pair of samplers, leave block
        // trails on near-black. Drawn on a coarse 48 x 32 grid and enlarged without smoothing,
        // so it sits with the CGA animations. Speeds and fades converted from the demo
        // (30 fps, 8 px cells) to this grid at 60 fps.
        var WALK_COLOURS = [[23, 76, 255], [255, 59, 47], [215, 255, 34], [255, 79, 179], [0, 199, 255]];
        var WALK_COLD = [true, false, false, false, true];
        function cgaWalker() {
            var COLS = 48, ROWS = 32, PER_COLOUR = 3;
            var grid = document.createElement('canvas');
            grid.width = COLS;
            grid.height = ROWS;
            var g = grid.getContext('2d');
            var coldX = null, coldY, warmX, warmY;
            var wx = [], wy = [];
            var tt = 0;

            function step() {
                g.fillStyle = 'rgba(15, 15, 15, 0.02)';
                g.fillRect(0, 0, COLS, ROWS);
                tt += 0.0125;
                for (var i = 0; i < WALK_COLOURS.length * PER_COLOUR; i++) {
                    var ci = Math.floor(i / PER_COLOUR), vi = i % PER_COLOUR;
                    var phase = ci * 6.7 + vi * 1.3;
                    var vx = (WALK_COLD[ci] ? coldX : warmX).sample(tt * 1.8 + phase, tt);
                    var vy = (WALK_COLD[ci] ? coldY : warmY).sample(tt * 2.1 + phase * 1.3, tt);
                    var px = wx[i], py = wy[i];
                    wx[i] = (wx[i] + vx / 20 + COLS) % COLS;
                    wy[i] = (wy[i] + vy / 20 + ROWS) % ROWS;
                    if (Math.abs(wx[i] - px) > COLS / 2 || Math.abs(wy[i] - py) > ROWS / 2) continue;
                    var brush = 1 + Math.round((Math.abs(vx) + Math.abs(vy)) * 0.25);
                    var c = WALK_COLOURS[ci];
                    g.fillStyle = 'rgba(' + c[0] + ',' + c[1] + ',' + c[2] + ',0.82)';
                    g.fillRect(Math.floor(wx[i]), Math.floor(wy[i]), brush, brush);
                }
            }

            return function () {
                if (!coldX) {
                    if (!window.Waves) return;
                    function sampler(freq, seed) {
                        return Waves.createSampler({
                            shift: true, shiftInterval: 7, shiftDuration: 1.5, group: 'gentle',
                            amplitude: 5, frequency: freq, seed: seed
                        });
                    }
                    coldX = sampler(0.4, 0);
                    coldY = sampler(0.3, 77);
                    warmX = sampler(0.4, 311);
                    warmY = sampler(0.3, 488);
                    g.fillStyle = '#0f0f0f';
                    g.fillRect(0, 0, COLS, ROWS);
                    for (var i = 0; i < WALK_COLOURS.length * PER_COLOUR; i++) {
                        var a = Math.PI * 2 * Math.floor(i / PER_COLOUR) / WALK_COLOURS.length + (i % PER_COLOUR - 1) * 0.14;
                        wx[i] = COLS / 2 + Math.cos(a) * 6;
                        wy[i] = ROWS / 2 + Math.sin(a) * 6;
                    }
                    // Pre-run ten seconds, so the trails are already there when the drawer opens.
                    for (var f = 0; f < 600; f++) step();
                }
                if (!still) step();
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(grid, 0, 0, size, high);
                ctx.imageSmoothingEnabled = true;
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
                    var base = high * (0.12 + r * 0.76 / (rows - 1));
                    ctx.strokeStyle = COFFEE[Math.round(r / (rows - 1) * (COFFEE.length - 1))];
                    ctx.beginPath();
                    for (var i = 0; i <= steps; i++) {
                        var v = Math.max(-1.2, Math.min(1.2, sampler.sample((i / steps) * 46 + r * 7, t + r * 0.35)));
                        var y = base + v * high * 0.06;
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
                // 30 square cells across, as many rows as the height holds.
                var cell = canvas.width / CELLS;
                var rows = Math.round(canvas.height / cell);
                var offset = t * 0.4;
                ctx.save();
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.fillStyle = '#000';
                for (var r = 0; r < rows; r++) {
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
                var w = size, h = high;
                var k = size / 450;   // the specimen's card is about twice as wide as a half drawer

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

        // A still photo, cropped to the slot's shape around a point (cx, cy, as fractions
        // of the photo), as large as the photo allows.
        function picture(src, cx, cy) {
            var img = null;
            return function () {
                if (!img) {
                    img = new Image();
                    img.src = src;
                }
                if (!img.complete || !img.naturalWidth) return;
                var iw = img.naturalWidth, ih = img.naturalHeight;
                var scale = Math.min(iw / size, ih / high);
                var w = size * scale, h = high * scale;
                var x = Math.max(0, Math.min(iw - w, iw * cx - w / 2));
                var y = Math.max(0, Math.min(ih - h, ih * cy - h / 2));
                ctx.drawImage(img, x, y, w, h, 0, 0, size, high);
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
                var cw = size / 3, ch = high / 3;
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
                    ctx.moveTo(i * cw, 0);
                    ctx.lineTo(i * cw, high);
                    ctx.moveTo(0, i * ch);
                    ctx.lineTo(size, i * ch);
                }
                ctx.stroke();

                var bar = Math.max(3, Math.round(ch * 0.12));
                from.forEach(function (a, w) {
                    var b = to[w];
                    var x = (a[0] + (b[0] - a[0]) * m) * cw + 2;
                    var y = (a[1] + (b[1] - a[1]) * m) * ch + 2;
                    var wd = (a[2] + (b[2] - a[2]) * m) * cw - 4;
                    var ht = (a[3] + (b[3] - a[3]) * m) * ch - 4;
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
                // N rows of square cells; as many columns as the width holds, centred.
                var cell = high / N;
                var cols = Math.max(N, Math.floor(size / cell));
                var left = (size - cols * cell) / 2;
                var shift = Math.floor((cols - N) / 2);   // the dancers keep their place in the middle
                var box = Math.max(4, Math.round(cell * 0.38));
                var i;

                ctx.lineWidth = 1;
                ctx.strokeStyle = '#8a8a8a';
                ctx.fillStyle = '#111';
                for (i = 0; i < cols * N; i++) {
                    var c = i % cols, r = Math.floor(i / cols);
                    var gx = Math.round(left + (c + 0.5) * cell - box / 2);
                    var gy = Math.round((r + 0.5) * cell - box / 2);
                    ctx.strokeRect(gx + 0.5, gy + 0.5, box, box);
                    if (DANCERS.indexOf(r * N + c - shift) >= 0 && c >= shift && c < shift + N) {
                        ctx.fillRect(gx, gy, box + 1, box + 1);
                    }
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
                if (ready(now)) ctx.drawImage(now, 0, 0, size, high);
                if (m > 0 && ready(next)) {
                    ctx.globalAlpha = m;
                    ctx.drawImage(next, 0, 0, size, high);
                    ctx.globalAlpha = 1;
                }
            };
        }

        // thumb.php crops to the size asked: 3:2, like the drawer.
        var PRJCTS_THUMBS = 'https://prjcts.be/_shared/thumb.php?w=600&h=400&p=content%2Fprojects%2F';
        var KMO_THUMBS = 'https://kunstmijnoren.be/_shared/thumb.php?w=600&h=400&p=content%2Fprojects%2F01-foliotheek%2F';

        // Projects with a CGA animation as their first image (600 x 400) show that GIF whole
        // in the 3:2 drawer. A GIF cannot play on the canvas, so it gets its own image,
        // loaded from the project's site on first hover.
        var GIFS = {
            p5penplotter: 'https://seb-prjcts-be.github.io/p5.penplotter/docs/images/animations/hero.gif',
            vanillapenplotter: 'https://seb-prjcts-be.github.io/vanilla.penplotter/docs/images/animations/overview.gif?v=restored-20261003',
            mdblocker: 'https://seb-prjcts-be.github.io/google_docs_md_filter/docs/images/overview.gif'
        };
        var gif = document.createElement('img');
        gif.alt = '';
        gif.hidden = true;
        vak.appendChild(gif);

        var beelden = {
            p5waves: ribbons(P5_COLOURS),
            processingwaves: cgaWalker(),
            vanillawaves: coffee(),
            waves_lab: binaryField(),
            waves_snippets: connector(),
            // A real plotted sheet, "I LOVE YOU" worn and permuted, from the p5.gysin site.
            gysin: picture('https://seb-prjcts-be.github.io/p5.gysin/docs/assets/plotted-sheet-love-you.jpg', 0.5, 0.62),
            'blocks.system': blocks(),
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
            high = canvas.clientHeight;
            canvas.width = size * ratio;
            canvas.height = high * ratio;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        }

        function draw(now) {
            ctx.clearRect(0, 0, size, high);
            if (!active) return;
            beelden[active](now / 1000);
            if (!still) raf = requestAnimationFrame(draw);
        }

        // Every card with an image opens a drawer just above itself, outside its border:
        // the card slides down and the image appears over it, as wide as the card, 3:2.
        // For a half card the drawer opens in its own column; the other half stays put.
        var WIDE = 2 / 3;       // 3:2, like a CGA animation
        var OPEN_DELAY = 180;   // rest on a card this long before it opens, so passing over does not
        var openDrawer = null;
        var openTimer = 0;

        // The drawer counts as part of the card for hover.
        function drawerOf(card) {
            var drawer = card.previousElementSibling;
            if (!drawer || !drawer.classList.contains('vak-drawer')) {
                drawer = document.createElement('div');
                drawer.className = 'vak-drawer';
                drawer.setAttribute('aria-hidden', 'true');
                drawer.appendChild(document.createElement('div')).className = 'vak-drawer-inner';
                card.parentNode.insertBefore(drawer, card);
                drawer.addEventListener('mouseenter', function () {
                    clearTimeout(hideTimer);
                    clearTimeout(openTimer);
                });
                drawer.addEventListener('mouseleave', hide);
            }
            return drawer;
        }
        function closeDrawer() {
            if (!openDrawer) return;
            openDrawer.classList.remove('is-open');
            openDrawer.nextElementSibling.classList.remove('is-playing');
            openDrawer = null;
        }
        // Before the slot leaves a drawer, leave an empty stand-in of the same size behind,
        // so that drawer can still slide shut instead of snapping closed.
        function leaveDrawer() {
            var inner = vak.parentElement;
            if (!inner || !inner.classList.contains('vak-drawer-inner')) return;
            var ghost = document.createElement('span');
            ghost.className = 'vak-ghost';
            ghost.style.width = vak.offsetWidth + 'px';
            ghost.style.height = vak.offsetHeight + 'px';
            inner.appendChild(ghost);
            setTimeout(function () { ghost.remove(); }, 750);
        }
        function setSize(w, h) {
            w = Math.round(w);
            h = Math.round(h);
            if (vak.offsetWidth !== w || vak.offsetHeight !== h) {
                vak.style.width = w + 'px';
                vak.style.height = h + 'px';
                size = 0;
            }
        }

        function open(card, link) {
            var drawer = drawerOf(card);
            if (drawer !== openDrawer) {
                closeDrawer();
                if (vak.parentElement !== drawer.firstChild) leaveDrawer();
                drawer.firstChild.querySelectorAll('.vak-ghost').forEach(function (g) { g.remove(); });
                drawer.firstChild.appendChild(vak);
                drawer.classList.add('is-open');
                card.classList.add('is-playing');
                openDrawer = drawer;
            }
            setSize(card.offsetWidth, card.offsetWidth * WIDE);
            paint(link);
        }

        function show(card, link) {
            clearTimeout(hideTimer);
            clearTimeout(openTimer);
            openTimer = setTimeout(function () { open(card, link); }, OPEN_DELAY);
        }

        // Start this card's image in the slot, wherever the slot is.
        function paint(link) {
            vak.dataset.vak = link.dataset.vak;
            cancelAnimationFrame(raf);
            active = beelden[link.dataset.vak] ? link.dataset.vak : null;
            // A GIF cannot be paused, so with reduced motion the slot stays empty.
            var src = still ? '' : (GIFS[link.dataset.vak] || '');
            gif.hidden = !src;
            if (src && gif.getAttribute('src') !== src) gif.src = src;
            if (!size) fit();
            draw(performance.now());
        }
        // A short pause, so moving across the gap between two cards keeps the slot open.
        var hideTimer = 0;
        function hide() {
            clearTimeout(hideTimer);
            clearTimeout(openTimer);
            hideTimer = setTimeout(function () {
                closeDrawer();
                cancelAnimationFrame(raf);
            }, 150);
        }
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
