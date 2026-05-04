<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root = realpath(__DIR__ . '/../content') ?: '';

$titles = [
    'nl' => 'Contact',
    'en' => 'Contact',
    'fr' => 'Contact',
];
$page_title = $titles[$lang] ?? 'Contact';

// Body content
$body_file = $root . '/contact/' . $lang . '.md';
if (!is_file($body_file)) $body_file = $root . '/contact/nl.md';
$body = is_file($body_file) ? file_get_contents($body_file) : '';

// Drop-folder: elke media-file in content/contact/ verschijnt in de sidebar
$contact_media = discover_works($root . '/contact');

/** Bouw URL voor een media-bestand in content/contact/ */
$contact_url = function (string $filename) use ($config, $root): string {
    $sat_root = realpath($GLOBALS['_prjcts_root']) ?: '';
    $abs      = realpath($root . '/contact/' . $filename) ?: '';
    if ($sat_root === '' || $abs === '' || strpos($abs, $sat_root) !== 0) {
        return '';
    }
    $rel = ltrim(str_replace('\\', '/', substr($abs, strlen($sat_root))), '/');
    return link_to(null, $rel, $config);
};
?>

<div class="contact-layout">
    <div class="contact-text">
        <h1><?= e($page_title) ?></h1>
        <?= $body ?>
    </div>

    <div class="contact-photo">
        <?php foreach ($contact_media as $m):
            $url = $contact_url($m['filename']);
            if ($url === '') continue;
        ?>
            <?php if ($m['is_video']): ?>
                <video class="contact-media" src="<?= e($url) ?>" autoplay muted loop playsinline></video>
            <?php else: ?>
                <img class="contact-media" src="<?= e($url) ?>" alt="">
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <aside class="contact-feed">
        <!-- Elfsight Instagram Feed | Prjcts Instagram Feed -->
        <script src="https://elfsightcdn.com/platform.js" async></script>
        <div class="elfsight-app-53a32a3c-33ba-451d-9953-7f817d7146b0" data-elfsight-app-lazy></div>
    </aside>
</div>
