<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root = realpath(__DIR__ . '/../content') ?: '';
$home = read_home_body($root, $lang);
?>

<?php if ($home !== ''): ?>
    <div class="home-body"><?= $home ?></div>
<?php endif; ?>
