<?php
/**
 * prjcts.be sidepanel-menu — overgenomen 1-op-1 van LIVE_prjcts3.0/menu.inc.php.
 * Items die we later niet meer nodig hebben, kunnen hier weg.
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

// === About menu (auto uit content/abouts/) ===
foreach (discover_abouts($root, $lang) as $a) {
    $items[] = ['type' => 'link', 'label' => $a['title'] ?: $a['key'], 'href' => '/about?key=' . $a['key']];
}

// === Externe verwijzingen (overgenomen uit oude menu.inc.php) ===
$items[] = ['type' => 'divider'];
$items[] = ['type' => 'external', 'label' => 'BYOB 2024', 'href' => 'https://checkbox.prjcts.be'];
$items[] = ['type' => 'external', 'label' => 'BYOB 2025', 'href' => 'https://social.prjcts.be'];
$items[] = ['type' => 'divider'];
$items[] = ['type' => 'external', 'label' => 'Zoomfield exposition', 'href' => '/zoomfield/index.html'];
$items[] = ['type' => 'divider'];

// === Artforms + projecten (auto uit content/projects/) ===
foreach (discover_artforms($root, $lang) as $artform) {
    $items[] = ['type' => 'category', 'label' => $artform['name'] ?: $artform['key']];
    foreach (discover_projects($artform['dir'], $lang) as $p) {
        $items[] = ['type' => 'link',
                    'label' => $p['name'] ?: $p['key'],
                    'href'  => '/projects?artform=' . $artform['key'] . '&project=' . $p['key']];
    }
}

// === Eind-menu ===
$end_labels = [
    'nl' => ['writings' => 'Writings',  'bio' => 'Bio', 'inspiration' => 'Inspiratie',  'guests' => 'Gastenboek', 'contact' => 'Contact'],
    'en' => ['writings' => 'Writings',  'bio' => 'Bio', 'inspiration' => 'Inspiration', 'guests' => 'Guests',     'contact' => 'Contact'],
    'fr' => ['writings' => 'Écrits',    'bio' => 'Bio', 'inspiration' => 'Inspiration', 'guests' => "Livre d'or", 'contact' => 'Contact'],
];
$L = $end_labels[$lang] ?? $end_labels['nl'];

$items[] = ['type' => 'divider'];
$items[] = ['type' => 'link', 'label' => $L['writings'],    'href' => '/writings'];
$items[] = ['type' => 'link', 'label' => $L['bio'],         'href' => '/bio'];
$items[] = ['type' => 'link', 'label' => $L['inspiration'], 'href' => '/inspiration'];
$items[] = ['type' => 'link', 'label' => $L['guests'],      'href' => '/guests'];
$items[] = ['type' => 'link', 'label' => $L['contact'],     'href' => '/contact'];

// Cross-satelliet shortcut — gebruikt link_to() dus werkt path-mode (localhost) én host-mode.
$items[] = ['type' => 'divider'];
$items[] = ['type' => 'satellite', 'key' => 'kunstmijnoren', 'label' => 'kunstmijnoren.be'];

return $items;
