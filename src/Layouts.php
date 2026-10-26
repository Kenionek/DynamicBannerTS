<?php
/**
 * The two banner layouts:
 *   classic  900 x 450   TeamSpeak host banner
 *   wide    1200 x 300   website header / sidebar strip
 */

declare(strict_types=1);

final class Layouts
{
    public const NAMES = ['classic', 'wide'];

    public static function draw(string $layout, array $s, DateTimeImmutable $now, string $themeName, array $cfg): GdImage
    {
        $theme = Themes::style($themeName, $now, (string) $cfg['accent']);
        $fonts = [$cfg['dir'] . '/assets/fonts/Orbitron.ttf', $cfg['dir'] . '/assets/fonts/RopaSans-Regular.ttf'];
        return $layout === 'wide'
            ? self::wide(new Canvas(1200, 300, ...$fonts), $s, $now, $themeName, $theme, $cfg)
            : self::classic(new Canvas(900, 450, ...$fonts), $s, $now, $themeName, $theme, $cfg);
    }

    private static function base(Canvas $c, DateTimeImmutable $now, string $themeName, array $theme, array $cfg): void
    {
        $c->background((string) $cfg['background']);
        $c->shade();
        if ($theme['wash']) $c->wash(...$theme['wash']);
        Themes::decorate($c, $themeName, $now);
    }

    /** Small maker credit in the top-right corner. Please keep it (see LICENSE). */
    private static function credit(Canvas $c): void
    {
        $text = 'DynamicBannerTS by Kenionek';
        imagettftext($c->img, 9, 0, $c->width - 12 - $c->textWidth($text, 9), 15, $c->color('FFFFFF', 55),
            dirname(__DIR__) . '/assets/fonts/RopaSans-Regular.ttf', $text);
    }

    /** "Ping 23 ms", "Loss 0.0%", ... for the info items switched on in the config. */
    private static function facts(array $s, array $cfg): array
    {
        $on = $cfg['show'] ?? [];
        $out = [];
        if (!empty($on['ping']) && !empty($s['ping'])) $out[] = 'Ping ' . (int) round($s['ping']) . ' ms';
        if (!empty($on['loss']) && isset($s['loss'])) $out[] = 'Loss ' . number_format((float) $s['loss'], 1) . '%';
        if (!empty($on['peak']) && isset($s['peak'])) $out[] = 'Peak today ' . (int) $s['peak'];
        if (!empty($on['visits']) && !empty($s['visits'])) $out[] = number_format((int) $s['visits']) . ' visits';
        return $out;
    }

    private static function uptime(int $sec): string
    {
        $d = intdiv($sec, 86400); $h = intdiv($sec % 86400, 3600); $m = intdiv($sec % 3600, 60);
        return $d ? "{$d}d {$h}h" : ($h ? "{$h}h {$m}m" : "{$m}m");
    }

    /** User count, fill bar and label inside the box $x1,$y1-$x2,$y2. */
    private static function usersCard(Canvas $c, array $s, array $theme, int $x1, int $y1, int $x2, int $y2, int $numSize): void
    {
        $c->panel($x1, $y1, $x2, $y2, 18, '000000', 58);
        $mid = intdiv($x1 + $x2, 2);
        if (empty($s['online'])) {
            $c->text('OFFLINE', 26, $mid, $y1 + intdiv($y2 - $y1, 2), 'EF9A9A', true, 'center');
            $c->text('back soon', 17, $mid, $y1 + intdiv($y2 - $y1, 2) + 34, 'DDDDDD', false, 'center');
            return;
        }
        $users = (string) (int) $s['users'];
        $slots = (int) ($s['slots'] ?? 0);
        $nw = $c->textWidth($users, $numSize, true);
        $sw = $slots ? $c->textWidth("/ $slots", 22) + 8 : 0;
        $start = $mid - intdiv($nw + $sw, 2);
        $base = $y1 + (int) (($y2 - $y1) * 0.56);
        $c->text($users, $numSize, $start, $base, 'FFFFFF', true);
        if ($slots) $c->text("/ $slots", 22, $start + $nw + 8, $base, $theme['muted']);
        // Fill bar: how full the server is.
        $bx1 = $x1 + 26; $bx2 = $x2 - 26; $by = $base + 20;
        $c->panel($bx1, $by, $bx2, $by + 8, 4, 'FFFFFF', 100);
        if ($slots > 0 && $s['users'] > 0) {
            $fill = $bx1 + max(8, (int) (($bx2 - $bx1) * min(1, $s['users'] / $slots)));
            $c->panel($bx1, $by, $fill, $by + 8, 4, $theme['accent'], 0);
        }
        $c->text((int) $s['users'] === 1 ? 'user online' : 'users online', 17, $mid, $by + 38, 'E6E6E6', false, 'center');
    }

