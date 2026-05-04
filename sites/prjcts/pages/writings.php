<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root = realpath(__DIR__ . '/../content') ?: '';

$titles = [
    'nl' => 'Writings',
    'en' => 'Writings',
    'fr' => 'Écrits',
];
$page_title = $titles[$lang] ?? 'Writings';

$key  = isset($_GET['key'])  ? preg_replace('/[^a-z0-9-]/i', '', (string) $_GET['key'])  : '';
$type = isset($_GET['type']) ? preg_replace('/[^a-z]/i',     '', (string) $_GET['type']) : '';

// Type-labels per taal — zelfde slug onder de motorkap
$type_labels = [
    'nl' => ['essay' => 'Essays', 'story' => 'Verhalen', 'research' => 'Onderzoek', 'letter' => 'Brieven'],
    'en' => ['essay' => 'Essays', 'story' => 'Stories',  'research' => 'Research',  'letter' => 'Letters'],
    'fr' => ['essay' => 'Essais', 'story' => 'Récits',   'research' => 'Recherche', 'letter' => 'Lettres'],
];
$L = $type_labels[$lang] ?? $type_labels['nl'];

$writings_url = link_to(null, 'writings', $config);

/** Bouw URL voor een media-bestand binnen een writing-folder */
$media_url = function (array $writing, string $filename) use ($config): string {
    $sat_root = realpath($GLOBALS['_prjcts_root']) ?: '';
    $abs      = realpath($writing['dir'] . '/' . $filename) ?: '';
    if ($sat_root === '' || $abs === '' || strpos($abs, $sat_root) !== 0) {
        return '';
    }
    $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($sat_root))), '/');
    return link_to(null, $rel, $config);
};
?>

<?php if ($key !== '' && ($w = read_writing($root, $key, $lang))): ?>
    <article class="writing-detail">
        <header>
            <p class="writing-meta">
                <?php if ($w['type'] !== '' && isset($L[$w['type']])): ?>
                    <a href="<?= e($writings_url . '?type=' . $w['type']) ?>" class="writing-type"><?= e($L[$w['type']]) ?></a>
                <?php endif; ?>
                <?php if ($w['date']): ?>
                    <span class="writing-date"><?= e($w['date']) ?></span>
                <?php endif; ?>
            </p>
            <h1><?= e($w['title']) ?></h1>
            <?php if ($w['subtitle']): ?>
                <p class="subtitle"><em><?= e($w['subtitle']) ?></em></p>
            <?php endif; ?>
        </header>

        <div class="writing-body"><?= render_md($w['body']) ?></div>

        <?php if (!empty($w['aside'])): ?>
            <aside class="writing-aside"><?= render_md($w['aside']) ?></aside>
        <?php endif; ?>

        <?php if (!empty($w['media'])): ?>
            <div class="works-grid">
                <?php foreach ($w['media'] as $m):
                    $url = $media_url($w, $m['filename']);
                    if ($url === '') continue;
                    $is_pdf = ($m['ext'] ?? '') === 'pdf';
                    if ($is_pdf)            $type_m = 'pdf';
                    elseif ($m['is_video']) $type_m = 'video';
                    elseif ($m['is_audio']) $type_m = 'audio';
                    else                    $type_m = 'image';
                    $cls = 'thumbnail-container'
                         . ($m['is_video'] ? ' is-video' : '')
                         . ($m['is_audio'] ? ' is-audio' : '')
                         . ($is_pdf       ? ' is-pdf'   : '');
                    $thumb = $type_m === 'image'
                        ? thumb_url($w['dir'] . '/' . $m['filename'], 400)
                        : '';
                ?>
                    <button type="button" class="<?= e($cls) ?>"
                            data-src="<?= e($url) ?>" data-type="<?= e($type_m) ?>"
                            aria-label="<?= e($m['filename']) ?>">
                        <?php if ($type_m === 'video'): ?>
                            <video src="<?= e($url) ?>" muted preload="metadata"></video>
                        <?php elseif ($type_m === 'audio'): ?>
                            <span class="thumb-icon"><i class="bi bi-music-note-beamed" aria-hidden="true"></i></span>
                            <span class="thumb-label"><?= e($m['filename']) ?></span>
                        <?php elseif ($type_m === 'pdf'): ?>
                            <span class="thumb-icon"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i></span>
                            <span class="thumb-label"><?= e($m['filename']) ?></span>
                        <?php else: ?>
                            <img src="<?= e($thumb !== '' ? $thumb : $url) ?>"
                                 loading="lazy" decoding="async" fetchpriority="low"
                                 width="400" height="400" alt="">
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="writing-back"><a href="<?= e($writings_url) ?>">&larr; <?= e($page_title) ?></a></p>
    </article>

<?php else:
    $items = discover_writings($root, $lang, $type);
?>
    <h1><?= e($page_title) ?></h1>

    <?php if (count($type_labels[$lang]) > 0): ?>
        <nav class="writing-filter" aria-label="Filter">
            <a href="<?= e($writings_url) ?>" class="<?= $type === '' ? 'is-active' : '' ?>"><?= $lang === 'fr' ? 'Tout' : ($lang === 'en' ? 'All' : 'Alles') ?></a>
            <?php foreach ($L as $t => $label): ?>
                <a href="<?= e($writings_url . '?type=' . $t) ?>" class="<?= $type === $t ? 'is-active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (empty($items)): ?>
        <p><em><?= $lang === 'fr' ? "Aucun écrit pour l'instant." : ($lang === 'en' ? 'No writings yet.' : 'Nog geen geschriften.') ?></em></p>
    <?php else: ?>
        <ul class="writings-list">
            <?php foreach ($items as $w): ?>
                <li class="writing-listitem">
                    <a href="<?= e($writings_url . '?key=' . $w['key']) ?>" class="writing-link">
                        <span class="writing-listitem-meta">
                            <?php if ($w['type'] !== '' && isset($L[$w['type']])): ?>
                                <span class="writing-type-tag"><?= e($L[$w['type']]) ?></span>
                            <?php endif; ?>
                            <?php if ($w['date']): ?>
                                <span class="writing-date"><?= e($w['date']) ?></span>
                            <?php endif; ?>
                        </span>
                        <h3 class="writing-listitem-title"><?= e($w['title'] ?: $w['key']) ?></h3>
                        <?php if ($w['subtitle']): ?>
                            <p class="writing-listitem-subtitle"><em><?= e($w['subtitle']) ?></em></p>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>
