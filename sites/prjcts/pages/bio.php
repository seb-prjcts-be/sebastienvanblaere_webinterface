<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root = realpath(__DIR__ . '/../content') ?: '';

$titles = [
    'nl' => 'Bio',
    'en' => 'Bio',
    'fr' => 'Bio',
];
$page_title = $titles[$lang] ?? 'Bio';

$body = read_body($root . '/bio', $lang);
?>

<article class="bio">
    <h1><?= e($page_title) ?></h1>
    <?php if ($body !== ''): ?>
        <div class="bio-body"><?= render_md($body) ?></div>
    <?php else: ?>
        <p><em>Bio nog niet ingevuld &mdash; drop tekst in <code>content/bio/<?= e($lang) ?>.md</code>.</em></p>
    <?php endif; ?>
</article>
