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
    $pref_qs_parts[] = 'lang=' . urlencode($current_lang);
}
$current_fs = $_COOKIE['prjcts_fontsize'] ?? '';
if (in_array($current_fs, ['klein', 'medium', 'groot'], true)) {
    $pref_qs_parts[] = 'fontsize=' . urlencode($current_fs);
}
$lang_qs = $pref_qs_parts ? '?' . implode('&', $pref_qs_parts) : '';

$current_key = $cfg['key'] ?? '';

/** Helper: link naar een satelliet-key (via shared link_to). External wint. */
$sat_url = function (string $key) use ($config, $lang_qs): string {
    $sat = $config['satellites'][$key] ?? null;
    if ($sat === null) return '#';
    if (!empty($sat['external_url'])) return $sat['external_url'];
    return link_to($sat['host'], $lang_qs, $config);
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

// Ordered linktree-items — match sebastienvanblaere.be/index.php
$items = [
    ['type' => 'sat',      'label' => 'prjcts.be',                  'key'  => 'prjcts'],
    ['type' => 'sat',      'label' => 'kunstmijnoren.be',           'key'  => 'kunstmijnoren'],
    ['type' => 'sat',      'label' => 'p5.waves (library)',         'key'  => 'waves'],
    ['type' => 'sat',      'label' => 'p5.export (service)',        'key'  => 'export'],
    ['type' => 'external', 'label' => 'SVG_converter (service)',    'href' => $svc_url('svg')],
    ['type' => 'header',   'label' => 'Education'],
    ['type' => 'sat',      'label' => 'Creative coding for creative people.', 'key' => 'p5'],
    ['type' => 'external', 'label' => 'p5.blocks (concept)',        'href' => $svc_url('p5.blocks')],
    ['type' => 'external', 'label' => 'FIDK (Photography)',         'href' => $svc_url('fidk')],
];
?>
<button class="constellation-toggler" aria-label="Sebastien Vanblaere — links" aria-expanded="false" aria-controls="prjcts-constellation">
    <i class="bi bi-arrow-down-left  icon-closed" aria-hidden="true"></i>
    <i class="bi bi-arrow-down-right icon-open"   aria-hidden="true"></i>
</button>

<aside class="constellation-overlay" id="prjcts-constellation" aria-hidden="true">
    <div class="constellation-inner">
        <div class="constellation-heading">
            <strong>Sebastien Vanblaere</strong>
            <small>teacher · coder · maker · artist</small>
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
