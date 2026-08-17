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
$socials = isset($profile['socials']) && is_array($profile['socials'])
    ? $profile['socials']
    : [];

$hub = isset($config['hub']) && is_array($config['hub']) ? $config['hub'] : [];

$page_title = (string) ($profile['name'] ?? $hub['title'] ?? 'Sebastien Vanblaere');
$page_desc  = (string) ($hub['description'] ?? '');
$page_tag   = (string) ($profile['tagline'] ?? $hub['tagline'] ?? '');
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

$item_url = function (array $item) use ($is_local, $site_url): string {
    if ($is_local && !empty($item['localPath'])) return (string) $item['localPath'];
    if ($is_local && !empty($item['key'])) {
        $local = $site_url((string) $item['key']);
        if ($local !== '#') return $local;
    }
    $url = $item['url'] ?? '#';
    return is_string($url) ? $url : '#';
};
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

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= $esc($page_title) ?>">
<meta property="og:locale" content="nl_BE">
<meta property="og:url" content="<?= $esc($canonical) ?>">
<meta property="og:title" content="<?= $esc($og_title) ?>">
<?php if ($page_desc): ?>
<meta property="og:description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>
<?php if ($og_image): ?>
<meta property="og:image" content="<?= $esc($og_image) ?>">
<?php endif; ?>

<meta name="twitter:card" content="<?= $og_image ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= $esc($og_title) ?>">
<?php if ($page_desc): ?>
<meta name="twitter:description" content="<?= $esc($page_desc) ?>">
<?php endif; ?>
<?php if ($og_image): ?>
<meta name="twitter:image" content="<?= $esc($og_image) ?>">
<?php endif; ?>

<link rel="canonical" href="<?= $esc($canonical) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
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
        font-family: 'IBM Plex Mono', 'Courier New', monospace;
        font-size: 0.7rem;
        font-weight: 400;
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
    a:focus-visible {
        outline: 2px solid #ff0000;
        outline-offset: 3px;
    }
    footer {
        margin-top: 2.5rem;
        text-align: center;
        font-size: 0.78rem;
        color: #909090;
    }
    footer a { color: inherit; text-decoration: none; }
    footer a:hover { color: #ff0000; }
    @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; }
    }
</style>
</head>
<body>
    <header>
        <h1><?= $esc($page_title) ?></h1>
        <?php if ($page_tag): ?>
        <p class="tagline"><?= $esc($page_tag) ?></p>
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
            <a href="<?= $esc($url) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?> aria-label="<?= $esc($label) ?>" title="<?= $esc($label) ?>"><i class="bi bi-<?= $esc((string) $icon) ?>" aria-hidden="true"></i></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>

    <nav aria-label="Projecten">
        <?php foreach ($items as $item):
            if (!is_array($item)) continue;
            $type  = (string) ($item['type'] ?? 'link');
            $label = (string) ($item['label'] ?? '');
            if ($type === 'header'): ?>
                <h2 class="nav-header"><?= $esc($label) ?></h2>
            <?php else:
                $url = $item_url($item);
                $external = !empty($item['external']);
            ?>
                <a href="<?= $esc($url) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>><?= $render_label($label) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <?php $email = (string) ($profile['email'] ?? ''); ?>
    <?php if ($email): ?>
    <footer><a href="mailto:<?= $esc($email) ?>"><?= $esc($email) ?></a></footer>
    <?php endif; ?>
</body>
</html>