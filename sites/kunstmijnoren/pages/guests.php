<?php
/** @var array $cfg
 *  @var array $config */
declare(strict_types=1);

$lang = $GLOBALS['_prjcts_lang'] ?? 'nl';
$root = realpath(__DIR__ . '/../content') ?: '';

$titles = [
    'nl' => 'Gastenboek',
    'en' => 'Guests',
    'fr' => "Livre d'or",
];
$page_title = $titles[$lang] ?? 'Gastenboek';

// Optionele introtekst — content/guests/<lang>.md (mag ontbreken)
$body_file = $root . '/guests/' . $lang . '.md';
if (!is_file($body_file)) $body_file = $root . '/guests/nl.md';
$body = is_file($body_file) ? file_get_contents($body_file) : '';
?>

<h1><?= e($page_title) ?></h1>
<?php if ($body !== ''): ?>
    <div class="guests-intro"><?= $body ?></div>
<?php endif; ?>

<!-- Elfsight Comments | Kunstmijnoren -->
<script src="https://elfsightcdn.com/platform.js" async></script>
<div class="elfsight-app-54f7a1ec-ae06-494e-a538-5aef538098e0" data-elfsight-app-lazy></div>
