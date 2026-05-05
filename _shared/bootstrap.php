<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/content.php';

// Production-veilige error reporting: lokaal alle errors tonen, productie verbergen
// (logged via Apache error_log; niets naar de browser).
$_is_local = is_localhost_path_mode();
ini_set('display_errors', $_is_local ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Productie HTTPS-detectie (Apache, evt. achter proxy via X-Forwarded-Proto)
$_is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'samesite' => 'Lax',
        'secure'   => $_is_https,
        'httponly' => true,
    ]);
    session_start();
}

/**
 * User-preferences die we cross-site willen syncen via ?param=X.
 * Server slaat op in cookie + (waar relevant) sessie, redirect naar schone URL.
 *
 * Server gebruikt 'lang' om content uit nl.md / en.md / fr.md te lezen.
 * 'fontsize' is puur client-side (CSS) — we zetten alleen het cookie zodat
 * de inline init-script in header.php het kan oppikken.
 */
$pref_specs = [
    'lang'     => ['allowed' => ['nl', 'en', 'fr'],          'cookie' => 'prjcts_lang',     'session' => true],
    'fontsize' => ['allowed' => ['klein', 'medium', 'groot'], 'cookie' => 'prjcts_fontsize', 'session' => false],
];

$pref_changed = false;
foreach ($pref_specs as $param => $spec) {
    if (isset($_GET[$param]) && in_array($_GET[$param], $spec['allowed'], true)) {
        $val = $_GET[$param];
        if ($spec['session']) {
            $_SESSION[$param] = $val;
        }
        setcookie($spec['cookie'], $val, [
            'expires'  => time() + 365 * 24 * 60 * 60, // 1 jaar
            'path'     => '/',
            'samesite' => 'Lax',
            'secure'   => $_is_https,
            // httponly bewust UIT: header.php inline-script en de fontsize-tool
            // moeten deze cookies via document.cookie kunnen lezen.
            'httponly' => false,
        ]);
        $pref_changed = true;
    }
}

if ($pref_changed) {
    // Redirect naar dezelfde URL zonder de pref-params, zodat URLs schoon blijven
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $qs  = $_GET;
    foreach (array_keys($pref_specs) as $p) unset($qs[$p]);
    $clean = $uri . (!empty($qs) ? '?' . http_build_query($qs) : '');
    header('Location: ' . $clean, true, 302);
    exit;
}

// Lang ophalen voor server-rendering: sessie > cookie > default
$allowed_langs = $pref_specs['lang']['allowed'];
$lang = $_SESSION['lang']
     ?? $_COOKIE['prjcts_lang']
     ?? 'nl';
if (!in_array($lang, $allowed_langs, true)) $lang = 'nl';
if (!isset($_SESSION['lang'])) $_SESSION['lang'] = $lang;

$GLOBALS['_prjcts_lang'] = $lang;

$current = detect_satellite($config['satellites']);

if ($current === null) {
    http_response_code(500);
    if ($_is_local) {
        // Diagnose-info enkel lokaal — productie geeft generieke fout (geen path-disclosure).
        echo 'Onbekende satelliet: host=' . e($_SERVER['HTTP_HOST'] ?? '') . ' dir=' . e(basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')));
    } else {
        echo 'Configuration error.';
    }
    exit;
}

$GLOBALS['_prjcts_mode'] = is_localhost_path_mode() ? 'path' : 'host';
