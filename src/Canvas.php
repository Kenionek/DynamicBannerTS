<?php
/**
 * A GD image plus the drawing helpers the layouts need: colours, shadowed
 * text, translucent rounded panels, logos and the background photo.
 */

declare(strict_types=1);

final class Canvas
{
    public GdImage $img;
    /** Rectangles [x1, y1, x2, y2] that theme decorations must stay out of. */
    public array $keepClear = [];
    /** Where a theme may put its moon. */
    public array $skySpot = [0, 0];

    public function __construct(public int $width, public int $height, private string $headFont, private string $bodyFont)
    {
        $this->img = imagecreatetruecolor($width, $height);
        imagealphablending($this->img, true);
    }

    public function color(string $hex, int $alpha = 0): int
    {
        $hex = ltrim($hex, '#');
        return imagecolorallocatealpha($this->img, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha);
    }

    /** Photo scaled to cover the canvas (centred), or a dark fill if it can't be read. */
    public function background(string $path): void
    {
        $src = is_file($path) ? @imagecreatefromstring((string) file_get_contents($path)) : false;
        if (!$src) {
            imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $this->color('15191E'));
            return;
        }
        $sw = imagesx($src); $sh = imagesy($src);
        $scale = max($this->width / $sw, $this->height / $sh);
        $cw = (int) round($this->width / $scale); $ch = (int) round($this->height / $scale);
        imagecopyresampled($this->img, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $this->width, $this->height, $cw, $ch);
        imagedestroy($src);
    }

    /** Left-to-right darkening so white text stays readable on any photo. */
    public function shade(int $leftAlpha = 14, int $rightAlpha = 70): void
    {
        for ($x = 0; $x < $this->width; $x += 4) {
            $a = (int) round($leftAlpha + ($rightAlpha - $leftAlpha) * $x / $this->width);
            imagefilledrectangle($this->img, $x, 0, $x + 3, $this->height, $this->color('0A0E12', $a));
        }
    }

    public function wash(string $hex, int $alpha): void
    {
        imagefilledrectangle($this->img, 0, 0, $this->width, $this->height, $this->color($hex, $alpha));
    }

    public function textWidth(string $text, float $size, bool $head = false): int
    {
        $box = imagettfbbox($size, 0, $head ? $this->headFont : $this->bodyFont, $text);
        return abs($box[2] - $box[0]);
    }

    /** Draws text with a soft shadow; $align is relative to $x ('left', 'center', 'right'). Returns its width. */
    public function text(string $text, float $size, int $x, int $y, string $hex, bool $head = false, string $align = 'left'): int
    {
        $font = $head ? $this->headFont : $this->bodyFont;
        $w = $this->textWidth($text, $size, $head);
        if ($align === 'right') $x -= $w; elseif ($align === 'center') $x -= intdiv($w, 2);
        imagettftext($this->img, $size, 0, $x + 2, $y + 2, $this->color('000000', 75), $font, $text);
        imagettftext($this->img, $size, 0, $x, $y, $this->color($hex), $font, $text);
        return $w;
    }

    /** Translucent rounded rectangle (drawn on its own layer so the corners don't stack darker). */
    public function panel(int $x1, int $y1, int $x2, int $y2, int $radius, string $hex = '000000', int $alpha = 60): void
    {
        $w = $x2 - $x1 + 1; $h = $y2 - $y1 + 1;
        $layer = imagecreatetruecolor($w, $h);
        imagealphablending($layer, false); imagesavealpha($layer, true);
        imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));
        $hex = ltrim($hex, '#');
        $c = imagecolorallocatealpha($layer, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha);
        imagefilledrectangle($layer, $radius, 0, $w - 1 - $radius, $h - 1, $c);
        imagefilledrectangle($layer, 0, $radius, $w - 1, $h - 1 - $radius, $c);
        foreach ([[$radius, $radius], [$w - 1 - $radius, $radius], [$radius, $h - 1 - $radius], [$w - 1 - $radius, $h - 1 - $radius]] as [$cx, $cy]) {
            imagefilledellipse($layer, $cx, $cy, $radius * 2, $radius * 2, $c);
        }
        imagecopy($this->img, $layer, $x1, $y1, 0, 0, $w, $h);
        imagedestroy($layer);
    }

    /** PNG logo scaled to $height, top-left at ($x, $y). Returns the drawn width (0 if missing). */
    public function logo(string $path, int $x, int $y, int $height): int
    {
        $src = is_file($path) ? @imagecreatefrompng($path) : false;
        if (!$src) return 0;
        $w = (int) round(imagesx($src) * $height / imagesy($src));
        $scaled = imagecreatetruecolor($w, $height);
        imagealphablending($scaled, false); imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));
        imagecopyresampled($scaled, $src, 0, 0, 0, 0, $w, $height, imagesx($src), imagesy($src));
        imagecopy($this->img, $scaled, $x, $y, 0, 0, $w, $height);
        imagedestroy($src); imagedestroy($scaled);
        return $w;
    }

    /** A random point that isn't inside a keep-clear rectangle (off-canvas if none is found). */
    public function freeSpot(int $bottomMargin = 60): array
    {
        for ($i = 0; $i < 40; $i++) {
            $x = mt_rand(0, $this->width); $y = mt_rand(0, $this->height - $bottomMargin);
            $blocked = false;
            foreach ($this->keepClear as [$x1, $y1, $x2, $y2]) {
                if ($x >= $x1 && $x <= $x2 && $y >= $y1 && $y <= $y2) { $blocked = true; break; }
            }
            if (!$blocked) return [$x, $y];
        }
        return [-50, -50];
    }
}
