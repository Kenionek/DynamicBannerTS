<?php
/**
 * Seasonal themes. Four are included; add your own by adding a case to
 * pick(), style() and decorate() (and its name to NAMES).
 *
 *   default    the rest of the year
 *   halloween  October 25 - November 1   moon and bats
 *   christmas  December 1 - 30           snow and fairy lights
 *   newyear    December 31 - January 2   confetti, next year's number
 */

declare(strict_types=1);

final class Themes
{
    public const NAMES = ['default', 'halloween', 'christmas', 'newyear'];

    public static function pick(DateTimeImmutable $now): string
    {
        $md = (int) $now->format('md');
        if ($md >= 1231 || $md <= 102) return 'newyear';
        if ($md >= 1201) return 'christmas';
        if ($md >= 1025 && $md <= 1101) return 'halloween';
        return 'default';
    }

    /** Colours and the extra header line. $accent is the configured colour used by 'default'. */
    public static function style(string $name, DateTimeImmutable $now, string $accent): array
    {
        $year = (int) $now->format('Y');
        return match ($name) {
            'halloween' => ['accent' => 'FF9800', 'muted' => 'FFE0B2', 'wash' => ['2B0D3F', 82], 'line' => 'Happy Halloween'],
            'christmas' => ['accent' => 'EF5350', 'muted' => 'E8F5E9', 'wash' => ['0B2545', 92], 'line' => 'Merry Christmas'],
            'newyear'   => ['accent' => 'FFD740', 'muted' => 'FFF8E1', 'wash' => ['0D0B26', 86],
                'line' => 'Happy New Year ' . ((int) $now->format('n') === 12 ? $year + 1 : $year)],
            default     => ['accent' => $accent, 'muted' => 'DDE6EE', 'wash' => null, 'line' => null],
        };
    }

    /** Decorations, seeded per day so a cached banner doesn't change every minute. */
    public static function decorate(Canvas $c, string $name, DateTimeImmutable $now): void
    {
        mt_srand(crc32($name . $now->format('Ymd')));
        switch ($name) {
            case 'halloween':
                [$mx, $my] = $c->skySpot;
                for ($g = 3; $g >= 1; $g--) imagefilledellipse($c->img, $mx, $my, 88 + $g * 22, 88 + $g * 22, $c->color('FFB74D', 100 + $g * 6));
                imagefilledellipse($c->img, $mx, $my, 88, 88, $c->color('FFE9C4', 16));
                foreach ([[-70, -30, 20], [62, 30, 14], [96, -38, 11], [-115, 26, 10]] as [$dx, $dy, $s]) self::bat($c, $mx + $dx, $my + $dy, $s);
                self::sprinkle($c, 35, ['FF9800', 'CE93D8'], 1, 3);
                break;
            case 'christmas':
                self::sprinkle($c, 90, ['FFFFFF'], 1, 4);
                self::lights($c, ['EF5350', 'FFD740', '66BB6A', '42A5F5']);
                break;
            case 'newyear':
                $colors = ['FFD740', 'FFFFFF', 'F48FB1', '80DEEA'];
                for ($i = 0; $i < 40; $i++) {
                    [$x, $y] = $c->freeSpot();
                    $a = mt_rand(0, 314) / 100; $pts = [];
                    foreach ([[-3, -6], [3, -6], [3, 6], [-3, 6]] as [$px, $py]) {
                        $pts[] = (int) round($x + $px * cos($a) - $py * sin($a));
                        $pts[] = (int) round($y + $px * sin($a) + $py * cos($a));
                    }
                    imagefilledpolygon($c->img, $pts, $c->color($colors[$i % 4], mt_rand(15, 50)));
                }
                break;
        }
    }

    private static function sprinkle(Canvas $c, int $count, array $colors, int $minR, int $maxR): void
    {
        for ($i = 0; $i < $count; $i++) {
            [$x, $y] = $c->freeSpot();
            $r = mt_rand($minR, $maxR);
            imagefilledellipse($c->img, $x, $y, $r * 2, $r * 2, $c->color($colors[$i % count($colors)], mt_rand(35, 90)));
        }
    }

    private static function bat(Canvas $c, int $x, int $y, int $s): void
    {
        $shape = [[-1, 0], [-.6, -.3], [-.48, -.06], [-.3, -.22], [-.14, 0], [-.06, -.18], [0, -.08], [.06, -.18], [.14, 0],
            [.3, -.22], [.48, -.06], [.6, -.3], [1, 0], [.6, .08], [.36, .26], [.14, .14], [0, .3], [-.14, .14], [-.36, .26], [-.6, .08]];
        $pts = [];
        foreach ($shape as [$px, $py]) { $pts[] = (int) round($x + $px * $s); $pts[] = (int) round($y + $py * $s); }
        imagefilledpolygon($c->img, $pts, $c->color('140A18', 12));
    }

    private static function lights(Canvas $c, array $colors): void
    {
        $prev = null;
        for ($x = 0, $i = 0; $x <= $c->width; $x += 6, $i++) {
            $y = (int) round(6 + 7 * abs(sin($x / $c->width * M_PI * 6)));
            if ($prev) imageline($c->img, $prev[0], $prev[1], $x, $y, $c->color('202020', 30));
            $prev = [$x, $y];
            if ($i % 6 === 3) {
                $col = $colors[intdiv($i, 6) % count($colors)];
                imagefilledellipse($c->img, $x, $y + 6, 18, 18, $c->color($col, 105));
                imagefilledellipse($c->img, $x, $y + 6, 8, 10, $c->color($col));
            }
        }
    }
}
