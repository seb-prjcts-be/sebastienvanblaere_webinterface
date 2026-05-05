<?php
/**
 * Sidepanel-menu — data-driven, met auto-constellation onderaan.
 *
 * Verwacht in scope:
 *   $menu_items   array met item-types: home, header, link, category, divider, external, satellite
 *   $cfg          huidige satelliet-config (heeft 'key')
 *   $config       hele config
 *
 * De "andere satellieten"-strip onderaan wordt automatisch gegenereerd uit
 * $config['satellites'] (alle behalve de huidige). Geen sister-items meer
 * nodig in per-satelliet menu_items.php — die zijn vervangen door deze
 * constellation.
 *
 * @var array $menu_items
 * @var array $cfg
 * @var array $config
 */
declare(strict_types=1);

// (cross-satelliet links zijn nu in de rechter constellation-offcanvas — niet meer hier)
?>
<button class="menu-toggler" aria-label="Menu" aria-controls="prjcts-menu"><span class="menu-toggler-icon" aria-hidden="true"></span></button>

<div class="menu-overlay" id="prjcts-menu" aria-hidden="false">
    <nav>
        <div class="menu-scrollable">
        <?php foreach (($menu_items ?? []) as $item):
            $type = $item['type'] ?? 'link';
            if ($type === 'sister') continue; // legacy, wordt genegeerd — constellation vervangt sister-buttons
        ?>
            <?php if ($type === 'home'): ?>
                <a href="<?= e(link_to(null, '', $config)) ?>" class="menu-link home-link">
                    <h1><?= e($item['label'] ?? '') ?></h1>
                </a>
            <?php elseif ($type === 'header'): ?>
                <?php if (!empty($item['href'])): ?>
                    <a href="<?= e($item['href']) ?>" class="menu-link"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>>
                        <h4><?= e($item['label'] ?? '') ?></h4>
                    </a>
                <?php else: ?>
                    <h4 class="menu-link"><?= e($item['label'] ?? '') ?></h4>
                <?php endif; ?>
            <?php elseif ($type === 'category'): ?>
                <div class="menu-category"><?= e($item['label'] ?? '') ?></div>
            <?php elseif ($type === 'divider'): ?>
                <div class="menu-divider"></div>
            <?php elseif ($type === 'external'): ?>
                <a href="<?= e($item['href'] ?? '#') ?>" class="menu-link" target="_blank" rel="noopener">
                    <?= e($item['label'] ?? '') ?> &#8599;
                </a>
            <?php elseif ($type === 'satellite'):
                $sat = $config['satellites'][$item['key']] ?? null;
                if ($sat):
                    // Geef huidige taal mee aan cross-satelliet links
                    $sat_lang = $GLOBALS['_prjcts_lang'] ?? '';
                    $sat_page = $item['page'] ?? '';
                    if ($sat_lang) {
                        $sat_page .= (strpos($sat_page, '?') === false ? '?' : '&') . 'lang=' . urlencode($sat_lang);
                    }
            ?>
                <a href="<?= e(link_to($sat['host'], $sat_page, $config)) ?>" class="menu-link">
                    <?= e($item['label'] ?? $sat['title']) ?>
                </a>
            <?php endif; ?>
            <?php else: /* default = link — href is reeds een complete URL */ ?>
                <a href="<?= e($item['href'] ?? '#') ?>" class="menu-link">
                    <?= e($item['label'] ?? '') ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

    </nav>
</div>
