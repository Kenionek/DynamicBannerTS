<?php
/**
 * Shared by banner.php and banner-wide.php: loads the config, gets the
 * (cached) server stats, renders the PNG once per minute and sends it.
 * Only ?theme=<name> is read from the request, and only names from
 * Themes::NAMES are accepted. Errors are logged, never shown.
 */

declare(strict_types=1);

require_once __DIR__ . '/TsQuery.php';
require_once __DIR__ . '/ServerStats.php';
require_once __DIR__ . '/Canvas.php';
require_once __DIR__ . '/Themes.php';
require_once __DIR__ . '/Layouts.php';

function dbts_output(string $layout): void
{
    ini_set('display_errors', '0');
    $root = dirname(__DIR__);
    $cfg = (is_file("$root/config.php") ? require "$root/config.php" : require "$root/config.example.php") + ['dir' => $root];
    $secret = is_file("$root/secret.php") ? require "$root/secret.php" : null;
    $cacheDir = rtrim((string) ($cfg['cache_dir'] ?? '') ?: sys_get_temp_dir(), '/');
    $tz = (string) ($cfg['timezone'] ?? 'UTC');

    $now = new DateTimeImmutable('now', new DateTimeZone($tz));
    $stats = is_array($secret) ? ServerStats::get($secret, $cacheDir, $tz) : ['online' => false];
    $want = $_GET['theme'] ?? null;
    $theme = is_string($want) && in_array($want, Themes::NAMES, true) ? $want
        : (!empty($cfg['seasonal_themes']) ? Themes::pick($now) : 'default');
    if (!in_array($layout, Layouts::NAMES, true)) $layout = 'classic';

    $file = $cacheDir . '/dbts-' . $layout . '-' . md5($now->format('YmdHi') . $theme . json_encode(array_diff_key($stats, ['at' => 0]))) . '.png';
    if (!is_file($file)) {
        try {
            $img = Layouts::draw($layout, $stats, $now, $theme, $cfg);
            $tmp = $file . "." . getmypid();
            imagepng($img, $tmp, 6);
            imagedestroy($img);
            rename($tmp, $file);
            foreach (glob($cacheDir . '/dbts-*.png') ?: [] as $old) {
                if ($old !== $file && filemtime($old) < time() - 300) @unlink($old);
            }
        } catch (Throwable $e) {
            error_log('[DynamicBannerTS] render failed: ' . $e->getMessage());
            http_response_code(503);
            exit;
        }
    }
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=30');
    header('X-Content-Type-Options: nosniff');
    readfile($file);
}
