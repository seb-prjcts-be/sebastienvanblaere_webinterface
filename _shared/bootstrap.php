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
 *
 * - 'fontsize' is puur client-side (CSS): zet cookie en redirect (302) naar schone URL.
 * - 'lang' ('nl', 'en', 'fr'):
 *   - Default taal ('nl'): zet cookie + sessie en redirect (302) naar de canonieke URL zonder ?lang=nl.
 *   - Niet-default talen ('en', 'fr'): zet cookie + sessie én render direct (HTTP 200 zonder redirect)
 *     zodat zoekmachines (die geen cookies bewaren) alle Engelse en Franse pagina's via hreflang kunnen indexeren.
 */
$allowed_langs = ['nl', 'en', 'fr'];
$allowed_sizes = ['klein', 'medium', 'groot'];

$redirect_clean = false;

if (isset($_GET['fontsize']) && in_array($_GET['fontsize'], $allowed_sizes, true)) {
    setcookie('prjcts_fontsize', $_GET['fontsize'], [
        'expires'  => time() + 365 * 24 * 60 * 60, // 1 jaar
        'path'     => '/',
        'samesite' => 'Lax',
        'secure'   => $_is_https,
        // httponly bewust UIT: header.php inline-script en de fontsize-tool lezen document.cookie.
        'httponly' => false,
    ]);
    $redirect_clean = true;
}

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs, true)) {
    $val = $_GET['lang'];
    $_SESSION['lang'] = $val;
    setcookie('prjcts_lang', $val, [
        'expires'  => time() + 365 * 24 * 60 * 60, // 1 jaar
        'path'     => '/',
        'samesite' => 'Lax',
        'secure'   => $_is_https,
        'httponly' => false,
    ]);
    if ($val === 'nl') {
        $redirect_clean = true;
    }
}

if ($redirect_clean) {
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $qs  = $_GET;
    unset($qs['fontsize']);
    if (($qs['lang'] ?? '') === 'nl') {
        unset($qs['lang']);
    }
    $clean = $uri . (!empty($qs) ? '?' . http_build_query($qs) : '');
    header('Location: ' . $clean, true, 302);
    exit;
}

// Lang ophalen voor server-rendering: expliciete GET > sessie > cookie > default ('nl')
$lang = (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs, true))
    ? $_GET['lang']
    : ($_SESSION['lang'] ?? $_COOKIE['prjcts_lang'] ?? 'nl');
if (!in_array($lang, $allowed_langs, true)) $lang = 'nl';
if (!isset($_SESSION['lang']) || $_SESSION['lang'] !== $lang) $_SESSION['lang'] = $lang;

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
