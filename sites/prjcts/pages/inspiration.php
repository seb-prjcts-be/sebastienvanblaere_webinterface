<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root = realpath(__DIR__ . '/../content') ?: '';

$titles = [
    'nl' => 'Inspiratie',
    'en' => 'Inspiration',
    'fr' => 'Inspiration',
];
$page_title = $titles[$lang] ?? 'Inspiratie';

// Body content
$body_file = $root . '/inspiration/' . $lang . '.md';
if (!is_file($body_file)) $body_file = $root . '/inspiration/nl.md';
$body = is_file($body_file) ? file_get_contents($body_file) : '';

// YouTube list
$yt_file = $root . '/inspiration/youtube.json';
$yt_data = is_file($yt_file) ? json_decode((string) file_get_contents($yt_file), true) : ['groups' => []];

// Drop-folder: elke media-file in content/inspiration/ verschijnt als intro
$intro_media = discover_works($root . '/inspiration');

/** Bouw URL voor een media-bestand in content/inspiration/ */
$inspiration_url = function (string $filename) use ($config, $root): string {
    $sat_root = realpath($GLOBALS['_prjcts_root']) ?: '';
    $abs      = realpath($root . '/inspiration/' . $filename) ?: '';
    if ($sat_root === '' || $abs === '' || strpos($abs, $sat_root) !== 0) {
        return '';
    }
    $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($sat_root))), '/');
    return link_to(null, $rel, $config);
};
?>

<div class="prjcts-row">
    <div class="prjcts-col-main">
        <h1><?= e($page_title) ?></h1>
        <?= $body ?>
    </div>

    <aside class="prjcts-col-side">
        <?php foreach ($intro_media as $m):
            $url = $inspiration_url($m['filename']);
            if ($url === '') continue;
        ?>
            <?php if ($m['is_video']): ?>
                <video class="about-media" src="<?= e($url) ?>" autoplay muted loop playsinline></video>
            <?php else: ?>
                <img class="about-media" src="<?= e($url) ?>" alt="">
            <?php endif; ?>
        <?php endforeach; ?>

        <?php foreach ($yt_data['groups'] ?? [] as $group):
            $group_name = $group['name_' . $lang] ?? $group['name_nl'] ?? '';
        ?>
            <?php if ($group_name): ?>
                <h3><?= e($group_name) ?></h3>
            <?php endif; ?>
            <ul class="works-list">
                <?php foreach ($group['items'] ?? [] as $yt): ?>
                    <li>
                        <button type="button"
                                class="works-list-item"
                                data-src="https://www.youtube.com/embed/<?= e($yt['code']) ?>?autoplay=1"
                                data-type="youtube"
                                aria-label="<?= e($yt['title']) ?>">
                            <code><?= e($yt['title']) ?></code>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </aside>
</div>
