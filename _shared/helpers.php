<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_localhost_path_mode(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $host === 'localhost' || str_starts_with($host, 'localhost:') || $host === '127.0.0.1';
}

function detect_satellite(array $satellites): ?array
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Modus 1: host-gebaseerd (productie of lokaal met hosts-file)
    foreach ($satellites as $key => $sat) {
        if ($sat['host'] === $host) {
            return $sat + ['key' => $key];
        }
    }

    // Modus 2: path-gebaseerd (lokaal) — htdocs/<host>/index.php
    // Met de gescheiden layout staat elke site op één niveau onder htdocs/,
    // dus de parent-folder-naam is gelijk aan de host (prjcts.be, kunstmijnoren.be, ...).
    if (is_localhost_path_mode()) {
        $script = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $parent = basename(dirname($script));  // bv. 'prjcts.be'
        foreach ($satellites as $key => $sat) {
            if ($sat['host'] === $parent) {
                return $sat + ['key' => $key];
            }
        }
    }

    return null;
}

function build_css_vars(array $cfg): string
{
    $vars = [
        '--accent'        => $cfg['accent'],
        '--density'       => (string) $cfg['density'],
        '--frame-padding' => round((1 - $cfg['density']) * 4, 2) . 'rem',
    ];
    $out = '';
    foreach ($vars as $key => $value) {
        $out .= $key . ':' . $value . ';';
    }
    return $out;
}

/**
 * Bouw een URL die werkt in beide modi.
 *  - host-modus:    https://prjcts.be/about     (productie, eigen webspace per domein)
 *  - path-modus:    /prjcts.be/about            (lokaal, htdocs/<host>/)
 *
 * Zelf-link (binnen huidige satelliet): satellite_host = null en alleen page meegeven.
 */
function link_to(?string $satellite_host, string $page = '', array $config = []): string
{
    $mode     = $GLOBALS['_prjcts_mode'] ?? 'host';
    $hub_root = $config['hub_root'] ?? 'sebastienvanblaere.be';
    $page     = ltrim($page, '/');
    // Query-param routing: werkt zonder mod_rewrite (bv. legacy prjcts.be webspace).
    // Pages die later params toevoegen moeten '&key=value' gebruiken (niet '?').
    $query    = $page === '' ? '' : '?page=' . rawurlencode($page);

    if ($satellite_host === null) {
        // Zelf-link — relatief vanuit huidige host
        if ($mode === 'path') {
            $current = $GLOBALS['_prjcts_current'] ?? null;
            if ($current === null) {
                return '/' . $query;
            }
            return '/' . $hub_root . '/' . $current['host'] . '/' . $query;
        }
        return '/' . $query;
    }

    if ($mode === 'path') {
        return '/' . $hub_root . '/' . $satellite_host . '/' . $query;
    }
    // Productie: expliciete https-URL naar de doel-satelliet
    return 'https://' . $satellite_host . '/' . $query;
}

/**
 * URL naar een statisch bestand (image, file, etc.) binnen de huidige satelliet.
 *  - host-mode:    /<rel-pad>
 *  - path-mode:    /<hub_root>/<host>/<rel-pad>
 *
 * Gebruik dit voor media-URLs die NIET via dispatch hoeven te gaan.
 * (link_to() wraps in ?page= voor routing; static_url is een directe file-URL.)
 */
function static_url(string $rel, array $config = []): string
{
    $rel  = ltrim($rel, '/');
    $mode = $GLOBALS['_prjcts_mode'] ?? 'host';
    if ($mode === 'path') {
        $hub_root = $config['hub_root'] ?? 'sebastienvanblaere.be';
        $current  = $GLOBALS['_prjcts_current'] ?? null;
        $host     = $current['host'] ?? $hub_root;
        return '/' . $hub_root . '/' . $host . '/' . $rel;
    }
    return '/' . $rel;
}

function shared_asset_url(array $config): string
{
    $mode = $GLOBALS['_prjcts_mode'] ?? 'host';
    if ($mode === 'path') {
        // Lokaal nested: /<hub_root>/<host>/_shared
        $hub_root = $config['hub_root'] ?? 'sebastienvanblaere.be';
        $current  = $GLOBALS['_prjcts_current'] ?? null;
        $host     = $current['host'] ?? $hub_root;
        return '/' . $hub_root . '/' . $host . '/_shared';
    }
    // Productie (per-domein webspace): elke domein heeft eigen _shared kopie
    // → relatief, geen cross-domain.
    return '/_shared';
}

