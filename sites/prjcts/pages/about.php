<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang   = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root   = realpath(__DIR__ . '/../content') ?: '';
$key    = isset($_GET['key']) ? preg_replace('/[^a-z0-9-]/i', '', (string) $_GET['key']) : '';
$abouts = discover_abouts($root, $lang);

$about_url = link_to(null, 'about', $config);

/** Bouw URL voor een media-bestand binnen een about-folder */
$media_url = function (array $about, string $filename) use ($config): string {
    $sat_root = realpath($GLOBALS['_prjcts_root']) ?: '';
    $abs      = realpath($about['dir'] . '/' . $filename) ?: '';
    if ($sat_root === '' || $abs === '' || strpos($abs, $sat_root) !== 0) {
        return '';
    }
    $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($sat_root))), '/');
    return link_to(null, $rel, $config);
};
?>

<?php if ($key !== '' && ($about = read_about($root, $key, $lang))): ?>
    <div class="prjcts-row">
        <div class="prjcts-col-main">
            <h1><?= e($about['title']) ?></h1>
            <div class="about-body"><?= $about['body'] ?></div>
        </div>

        <aside class="prjcts-col-side">
            <?php foreach ($about['media'] as $m):
                $url = $media_url($about, $m['filename']);
                if ($url === '') continue;
                if ($m['is_video']): ?>
                    <video class="about-media" src="<?= e($url) ?>" autoplay muted loop playsinline></video>
                <?php else: ?>
                    <img class="about-media" src="<?= e($url) ?>" alt="">
                <?php endif; ?>
            <?php endforeach; ?>
        </aside>
    </div>

<?php else: ?>
    <h1>about</h1>
    <?php if (empty($abouts)): ?>
        <p><em>geen abouts gevonden &mdash; drop een folder in <code>content/abouts/</code>.</em></p>
    <?php endif; ?>
<?php endif; ?>
