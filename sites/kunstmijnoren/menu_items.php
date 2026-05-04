<?php
/**
 * kunstmijnoren.be sidepanel-menu — overgenomen van LIVE_kunstmijnoren/menu.inc.php.
 * Volgorde: artforms + projecten eerst (kunstmijnoren benadrukt het werk), dan abouts.
 *
 * @var string $satellite_root  uit dispatch()
 * @var array  $config
 */
declare(strict_types=1);

$root = $satellite_root . '/content';
$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';

$items = [
    ['type' => 'home',   'label' => $cfg['home_label'] ?? $cfg['title']],
    ['type' => 'header', 'label' => 'Sebastien Vanblaere', 'href' => 'https://www.instagram.com/prjcts.be/', 'external' => true],
];

// === Artforms + projecten eerst (oude kunstmijnoren-volgorde) ===
foreach (discover_artforms($root, $lang) as $artform) {
    $items[] = ['type' => 'category', 'label' => $artform['name'] ?: $artform['key']];
    foreach (discover_projects($artform['dir'], $lang) as $p) {
        $items[] = ['type' => 'link',
                    'label' => $p['name'] ?: $p['key'],
                    'href'  => '/projects?artform=' . $artform['key'] . '&project=' . $p['key']];
    }
}

$items[] = ['type' => 'divider'];

// === About menu daarna ===
foreach (discover_abouts($root, $lang) as $a) {
    $items[] = ['type' => 'link', 'label' => $a['title'] ?: $a['key'], 'href' => '/about?key=' . $a['key']];
}

// === Gastenboek (Elfsight Comments-widget) ===
$guests_labels = ['nl' => 'Gastenboek', 'en' => 'Guests', 'fr' => "Livre d'or"];
$items[] = ['type' => 'divider'];
$items[] = ['type' => 'link', 'label' => $guests_labels[$lang] ?? 'Gastenboek', 'href' => '/guests'];

return $items;
