<?php
$_current_lang = (string) ($GLOBALS['_prjcts_lang'] ?? 'nl');
if (!in_array($_current_lang, ['nl', 'en', 'fr'], true)) {
    $_current_lang = 'nl';
}
$_is_404 = !empty($page['is_404']) || (($page['title'] ?? null) === '404');
$_page_title_part = isset($page['title']) && $page['title'] !== null && $page['title'] !== ''
    ? (string) $page['title']
    : '';
$_full_title = (string) $cfg['title'] . ($_page_title_part !== '' ? ' / ' . $_page_title_part : '');

// === SEO + social meta ===
// Pagina-overrides via $page['description'], $page['og_image'], $page['og_title'], $page['og_type'].
$_meta_desc = (string) ($page['description'] ?? ($cfg['tagline'] ?? $cfg['description'] ?? ''));
$_meta_kw   = isset($cfg['keywords']) && is_array($cfg['keywords']) ? implode(', ', $cfg['keywords']) : '';
$_meta_auth = (string) ($cfg['author'] ?? 'Sebastien Vanblaere');
$_og_title  = (string) ($page['og_title'] ?? ($_page_title_part !== '' ? $_full_title : ($cfg['og_title'] ?? $cfg['title'])));
$_og_type   = (string) ($page['og_type'] ?? 'website');
$_og_image  = (string) ($page['og_image'] ?? ($cfg['og_image'] ?? ''));

// Relatief og_image-pad → absolute URL op productie-host
if ($_og_image !== '' && !preg_match('~^https?://~i', $_og_image)) {
    $_og_image = 'https://' . $cfg['host'] . '/' . ltrim($_og_image, '/');
}

$_canonical_url = function_exists('build_canonical_url')
    ? build_canonical_url($cfg, $config, $_current_lang)
    : ('https://' . $cfg['host'] . ($_SERVER['REQUEST_URI'] ?? '/'));
$_fb_app = (string) ($cfg['fb_app_id'] ?? '');

$_locale_map = ['nl' => 'nl_BE', 'en' => 'en_GB', 'fr' => 'fr_BE'];
$_og_locale  = $_locale_map[$_current_lang] ?? 'nl_BE';

// JSON-LD voor satelliet-pagina's
$_sat_base    = 'https://' . $cfg['host'] . '/';
$_hub_base    = 'https://' . ($config['hub_root'] ?? 'sebastienvanblaere.be') . '/';
$_schema_type = (string) ($page['schema_type'] ?? 'WebPage');
$_pub_time    = isset($page['published_time']) && $page['published_time'] !== null ? (string) $page['published_time'] : '';

$_page_entity = [
    '@type'      => $_schema_type,
    '@id'        => $_canonical_url . '#webpage',
    'url'        => $_canonical_url,
    'name'       => $_full_title,
    'inLanguage' => $_current_lang,
    'isPartOf'   => ['@id' => $_sat_base . '#website'],
    'author'     => ['@id' => $_hub_base . '#person'],
    'creator'    => ['@id' => $_hub_base . '#person'],
];
if ($_meta_desc !== '') $_page_entity['description'] = $_meta_desc;
if ($_og_image !== '')  $_page_entity['image'] = $_og_image;
if ($_pub_time !== '')  $_page_entity['datePublished'] = $_pub_time;

$_breadcrumbs = [
    [
        '@type'    => 'ListItem',
        'position' => 1,
        'name'     => (string) ($config['hub']['title'] ?? 'Sebastien Vanblaere'),
        'item'     => $_hub_base,
    ],
    [
        '@type'    => 'ListItem',
        'position' => 2,
        'name'     => (string) $cfg['title'],
        'item'     => $_sat_base . ($_current_lang !== 'nl' ? '?lang=' . $_current_lang : ''),
    ],
];
if ($_page_title_part !== '' && !$_is_404) {
    $_breadcrumbs[] = [
        '@type'    => 'ListItem',
        'position' => 3,
        'name'     => $_page_title_part,
        'item'     => $_canonical_url,
    ];
}

