<?php
/**
 * Constellation — rechts-offcanvas, fungeert als snelle linktree-drawer.
 * Toont dezelfde links als sebastienvanblaere.be/index.php (de landing),
 * met de huidige satelliet visueel gemarkeerd.
 *
 * @var array $cfg     huidige satelliet-config (heeft 'key')
 * @var array $config  hele config
 */
declare(strict_types=1);

// User-prefs meegeven zodat lang/fontsize blijven hangen tussen sites.
$pref_qs_parts = [];
$current_lang = $GLOBALS['_prjcts_lang'] ?? '';
if ($current_lang !== '') {
    $pref_qs_parts[] = 'lang=' . rawurlencode($current_lang);
}
$current_fs = $_COOKIE['prjcts_fontsize'] ?? '';
if (in_array($current_fs, ['klein', 'medium', 'groot'], true)) {
    $pref_qs_parts[] = 'fontsize=' . rawurlencode($current_fs);
}
$pref_qs = $pref_qs_parts ? implode('&', $pref_qs_parts) : '';

$current_key = $cfg['key'] ?? '';

/** Helper: link naar een satelliet-key (via shared link_to). External wint.
 *  Voegt user-prefs (lang/fontsize) als extra query-string toe. */
$sat_url = function (string $key) use ($config, $pref_qs): string {
    $sat = $config['satellites'][$key] ?? null;
    if ($sat === null) return '#';
    if (!empty($sat['external_url'])) return $sat['external_url'];
    $url = link_to($sat['host'], '', $config);
    if ($pref_qs !== '') {
        $url .= (strpos($url, '?') === false ? '?' : '&') . $pref_qs;
    }
    return $url;
};
$sat_external = function (string $key) use ($config): bool {
    return !empty($config['satellites'][$key]['external_url']);
};

// URL-helper voor services (mode-aware): lokaal nested path, productie via prjcts.be
$mode_now = $GLOBALS['_prjcts_mode'] ?? 'host';
$svc_url  = function (string $name) use ($config, $mode_now): string {
    $hub = $config['hub_root'] ?? 'sebastienvanblaere.be';
    return $mode_now === 'path'
        ? '/' . $hub . '/services/' . $name . '/'
        : 'https://prjcts.be/services/' . $name . '/';
};

// CV-service leeft op de hub (sebastienvanblaere.be), niet op prjcts.be.
$hub_host = $config['hub_root'] ?? 'sebastienvanblaere.be';
$cv_url   = $mode_now === 'path'
    ? '/' . $hub_host . '/services/cv/'
    : 'https://' . $hub_host . '/services/cv/';

// Ordered linktree-items — match sebastienvanblaere.be/index.php
$items = [
    ['type' => 'sat',      'label' => 'prjcts.be',                  'key'  => 'prjcts'],
    ['type' => 'sat',      'label' => 'kunstmijnoren.be',           'key'  => 'kunstmijnoren'],
    ['type' => 'sat',      'label' => 'p5waves.org (library + service)', 'key' => 'p5waves'],
    ['type' => 'sat',      'label' => 'p5.export (service)',        'key'  => 'export'],
    ['type' => 'external', 'label' => 'SVG_converter (service)',    'href' => $svc_url('svg')],
    ['type' => 'header',   'label' => 'Education'],
    ['type' => 'sat',      'label' => 'Creative coding for creative people.', 'key' => 'p5'],
    ['type' => 'external', 'label' => 'p5.blocks (concept)',        'href' => $svc_url('p5.blocks')],
    ['type' => 'external', 'label' => 'FIDK (Photography)',         'href' => $svc_url('fidk')],
    ['type' => 'header',   'label' => 'Resume'],
    ['type' => 'external', 'label' => 'curriculum vitae',           'href' => $cv_url],
];
?>
<button class="constellation-toggler" aria-label="Sebastien Vanblaere — links" aria-expanded="false" aria-controls="prjcts-constellation">
    <i class="bi bi-arrow-down-left  icon-closed" aria-hidden="true"></i>
    <i class="bi bi-arrow-down-right icon-open"   aria-hidden="true"></i>
</button>

<aside class="constellation-overlay" id="prjcts-constellation" aria-hidden="true">
    <div class="constellation-inner">
        <div class="constellation-heading">
            <h2 class="constellation-name">Sebastien Vanblaere</h2>
            <div class="constellation-socials">
                <a href="https://www.instagram.com/prjcts.be/" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="https://www.linkedin.com/in/sebvanb" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="https://www.buymeacoffee.com/prjcts" target="_blank" rel="noopener" aria-label="Buy me a coffee?" title="Buy me a coffee?"><i class="bi bi-cup-hot-fill"></i></a>
            </div>
        </div>

        <ul class="constellation-list">
            <?php foreach ($items as $it): ?>
                <li>
                    <?php if ($it['type'] === 'header'): ?>
                        <div class="constellation-header"><?= e($it['label']) ?></div>
                    <?php elseif ($it['type'] === 'external'): ?>
                        <?php
                        // External-services blijven in eigen tab (zelfde site-context),
                        // echte external links (Instagram, LinkedIn) openen in nieuw venster.
                        $is_offsite = !empty($it['offsite']);
                        ?>
                        <a href="<?= e($it['href']) ?>" class="constellation-card"<?= $is_offsite ? ' target="_blank" rel="noopener"' : '' ?>>
                            <span class="constellation-card-title"><?= e($it['label']) ?></span>
                        </a>
                    <?php else:
                        $is_current  = $it['key'] === $current_key;
                        $is_external = $sat_external($it['key']);
                    ?>
                        <a href="<?= e($sat_url($it['key'])) ?>"
                           class="constellation-card<?= $is_current ? ' is-current' : '' ?>"
                           <?= $is_current ? 'aria-current="page"' : '' ?>
                           <?= $is_external ? 'target="_blank" rel="noopener"' : '' ?>>
                            <span class="constellation-card-title"><?= e($it['label']) ?></span>
                            <?php if (!empty($it['desc'])): ?>
                                <span class="constellation-card-desc"><?= e($it['desc']) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</aside>