    private static function classic(Canvas $c, array $s, DateTimeImmutable $now, string $themeName, array $theme, array $cfg): GdImage
    {
        $c->keepClear = [[20, 15, 540, 150], [20, 160, 540, 390], [560, 140, 880, 360], [640, 20, 880, 80]];
        $c->skySpot = [610, 78];
        self::base($c, $now, $themeName, $theme, $cfg);
        $online = !empty($s['online']);

        // Header: logo, title, address, theme line.
        $lw = $c->logo((string) $cfg['logo'], 26, 20, 124);
        $tx = 26 + ($lw ? $lw + 18 : 0);
        $c->text((string) $cfg['title'], 28, $tx, 70, 'FFFFFF', true);
        $c->text((string) $cfg['address'], 18, $tx + 2, 102, $theme['muted']);
        if ($theme['line']) $c->text($theme['line'], 18, $tx + 2, 132, $theme['accent']);

        // Status pill.
        $label = $online ? 'ONLINE' : 'OFFLINE';
        $pw = $c->textWidth($label, 14) + 48;
        $c->panel(872 - $pw, 32, 872, 66, 17, '000000', 50);
        imagefilledellipse($c->img, 872 - $pw + 21, 49, 13, 13, $c->color($online ? '66BB6A' : 'EF5350'));
        $c->text($label, 14, 872 - $pw + 34, 56, 'FFFFFF');

        // Clock, date and time zone.
        $c->text($now->format('H:i'), 74, 30, 262, 'FFFFFF', true);
        $dw = $c->text($now->format('l j F Y'), 22, 34, 304, 'E6E6E6');
        $c->text((string) $cfg['zone_label'] . ' · ' . $now->format('T'), 16, 34 + $dw + 14, 303, $theme['muted']);

        // Info chips.
        if ($online) {
            $x = 32;
            foreach (self::facts($s, $cfg) as $fact) {
                $w = $c->textWidth($fact, 14) + 24;
                if ($x + $w > 540) break;
                $c->panel($x, 326, $x + $w, 356, 15, '000000', 62);
                imagefilledellipse($c->img, $x + 12, 341, 6, 6, $c->color($theme['accent']));
                $c->text($fact, 14, $x + 18, 347, 'E6E6E6');
                $x += $w + 8;
            }
        }

        self::usersCard($c, $s, $theme, 560, 150, 872, 350, 70);

        // Footer.
        imagefilledrectangle($c->img, 0, 398, 900, 450, $c->color('000000', 45));
        imagefilledrectangle($c->img, 0, 398, 900, 400, $c->color($theme['accent']));
        $c->text((string) $cfg['website'], 18, 26, 431, 'FFFFFF');
        if ($online) {
            $c->text('Uptime ' . self::uptime((int) $s['uptime']) . '  ·  ' . (int) $s['channels'] . ' channels', 16, 450, 430, $theme['muted'], false, 'center');
        }
        $c->text((string) $cfg['address'], 18, 874, 431, 'FFFFFF', false, 'right');
        self::credit($c);
        return $c->img;
    }

    private static function wide(Canvas $c, array $s, DateTimeImmutable $now, string $themeName, array $theme, array $cfg): GdImage
    {
        $c->keepClear = [[20, 15, 820, 140], [20, 140, 700, 250], [840, 15, 1185, 250]];
        $c->skySpot = [770, 190];
        self::base($c, $now, $themeName, $theme, $cfg);
        $online = !empty($s['online']);

        $lw = $c->logo((string) $cfg['logo'], 24, 24, 200);
        $tx = 24 + ($lw ? $lw + 24 : 0);
        $c->text((string) $cfg['title'], 26, $tx, 62, 'FFFFFF', true);
        $c->text((string) $cfg['address'], 17, $tx + 2, 92, $theme['muted']);
        if ($theme['line']) $c->text($theme['line'], 17, $tx + 2, 120, $theme['accent']);
        $cw = $c->text($now->format('H:i'), 50, $tx, 204, 'FFFFFF', true);
        $c->text($now->format('l j F'), 19, $tx + $cw + 18, 182, 'E6E6E6');
        $c->text((string) $cfg['zone_label'] . ' · ' . $now->format('T'), 15, $tx + $cw + 18, 206, $theme['muted']);
        if ($online) {
            $facts = self::facts($s, $cfg);
            if ($facts) $c->text(implode('   ·   ', $facts), 15, $tx + 2, 240, 'E6E6E6');
        }

        self::usersCard($c, $s, $theme, 850, 22, 1176, 246, 62);

        imagefilledrectangle($c->img, 0, 254, 1200, 300, $c->color('000000', 45));
        imagefilledrectangle($c->img, 0, 254, 1200, 256, $c->color($theme['accent']));
        $status = $online ? 'Online  ·  uptime ' . self::uptime((int) $s['uptime']) . '  ·  ' . (int) $s['channels'] . ' channels' : 'Server offline';
        imagefilledellipse($c->img, 32, 277, 12, 12, $c->color($online ? '66BB6A' : 'EF5350'));
        $c->text($status, 16, 46, 283, 'FFFFFF');
        $c->text((string) $cfg['website'], 17, 1176, 284, 'FFFFFF', false, 'right');
        self::credit($c);
        return $c->img;
    }
}
