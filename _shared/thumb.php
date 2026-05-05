<?php
/**
 * On-demand thumbnail-generator met file-cache.
 *
 * Input:  ?p=<rel-path-vanaf-DOCUMENT_ROOT>&w=<int>[&h=<int>]
 * Output: image/jpeg (cached)
 * Cache:  <dirname>/.thumb/<basename>_<w>x<h>.jpg  (auto-aangemaakt naast bron-image)
 *
 * Beveiliging:
 *   - p moet matchen [a-zA-Z0-9._\-/] (geen .. of weird chars)
 *   - realpath() + str_starts_with(DOCUMENT_ROOT) — geen escape uit webroot
 *   - whitelist op image-extensies (jpg/jpeg/png/gif/webp)
 *   - w/h geclamped naar [50, 2000]
 *
 * Cache-strategie: regenereert als bron-mtime > thumb-mtime, anders serveert cache.
 */
declare(strict_types=1);

const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const MIN_DIM = 50;
const MAX_DIM = 2000;
const JPEG_QUALITY = 82;

function fail(int $code, string $msg = ''): void
{
    http_response_code($code);
    header('Content-Type: text/plain');
    echo $msg ?: 'thumb error';
    exit;
}

// === input ===
$p = (string) ($_GET['p'] ?? '');
$w = max(MIN_DIM, min(MAX_DIM, (int) ($_GET['w'] ?? 400)));
$h = max(MIN_DIM, min(MAX_DIM, (int) ($_GET['h'] ?? $w)));

// Filename-veilige chars (incl. parens, spatie, apostrof, plus voor real-world filenames).
// Echte traversal-defense komt van realpath() + sandbox-check hieronder; de regex blokt
// enkel evident weird chars (null-bytes, control chars, URL-specials zoals & = ? #).
if ($p === ''
 || !preg_match('#^[a-zA-Z0-9._\-/() \'+,!~]+$#', $p)
 || str_contains($p, '..')
 || str_contains($p, '\\')) {
    fail(400, 'invalid path');
}

// === resolve + sandbox check ===
// Resolveer tegen HUB-ROOT (= htdocs/sebastienvanblaere.be/), niet DOCUMENT_ROOT.
// In Setup B (per-satelliet DocumentRoot) verschilt DOCUMENT_ROOT per host —
// hub_root via __DIR__ is constant.
$hub_root = realpath(__DIR__ . '/..');
$abs_path = realpath($hub_root . '/' . $p);
if ($hub_root === false || $abs_path === false
 || strncmp($abs_path, $hub_root . DIRECTORY_SEPARATOR, strlen($hub_root) + 1) !== 0
 || !is_file($abs_path)) {
    fail(404, 'not found');
}

$ext = strtolower(pathinfo($abs_path, PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTS, true)) {
    fail(415, 'unsupported');
}

// === cache-pad ===
$dir       = dirname($abs_path);
$basename  = pathinfo($abs_path, PATHINFO_FILENAME);
$thumb_dir = $dir . DIRECTORY_SEPARATOR . '.thumb';
$thumb_path = $thumb_dir . DIRECTORY_SEPARATOR . $basename . '_' . $w . 'x' . $h . '.jpg';

// Serveer cache als geldig
if (is_file($thumb_path) && filemtime($abs_path) <= filemtime($thumb_path)) {
    serve_thumb($thumb_path);
    exit;
}

// === GD generate ===
if (!function_exists('imagecreatetruecolor')) {
    fail(500, 'GD missing');
}

$src = match ($ext) {
    'jpg', 'jpeg' => @imagecreatefromjpeg($abs_path),
    'png'         => @imagecreatefrompng($abs_path),
    'gif'         => @imagecreatefromgif($abs_path),
    'webp'        => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs_path) : false,
    default       => false,
};
if (!$src) {
    fail(500, 'decode failed');
}

$src_w = imagesx($src);
$src_h = imagesy($src);
$src_ratio = $src_w / $src_h;
$dst_ratio = $w / $h;

// Cover-effect: crop the bron zodat het volledig de target dekt (zoals object-fit: cover)
if ($src_ratio > $dst_ratio) {
    $crop_h = $src_h;
    $crop_w = (int) round($src_h * $dst_ratio);
    $crop_x = (int) round(($src_w - $crop_w) / 2);
    $crop_y = 0;
} else {
    $crop_w = $src_w;
    $crop_h = (int) round($src_w / $dst_ratio);
    $crop_x = 0;
    $crop_y = (int) round(($src_h - $crop_h) / 2);
}

$dst = imagecreatetruecolor($w, $h);
// Zwarte achtergrond (matcht site-bg niet, maar onzichtbaar door cover-crop)
imagecopyresampled($dst, $src, 0, 0, $crop_x, $crop_y, $w, $h, $crop_w, $crop_h);

if (!is_dir($thumb_dir)) {
    @mkdir($thumb_dir, 0755, true);
}
$ok = @imagejpeg($dst, $thumb_path, JPEG_QUALITY);
imagedestroy($src);
imagedestroy($dst);

if (!$ok) {
    fail(500, 'write failed');
}

serve_thumb($thumb_path);

function serve_thumb(string $path): void
{
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
}
