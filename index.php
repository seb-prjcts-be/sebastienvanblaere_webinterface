<?php
/**
 * sebastienvanblaere.be — landing / linktree.
 * Toont alleen als de host géén satelliet is. Productie-routing in .htaccess
 * stuurt prjcts.be en kunstmijnoren.be al door naar sites/<key>/.
 */
declare(strict_types=1);

$config = require __DIR__ . '/_shared/config.php';
$host   = $_SERVER['HTTP_HOST'] ?? '';

// In productie: redirect naar de juiste site als deze host bij een hub-satelliet hoort
foreach ($config['satellites'] as $key => $sat) {
    if (!empty($sat['in_hub']) && $sat['host'] === $host) {
        header('Location: /sites/' . $key . '/', true, 302);
        exit;
    }
}

$is_local = $host === 'localhost' || str_starts_with($host, 'localhost:') || $host === '127.0.0.1';

/** Bouw URL naar een satelliet (mode-aware).
 *  Lokaal nested: /sebastienvanblaere.be/<host>/ — elke site is folder onder hub.
 *  Productie: https://<host>/ — elk domein heeft eigen webspace. */
$hub_root = $config['hub_root'] ?? 'sebastienvanblaere.be';
$site_url = function (string $key) use ($config, $is_local, $hub_root): string {
    $sat = $config['satellites'][$key] ?? null;
    if ($sat === null) return '#';
    // External-only satellieten (bv. GitHub Pages) — altijd absolute URL
    if (!empty($sat['external_url'])) {
        return $sat['external_url'];
    }
    if ($is_local) {
        return '/' . $hub_root . '/' . $sat['host'] . '/';
    }
    return 'https://' . $sat['host'] . '/';
};

// External-flag aan template doorgeven (voor target=_blank)
$site_external = function (string $key) use ($config): bool {
    return !empty($config['satellites'][$key]['external_url']);
};

// Hub-meta — SEO + Open Graph
$hub        = $config['hub'] ?? [];
$page_title = $hub['title']       ?? 'Sebastien Vanblaere';
$page_desc  = $hub['description'] ?? '';
$page_kws   = isset($hub['keywords']) ? implode(', ', $hub['keywords']) : '';
$page_tag   = $hub['tagline']     ?? '';
$page_auth  = $hub['author']      ?? '';
$og_title   = $hub['og_title']    ?? $page_title;
$og_image   = $hub['og_image']    ?? '';
$canonical  = ($is_local ? '/' . $hub_root . '/' : 'https://' . $hub_root . '/');

// Maak og_image absolute (social-media crawlers vereisen volledige URL)
if ($og_image !== '' && !preg_match('#^https?://#', $og_image)) {
    $abs_base = $is_local ? '/' . $hub_root : 'https://' . $hub_root;
    $og_image = $abs_base . '/' . ltrim($og_image, '/');
}

// Helper voor escape in HTML attributes
$esc = fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
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

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $esc($canonical) ?>">
<meta property="og:title" content="<?= $esc($og_title) ?>">
<?php if ($page_desc): ?>
<meta property="og:description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>
<?php if ($og_image): ?>
<meta property="og:image" content="<?= $esc($og_image) ?>">
<?php endif; ?>

<!-- Twitter -->
<meta name="twitter:card" content="summary<?= $og_image ? '_large_image' : '' ?>">
<meta name="twitter:title" content="<?= $esc($og_title) ?>">
<?php if ($page_desc): ?>
<meta name="twitter:description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>

<link rel="canonical" href="<?= $esc($canonical) ?>">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=IBM+Plex+Mono:wght@300;400&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { min-height: 100%; }
    body {
        background: #f5f5f5;
        color: #121212;
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
        color: #606060;
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
        color: #121212;
        transition: color 0.15s;
    }
    .socials a:hover { color: #ff0000; }

    nav { display: flex; flex-direction: column; gap: 0.75rem; width: 100%; }
    .nav-header {
        font-size: 0.7rem;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: #888;
        margin-top: 1rem;
        padding-bottom: 0.4rem;
        border-bottom: 1px solid #ddd;
        text-align: center;
    }
    nav a {
        color: #121212;
        text-decoration: none;
        font-size: 1rem;
        padding: 1rem 1.1rem;
        border: 1px solid #d0d0d0;
        background: #fafafa;
        text-align: center;
        transition: border-color 0.15s, color 0.15s, background 0.15s;
    }
    nav a:hover {
        color: #ff0000;
        border-color: #ff0000;
        background: #ffffff;
    }
    nav a.featured {
        padding: 0;
        overflow: hidden;
        position: relative;
    }
    nav a.featured img {
        display: block;
        width: 100%;
        height: auto;
    }
    nav a.featured span {
        display: block;
        padding: 0.85rem 1.1rem;
        border-top: 1px solid #d0d0d0;
        background: #fafafa;
    }
    nav a.featured:hover span {
        background: #ffffff;
    }
    footer {
        margin-top: 2.5rem;
        text-align: center;
        font-size: 0.78rem;
        color: #909090;
    }
</style>
</head>
<body>
    <header>
        <h1><?= $esc($page_title) ?></h1>
        <?php if ($page_tag): ?>
        <p class="tagline"><?= $esc($page_tag) ?></p>
        <?php endif; ?>
        <div class="socials">
            <a href="https://www.instagram.com/prjcts.be/" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="https://www.linkedin.com/in/sebvanb" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
    </header>

    <nav>
        <a href="<?= $site_url('prjcts') ?>">prjcts.be</a>
        <a href="<?= $site_url('kunstmijnoren') ?>">kunstmijnoren.be</a>
        <a href="<?= $site_url('waves') ?>"<?= $site_external('waves') ? ' target="_blank" rel="noopener"' : '' ?>>p5.waves (library)</a>
        <a href="<?= $site_url('export') ?>">p5.export (service)</a>
        <a href="<?= $is_local ? '/sebastienvanblaere.be/services/svg/' : 'https://prjcts.be/services/svg/' ?>">SVG_converter (service)</a>

        <div class="nav-header">Education</div>
        <a href="<?= $site_url('p5') ?>">Creative coding<br>for creative people.</a>
        <a href="<?= $is_local ? '/sebastienvanblaere.be/services/p5.blocks/' : 'https://prjcts.be/services/p5.blocks/' ?>">p5.blocks (concept)</a>
        <a href="<?= $is_local ? '/sebastienvanblaere.be/services/fidk/' : 'https://prjcts.be/services/fidk/' ?>">FIDK (Photography)</a>
    </nav>

    <footer>seb@prjcts.be</footer>
</body>
</html>
