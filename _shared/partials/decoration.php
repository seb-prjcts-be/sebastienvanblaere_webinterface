<?php
/**
 * decoration.php — laadt p5 + p5play + de decoratie-engine, met per-pagina config.
 *
 * GEBRUIK in een pagina:
 *
 *   <?php
 *     // (optioneel) custom config
 *     $decoration = [
 *         'count'        => 3,
 *         'colors'       => ['#ff0000'],
 *         'fill'         => '#f1f1f1',
 *         'diameter'     => 80,
 *         'gravity'      => 10,
 *         'enableCursor' => true,
 *         'obstacleSelector' => 'img, .thumbnail-container',
 *         'persistKey'   => 'ballsState'
 *     ];
 *     require __DIR__ . '/../../../_shared/partials/decoration.php';
 *   ?>
 *
 * Of eenvoudiger via dispatch — zet `$page['decoration'] = []` (of met config) en
 * laat de footer-partial dit auto-includeren.
 *
 * @var array $decoration  per-pagina overrides (mag leeg zijn voor defaults)
 */
declare(strict_types=1);

$decoration = $decoration ?? [];

// p5 + planck via CDN; p5play LOKAAL (geteste versie uit prjcts3.0;
// p5play.org/v3/ kan stilzwijgend updaten en API-changes brengen).
$cdn_p5     = 'https://cdn.jsdelivr.net/npm/p5@1.11.4/lib/p5.js';
$cdn_planck = 'https://cdn.jsdelivr.net/npm/planck@latest/dist/planck.min.js';

$shared_url = shared_asset_url($config);
?>
<script>window.DECORATION_CONFIG = <?= json_encode($decoration, JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e($cdn_p5) ?>"></script>
<script src="<?= e($cdn_planck) ?>"></script>
<script src="<?= e($shared_url) ?>/assets/p5play.js"></script>
<script src="<?= e($shared_url) ?>/assets/decoration.js"></script>