/**
 * Bouw een URL naar de gedeelde thumbnail-generator (_shared/thumb.php).
 * Werkt mode-aware: gebruikt absolute server-pad → relative-vanaf-DOCUMENT_ROOT.
 *
 * @param string $abs_path  Absolute filesystem-pad naar de bron-image
 * @param int    $w         Target width (default 400)
 * @param int|null $h       Target height (default = $w voor square)
 * @return string           URL of '' als sandbox-check faalt
 */
function thumb_url(string $abs_path, int $w = 400, ?int $h = null): string
{
    $h = $h ?? $w;
    // Resolveer relatief tot satelliet-root (de site die thumb.php aanroept).
    // In de gescheiden layout zit thumb.php via junction in elke site,
    // en de bron-images leven binnen die site (bv. content/projects/...).
    $current = $GLOBALS['_prjcts_current'] ?? null;
    $sat_root = $GLOBALS['_prjcts_root'] ?? null;
    if ($sat_root === null) {
        return '';
    }
    $sat_root_real = realpath($sat_root);
    $abs           = realpath($abs_path);
    if ($sat_root_real === false || $abs === false
     || strncmp($abs, $sat_root_real . DIRECTORY_SEPARATOR, strlen($sat_root_real) + 1) !== 0) {
        return '';
    }
    $rel  = ltrim(str_replace('\\', '/', substr($abs, strlen($sat_root_real))), '/');
    $mode = $GLOBALS['_prjcts_mode'] ?? 'host';
    if ($mode === 'path') {
        $hub_root = 'sebastienvanblaere.be';
        $host     = $current['host'] ?? $hub_root;
        $base     = '/' . $hub_root . '/' . $host . '/_shared';
    } else {
        $base = '/_shared';
    }
    return $base . '/thumb.php?p=' . rawurlencode($rel) . '&w=' . $w . '&h=' . $h;
}

function render_header(array $cfg, array $config, array $page = []): void
{
    $GLOBALS['_prjcts_current'] = $cfg;
    $style_vars = build_css_vars($cfg);
    $shared_url = shared_asset_url($config);
    $hub_link   = hub_url($config);
    require __DIR__ . '/partials/header.php';
}

function render_footer(array $cfg, array $config): void
{
    $hub_link = hub_url($config);
    require __DIR__ . '/partials/footer.php';
}

/** URL naar de linktree (sebastienvanblaere.be), mode-aware.
 *  Productie: altijd absolute https-URL — zo werkt de "← sebastien" knop
 *  ongeacht welk satelliet-domein we bedienen (kunstmijnoren.be,
 *  prjcts.be, p5.prjcts.be, etc.). */
function hub_url(array $config): string
{
    $hub_root = $config['hub_root'] ?? 'sebastienvanblaere.be';
    $mode     = $GLOBALS['_prjcts_mode'] ?? 'host';
    return $mode === 'path' ? '/' . $hub_root . '/' : 'https://' . $hub_root . '/';
}

function dispatch(string $satellite_root, array $cfg, array $config): void
{
    $GLOBALS['_prjcts_root'] = $satellite_root;
    $folder = basename($satellite_root);

    // Routing: prefer ?page= (werkt zonder mod_rewrite), fallback naar URL-pad
    // (voor sites waar rewrite wel werkt — bv. localhost en kunstmijnoren).
    $page_param = $_GET['page'] ?? '';
    if ($page_param !== '') {
        $key = preg_replace('/[^a-z0-9-]/', '', strtolower($page_param));
    } else {
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
        // In path-modus: strip de nested prefix '<hub_root>/<folder>/' uit het pad
        if (($GLOBALS['_prjcts_mode'] ?? 'host') === 'path') {
            $hub_root = $config['hub_root'] ?? 'sebastienvanblaere.be';
            $prefix   = $hub_root . '/' . $folder;
            if ($path === $prefix) {
                $path = '';
            } elseif (str_starts_with($path, $prefix . '/')) {
                $path = substr($path, strlen($prefix) + 1);
            }
        }
        $key = $path === '' ? 'home' : preg_replace('/[^a-z0-9-]/', '', strtolower($path));
    }
    if ($key === '' || $key === false || $key === null) {
        $key = 'home';
    }

    $page_file = $satellite_root . '/pages/' . $key . '.php';
    $title     = $key === 'home' ? null : $key;

    if (!is_file($page_file)) {
        http_response_code(404);
        $page_file = $satellite_root . '/pages/404.php';
        $title     = '404';
    }

    // Optioneel: per-satelliet menu-items
    $menu_items_file = $satellite_root . '/menu_items.php';
    $menu_items = is_file($menu_items_file) ? (require $menu_items_file) : [];
    if (!is_array($menu_items)) {
        $menu_items = [];
    }

    render_header($cfg, $config, ['title' => $title, 'menu_items' => $menu_items]);
    require $page_file;
    render_footer($cfg, $config);
}