$_sat_json_ld = [
    '@context' => 'https://schema.org',
    '@graph'   => [
        [
            '@type'       => 'WebSite',
            '@id'         => $_sat_base . '#website',
            'url'         => $_sat_base,
            'name'        => (string) $cfg['title'],
            'description' => (string) ($cfg['tagline'] ?? $cfg['description'] ?? ''),
            'inLanguage'  => ['nl', 'en', 'fr'],
            'publisher'   => ['@id' => $_hub_base . '#person'],
        ],
        [
            '@type' => 'Person',
            '@id'   => $_hub_base . '#person',
            'name'  => 'Sebastien Vanblaere',
            'url'   => $_hub_base,
        ],
        $_page_entity,
        [
            '@type'           => 'BreadcrumbList',
            '@id'             => $_canonical_url . '#breadcrumb',
            'itemListElement' => $_breadcrumbs,
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="<?= e($_current_lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#faf9f5">
<meta name="robots" content="<?= $_is_404 ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' ?>">
<title><?= e($_full_title) ?></title>

<?php if ($_meta_desc): ?>
<meta name="description" content="<?= e($_meta_desc) ?>">
<?php endif; ?>
<?php if ($_meta_kw): ?>
<meta name="keywords" content="<?= e($_meta_kw) ?>">
<?php endif; ?>
<?php if ($_meta_auth): ?>
<meta name="author" content="<?= e($_meta_auth) ?>">
<?php endif; ?>

<?php if (!$_is_404): ?>
<link rel="canonical" href="<?= e($_canonical_url) ?>">
<?php if (function_exists('build_canonical_url')): ?>
<link rel="alternate" hreflang="nl" href="<?= e(build_canonical_url($cfg, $config, 'nl')) ?>">
<link rel="alternate" hreflang="en" href="<?= e(build_canonical_url($cfg, $config, 'en')) ?>">
<link rel="alternate" hreflang="fr" href="<?= e(build_canonical_url($cfg, $config, 'fr')) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e(build_canonical_url($cfg, $config, 'nl')) ?>">
<?php endif; ?>
<?php endif; ?>

<meta property="og:site_name" content="<?= e((string) $cfg['title']) ?>">
<meta property="og:locale" content="<?= e($_og_locale) ?>">
<?php foreach ($_locale_map as $_lc_code => $_lc_val): if ($_lc_code !== $_current_lang): ?>
<meta property="og:locale:alternate" content="<?= e($_lc_val) ?>">
<?php endif; endforeach; ?>
<meta property="og:title" content="<?= e($_og_title) ?>">
<meta property="og:url" content="<?= e($_canonical_url) ?>">
<meta property="og:type" content="<?= e($_og_type) ?>">
<?php if ($_og_image): ?>
<meta property="og:image" content="<?= e($_og_image) ?>">
<meta property="og:image:alt" content="<?= e($_og_title) ?>">
<?php endif; ?>
<?php if ($_meta_desc): ?>
<meta property="og:description" content="<?= e($_meta_desc) ?>">
<?php endif; ?>
<?php if ($_pub_time && $_og_type === 'article'): ?>
<meta property="article:published_time" content="<?= e($_pub_time) ?>">
<?php endif; ?>
<?php if ($_fb_app): ?>
<meta property="fb:app_id" content="<?= e($_fb_app) ?>">
<?php endif; ?>

<meta name="twitter:card" content="<?= $_og_image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($_og_title) ?>">
<?php if ($_meta_desc): ?>
<meta name="twitter:description" content="<?= e($_meta_desc) ?>">
<?php endif; ?>
<?php if ($_og_image): ?>
<meta name="twitter:image" content="<?= e($_og_image) ?>">
<meta name="twitter:image:alt" content="<?= e($_og_title) ?>">
<?php endif; ?>

<?php if (!$_is_404): ?>
<script type="application/ld+json"><?= json_encode($_sat_json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>

<script>
/* FOUC-free init: zet menu- en font-size-state op <html> vóór de paint. */
(function () {
    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]+)'));
        return m ? decodeURIComponent(m[1]) : null;
    }
    try {
        var stored = localStorage.getItem('prjcts_menu');
        var isMobile = window.matchMedia && window.matchMedia('(max-width: 720px)').matches;
        // Eerste bezoek: mobile = closed (anders covert overlay alle content), desktop = open.
        // Eens gebruiker bewust kiest, blijft die keuze (localStorage).
        var menu = (stored === 'open' || stored === 'closed') ? stored : (isMobile ? 'closed' : 'open');
        document.documentElement.setAttribute('data-menu-state', menu);

        // Fontsize: cookie > localStorage > default. Cookie is cross-site (via constellation ?fontsize=).
        var fs = readCookie('prjcts_fontsize') || localStorage.getItem('prjcts_fontsize') || 'medium';
        if (['klein', 'medium', 'groot'].indexOf(fs) === -1) fs = 'medium';
        document.documentElement.setAttribute('data-fontsize', fs);
    } catch (e) {
        document.documentElement.setAttribute('data-menu-state', 'open');
        document.documentElement.setAttribute('data-fontsize', 'medium');
    }
})();
</script>

<?php
// Path-prefix naar de satelliet-root, mode-aware via static_url():
// path-modus → /<hub_root>/<host>, host-modus → '' (relatief).
$path_pref = rtrim(static_url('', $config), '/');

// Favicon — alleen tonen als de satelliet een favicon.ico in z'n root heeft.
$favicon_file = ($GLOBALS['_prjcts_root'] ?? '') . '/favicon.ico';
if (is_file($favicon_file)): ?>
<link rel="icon" type="image/x-icon" href="<?= e($path_pref . '/favicon.ico') ?>">
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Alegreya:ital,wght@0,400..700;1,400&family=IBM+Plex+Mono:ital,wght@0,300;0,400;1,300&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet" href="<?= e($shared_url) ?>/assets/shared.css">
<link rel="stylesheet" href="<?= e($shared_url) ?>/assets/menu.css">
<?php
// Per-satelliet site.css (optioneel) — wordt geladen NA shared.css zodat het kan overschrijven.
$site_css_file = ($GLOBALS['_prjcts_root'] ?? '') . '/assets/site.css';
$site_css_path = $path_pref !== '' ? $path_pref . '/assets/site.css' : '/assets/site.css';
if (is_file($site_css_file)):
?>
<link rel="stylesheet" href="<?= e($site_css_path) ?>">
<?php endif; ?>
<style>:root { <?= $style_vars ?> }</style>
</head>
<body data-register="<?= e($cfg['register']) ?>" data-satellite="<?= e($cfg['key']) ?>">

<?php
$menu_items = $page['menu_items'] ?? [];
if (!empty($menu_items)) {
    require __DIR__ . '/menu-sidepanel.php';
}
// Constellation: altijd zichtbaar op elke pagina
require __DIR__ . '/constellation.php';
?>

<!-- Lightbox overlay (fullscreen image/video preview, hidden tot click) -->
<div class="lightbox-overlay" id="prjcts-lightbox" aria-hidden="true">
    <button class="lightbox-close" type="button" aria-label="Sluit">&times;</button>
    <div class="lightbox-content"></div>
</div>

<div class="main-wrapper">
    <header class="prjcts-frame">
        <div class="prjcts-frame-trail">
            <a href="<?= e($hub_link) ?>" class="hub-back" title="terug naar Sebastien Vanblaere">&larr; sebastien</a>
            <span> · </span>
            <strong><?= e($cfg['title']) ?></strong>
        </div>

        <div class="prjcts-tools" aria-label="Tools">
            <div class="lang-tool" aria-label="Taal / Language">
                <?php
                $lang_labels = ['nl' => 'NL', 'en' => 'EN', 'fr' => 'FR'];
                $current_lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
                $current_uri  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
                $current_qs   = $_GET;
                foreach ($lang_labels as $code => $label):
                    $qs = $current_qs;
                    $qs['lang'] = $code;
                    $href = $current_uri . '?' . http_build_query($qs);
                ?>
                    <a href="<?= e($href) ?>"
                       hreflang="<?= e($code) ?>"
                       rel="alternate"
                       class="lang-btn <?= $code === $current_lang ? 'is-active' : '' ?>"
                       data-lang="<?= e($code) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>

            <div class="fontsize-tool" aria-label="Lettergrootte">
                <button type="button" class="fontsize-btn" data-size="klein"  title="Klein">A</button>
                <button type="button" class="fontsize-btn is-active" data-size="medium" title="Normaal">A</button>
                <button type="button" class="fontsize-btn" data-size="groot"  title="Groot">A</button>
            </div>
        </div>
    </header>
    <main>
