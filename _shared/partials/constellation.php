<?php
/**
 * Constellation — rechts-offcanvas, gevoed door constellation.json.
 *
 * @var array $cfg
 * @var array $config
 */
declare(strict_types=1);

$data_path = dirname(__DIR__, 2) . '/constellation.json';
$data_raw  = is_file($data_path) ? file_get_contents($data_path) : false;
$data      = is_string($data_raw) ? json_decode($data_raw, true) : null;
if (!is_array($data)) $data = [];

$profile = isset($data['profile']) && is_array($data['profile']) ? $data['profile'] : [];
$items   = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$socials = isset($profile['socials']) && is_array($profile['socials']) ? $profile['socials'] : [];

$pref_qs_parts = [];
$current_lang = $GLOBALS['_prjcts_lang'] ?? '';
if ($current_lang !== '') $pref_qs_parts[] = 'lang=' . rawurlencode($current_lang);
$current_fs = $_COOKIE['prjcts_fontsize'] ?? '';
if (in_array($current_fs, ['klein', 'medium', 'groot'], true)) {
    $pref_qs_parts[] = 'fontsize=' . rawurlencode($current_fs);
}
$pref_qs = $pref_qs_parts ? implode('&', $pref_qs_parts) : '';

$current_key = $cfg['key'] ?? '';
$mode_now    = $GLOBALS['_prjcts_mode'] ?? 'host';

$item_url = function (array $item) use ($config, $mode_now, $pref_qs): string {
    $url = (string) ($item['url'] ?? '#');
    $key = (string) ($item['key'] ?? '');

    if ($mode_now === 'path') {
        if ($key !== '' && isset($config['satellites'][$key])) {
            $url = link_to($config['satellites'][$key]['host'], '', $config);
        } elseif (!empty($item['localPath'])) {
            $url = (string) $item['localPath'];
        }
    }

    if ($pref_qs !== '' && $key !== '' && empty($item['external'])) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . $pref_qs;
    }

    return $url;
};
?>
<button class="constellation-toggler" aria-label="Sebastien Vanblaere — links" aria-expanded="false" aria-controls="prjcts-constellation">
    <i class="bi bi-arrow-down-left icon-closed" aria-hidden="true"></i>
    <i class="bi bi-arrow-down-right icon-open" aria-hidden="true"></i>
</button>

<aside class="constellation-overlay" id="prjcts-constellation" aria-hidden="true">
    <div class="constellation-inner">
        <div class="constellation-heading">
            <h2 class="constellation-name"><?= e((string) ($profile['name'] ?? 'Sebastien Vanblaere')) ?></h2>
            <?php if ($socials): ?>
            <div class="constellation-socials">
                <?php foreach ($socials as $social):
                    if (!is_array($social)) continue;
                    $label = (string) ($social['label'] ?? 'Link');
                    $url   = (string) ($social['url'] ?? '#');
                    $icon  = preg_replace('/[^a-z0-9-]/i', '', (string) ($social['icon'] ?? 'link-45deg'));
                    $external = !empty($social['external']);
                ?>
                <a href="<?= e($url) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?> aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><i class="bi bi-<?= e($icon) ?>"></i></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <ul class="constellation-list">
            <?php foreach ($items as $item):
                if (!is_array($item)) continue;
                $type  = (string) ($item['type'] ?? 'link');
                $label = (string) ($item['label'] ?? '');
            ?>
                <li>
                    <?php if ($type === 'header'): ?>
                        <div class="constellation-header"><?= e($label) ?></div>
                    <?php else:
                        $key        = (string) ($item['key'] ?? '');
                        $is_current = $key !== '' && $key === $current_key;
                        $external   = !empty($item['external']);
                        $href       = $item_url($item);
                    ?>
                        <a href="<?= e($href) ?>"
                           class="constellation-card<?= $is_current ? ' is-current' : '' ?>"
                           <?= $is_current ? 'aria-current="page"' : '' ?>
                           <?= $external ? 'target="_blank" rel="noopener"' : '' ?>>
                            <span class="constellation-card-title"><?= nl2br(e($label), false) ?></span>
                            <?php if (!empty($item['desc'])): ?>
                                <span class="constellation-card-desc"><?= e((string) $item['desc']) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</aside>
