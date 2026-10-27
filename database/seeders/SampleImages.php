<?php

namespace Database\Seeders;

/**
 * Vẽ ảnh minh họa đơn giản bằng GD cho dữ liệu mẫu (không cần file ảnh có sẵn).
 */
final class SampleImages
{
    private const THEMES = [
        'mountain' => [[191, 219, 254], [59, 130, 246]],
        'receipt' => [[219, 234, 254], [96, 165, 250]],
        'basket' => [[147, 197, 253], [37, 99, 235]],
        'books' => [[239, 246, 255], [147, 197, 253]],
    ];

    /**
     * Trả về đường dẫn file PNG tạm, hoặc null nếu máy chưa bật GD.
     */
    public static function make(string $theme): ?string
    {
        if (! function_exists('imagecreatetruecolor') || ! isset(self::THEMES[$theme])) {
            return null;
        }

        [$from, $to] = self::THEMES[$theme];
        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);

        // Nền chuyển màu chéo.
        for ($y = 0; $y < $height; $y++) {
            $t = $y / $height;
            $color = imagecolorallocate(
                $image,
                (int) ($from[0] + ($to[0] - $from[0]) * $t),
                (int) ($from[1] + ($to[1] - $from[1]) * $t),
                (int) ($from[2] + ($to[2] - $from[2]) * $t),
            );
            imageline($image, 0, $y, $width, $y, $color);
        }

        $white = imagecolorallocatealpha($image, 255, 255, 255, 30);
        $soft = imagecolorallocatealpha($image, 255, 255, 255, 90);

        match ($theme) {
            'mountain' => self::mountain($image, $white, $soft),
            'receipt' => self::receipt($image, $white, $soft),
            'basket' => self::basket($image, $white, $soft),
            'books' => self::books($image, $white, $soft),
        };

        $path = tempnam(sys_get_temp_dir(), 'seed').'.png';
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private static function mountain($image, int $white, int $soft): void
    {
        imagefilledellipse($image, 620, 150, 140, 140, $white);
        imagefilledpolygon($image, [0, 600, 260, 260, 520, 600], $soft);
        imagefilledpolygon($image, [220, 600, 520, 200, 800, 600], $white);
        imagefilledpolygon($image, [460, 290, 520, 200, 580, 290], imagecolorallocate($image, 255, 255, 255));
    }

    private static function receipt($image, int $white, int $soft): void
    {
        imagefilledrectangle($image, 250, 80, 550, 520, $white);
        for ($i = 0; $i < 7; $i++) {
            $y = 140 + $i * 50;
            imagefilledrectangle($image, 290, $y, 290 + ($i % 2 ? 160 : 220), $y + 16, $soft);
        }
        imagefilledellipse($image, 600, 470, 120, 120, $soft);
    }

    private static function basket($image, int $white, int $soft): void
    {
        imagefilledellipse($image, 330, 250, 150, 150, $soft);
        imagefilledellipse($image, 470, 240, 130, 130, $white);
        imagefilledellipse($image, 400, 200, 110, 110, $soft);
        imagefilledpolygon($image, [180, 300, 620, 300, 560, 520, 240, 520], $white);
        imagesetthickness($image, 10);
        imagearc($image, 400, 300, 380, 300, 180, 360, $white);
    }

    private static function books($image, int $white, int $soft): void
    {
        imagefilledrectangle($image, 200, 380, 600, 450, $white);
        imagefilledrectangle($image, 230, 300, 570, 370, $soft);
        imagefilledrectangle($image, 260, 220, 540, 290, $white);
        imagefilledellipse($image, 620, 140, 90, 90, $soft);
        imagefilledellipse($image, 170, 160, 50, 50, $white);
    }
}
