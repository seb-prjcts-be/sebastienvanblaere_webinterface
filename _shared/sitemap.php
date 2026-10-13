<?php
/**
 * Dynamische XML Sitemap-generator voor zowel de hub (sebastienvanblaere.be)
 * als alle satellieten (prjcts.be, kunstmijnoren.be, ...) met hreflang-ondersteuning.
 */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/content.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$xml_esc = static fn(string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

$satellites = isset($config['satellites']) && is_array($config['satellites']) ? $config['satellites'] : [];
$current_sat = detect_satellite($satellites);

/**
 * Zoek de satelliet-root op schijf als deze request voor een satelliet-host is.
 */
$sat_root = null;
if ($current_sat !== null) {
    $key  = (string) ($current_sat['key'] ?? '');
    $host = (string) ($current_sat['host'] ?? '');
    $candidates = [
        dirname(__DIR__) . '/sites/' . $key,
        dirname(__DIR__) . '/' . $host,
        dirname(__DIR__, 2) . '/' . $host,
    ];
    foreach ($candidates as $cand) {
        if (is_dir($cand . '/pages')) {
            $sat_root = $cand;
            break;
        }
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

if ($current_sat !== null && $sat_root !== null) {
    // === Satelliet-sitemap met volledige NL / EN / FR hreflang-clusters ===
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    $base_url = 'https://' . $current_sat['host'];
    $langs    = ['nl', 'en', 'fr'];

    $build_lang_loc = static function (string $rel_path, string $lang) use ($base_url): string {
        $url = $base_url . '/' . ltrim($rel_path, '/');
        if ($lang !== 'nl') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'lang=' . $lang;
        }
        return $url;
    };

    $emit_multilingual_url = static function (string $rel_path, int $mtime = 0, string $priority = '0.8', string $changefreq = 'monthly') use ($langs, $build_lang_loc, $xml_esc): void {
        $lastmod = $mtime > 0 ? gmdate('Y-m-d', $mtime) : '';
        foreach ($langs as $lang) {
            $loc = $build_lang_loc($rel_path, $lang);
            echo "  <url>\n";
            echo '    <loc>' . $xml_esc($loc) . "</loc>\n";
            if ($lastmod !== '') {
                echo '    <lastmod>' . $xml_esc($lastmod) . "</lastmod>\n";
            }
            echo '    <changefreq>' . $xml_esc($changefreq) . "</changefreq>\n";
            echo '    <priority>' . $xml_esc($priority) . "</priority>\n";
            foreach ($langs as $alt_lang) {
                $alt_href = $build_lang_loc($rel_path, $alt_lang);
                echo '    <xhtml:link rel="alternate" hreflang="' . $xml_esc($alt_lang) . '" href="' . $xml_esc($alt_href) . '" />' . "\n";
            }
            echo '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $xml_esc($build_lang_loc($rel_path, 'nl')) . '" />' . "\n";
            echo "  </url>\n";
        }
    };

    $content_root = $sat_root . '/content';
    $home_mtime   = is_dir($content_root . '/home') ? (filemtime($content_root . '/home') ?: 0) : 0;
    $emit_multilingual_url('', $home_mtime, '1.0', 'weekly');

    // Statische pagina's in pages/
    foreach (glob($sat_root . '/pages/*.php') ?: [] as $pfile) {
        $pkey = basename($pfile, '.php');
        if (in_array($pkey, ['home', '404'], true)) continue;
        $emit_multilingual_url('?page=' . rawurlencode($pkey), filemtime($pfile) ?: 0, '0.7', 'monthly');
    }

    // Abouts
    foreach (discover_abouts($content_root, 'nl') as $about) {
        $mtime = filemtime($about['dir']) ?: 0;
        $emit_multilingual_url('?page=about&key=' . rawurlencode((string) $about['key']), $mtime, '0.8', 'monthly');
    }

    // Projects per artform
    foreach (discover_artforms($content_root, 'nl') as $artform) {
        $af_key = (string) $artform['key'];
        $af_mtime = filemtime($artform['dir']) ?: 0;
        $emit_multilingual_url('?page=projects&artform=' . rawurlencode($af_key), $af_mtime, '0.8', 'monthly');

        foreach (discover_projects($artform['dir'], 'nl') as $proj) {
            $p_key = (string) $proj['key'];
            $p_mtime = filemtime($proj['dir']) ?: 0;
            $emit_multilingual_url(
                '?page=projects&artform=' . rawurlencode($af_key) . '&project=' . rawurlencode($p_key),
                $p_mtime,
                '0.9',
                'monthly'
            );
        }
    }

    // Writings
    foreach (discover_writings($content_root, 'nl') as $writing) {
        $w_key = (string) $writing['key'];
        $w_mtime = filemtime($writing['dir']) ?: 0;
        $emit_multilingual_url('?page=writings&key=' . rawurlencode($w_key), $w_mtime, '0.9', 'monthly');
    }

    echo "</urlset>\n";
    exit;
}

// === Hub-sitemap (sebastienvanblaere.be) ===
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$hub_root = (string) ($config['hub_root'] ?? 'sebastienvanblaere.be');
$hub_base = 'https://' . $hub_root;

$root_dir = dirname(__DIR__);
$index_mtime = is_file($root_dir . '/index.php') ? (filemtime($root_dir . '/index.php') ?: time()) : time();

$urls = [
    [
        'loc'        => $hub_base . '/',
        'lastmod'    => gmdate('Y-m-d', $index_mtime),
        'changefreq' => 'weekly',
        'priority'   => '1.0',
    ],
];

$constellation_path = $root_dir . '/services/constellation.json';
if (!is_file($constellation_path)) {
    $constellation_path = $root_dir . '/constellation.json';
}
$constellation_raw = is_file($constellation_path) ? file_get_contents($constellation_path) : false;
$constellation     = is_string($constellation_raw) ? json_decode($constellation_raw, true) : null;
$items = is_array($constellation['items'] ?? null) ? $constellation['items'] : [];

$seen = [$hub_base . '/' => true];

foreach ($items as $item) {
    if (!is_array($item)) continue;
    $u = (string) ($item['url'] ?? '');
    if ($u === '' || $u === '#') continue;
    if (rtrim($u, '/') === 'https://prjcts.be/services/fidk') {
        $u = 'https://sebastienvanblaere.be/services/audiovisueel/';
    }
    // Neem alleen URLs op het hub-domein zelf op in deze hub-sitemap (exclusief apps).
    if (str_starts_with($u, $hub_base . '/') && !str_contains($u, '/apps/') && !isset($seen[$u])) {
        $seen[$u] = true;
        $urls[] = [
            'loc'        => $u,
            'lastmod'    => gmdate('Y-m-d', $index_mtime),
            'changefreq' => 'monthly',
            'priority'   => '0.8',
        ];
    }
}

foreach ($urls as $entry) {
    echo "  <url>\n";
    echo '    <loc>' . $xml_esc($entry['loc']) . "</loc>\n";
    echo '    <lastmod>' . $xml_esc($entry['lastmod']) . "</lastmod>\n";
    echo '    <changefreq>' . $xml_esc($entry['changefreq']) . "</changefreq>\n";
    echo '    <priority>' . $xml_esc($entry['priority']) . "</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
