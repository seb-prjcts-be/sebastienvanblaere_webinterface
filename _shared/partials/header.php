<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($cfg['title']) ?><?= isset($page['title']) && $page['title'] !== null ? ' / ' . e($page['title']) : '' ?></title>

<?php
// === SEO + social meta ===
// Pagina-overrides via $page['description'], $page['og_image'], $page['og_title'].
$_proto    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? $cfg['host'];
$_uri      = $_SERVER['REQUEST_URI'] ?? '/';
$_meta_desc  = $page['description']  ?? ($cfg['tagline'] ?? $cfg['description'] ?? '');
$_meta_kw    = isset($cfg['keywords']) ? implode(', ', $cfg['keywords']) : '';
$_meta_auth  = $cfg['author']        ?? '';
$_og_title   = $page['og_title']     ?? ($cfg['og_title'] ?? ($cfg['title'] . (isset($page['title']) && $page['title'] !== null ? ' / ' . $page['title'] : '')));
$_og_image   = $page['og_image']     ?? ($cfg['og_image'] ?? '');
// Relatief og_image-pad → absolute URL (op productie-host, niet localhost)
if ($_og_image !== '' && !preg_match('~^https?://~i', $_og_image)) {
    $_og_image = 'https://' . $cfg['host'] . '/' . ltrim($_og_image, '/');
}
$_og_url     = $_proto . '://' . $_host . $_uri;
$_fb_app     = $cfg['fb_app_id']     ?? '';
?>
<?php if ($_meta_desc): ?>
<meta name="description" content="<?= e($_meta_desc) ?>">
<?php endif; ?>
<?php if ($_meta_kw): ?>
<meta name="keywords" content="<?= e($_meta_kw) ?>">
<?php endif; ?>
<?php if ($_meta_auth): ?>
<meta name="author" content="<?= e($_meta_auth) ?>">
<?php endif; ?>

<meta property="og:title" content="<?= e($_og_title) ?>">
<meta property="og:url" content="<?= e($_og_url) ?>">
<meta property="og:type" content="website">
<?php if ($_og_image): ?>
<meta property="og:image" content="<?= e($_og_image) ?>">
<?php endif; ?>
<?php if ($_meta_desc): ?>
<meta property="og:description" content="<?= e($_meta_desc) ?>">
<?php endif; ?>
<?php if ($_fb_app): ?>
<meta property="fb:app_id" content="<?= e($_fb_app) ?>">
<?php endif; ?>

<script>
/* FOUC-free init: zet menu- en font-size-state op <html> vóór de paint. */
(function () {
    function readCookie(name) {
        var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]+)'));
        return m ? decodeURIComponent(m[1]) : null;
    }
    try {
        var menu = localStorage.getItem('prjcts_menu') || 'open';        // default OPEN
        if (menu !== 'open' && menu !== 'closed') menu = 'open';
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
// Path-prefix: in path-modus zit elke site op /<host>/, in host-modus is alles relatief.
$mode      = $GLOBALS['_prjcts_mode'] ?? 'host';
$path_pref = $mode === 'path' ? '/' . $cfg['host'] : '';

// Favicon — alleen tonen als de satelliet een favicon.ico in z'n root heeft.
$favicon_file = ($GLOBALS['_prjcts_root'] ?? '') . '/favicon.ico';
if (is_file($favicon_file)): ?>
<link rel="icon" type="image/x-icon" href="<?= e($path_pref . '/favicon.ico') ?>">
<?php endif; ?>

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
