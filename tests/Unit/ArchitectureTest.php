<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Giữ đúng quy tắc phụ thuộc của Clean Architecture: Presentation -> Domain <- Data.
 */
class ArchitectureTest extends TestCase
{
    public static function forbiddenDependencies(): array
    {
        return [
            'Domain không phụ thuộc Laravel' => ['Domain', 'Illuminate\\'],
            'Domain không phụ thuộc tầng Data' => ['Domain', 'App\\Data\\'],
            'Domain không phụ thuộc tầng Presentation' => ['Domain', 'App\\Presentation\\'],
            'Data không phụ thuộc tầng Presentation' => ['Data', 'App\\Presentation\\'],
            'Presentation không gọi thẳng tầng Data' => ['Presentation', 'App\\Data\\'],
        ];
    }

    #[DataProvider('forbiddenDependencies')]
    public function test_layer_does_not_depend_on(string $layer, string $forbiddenNamespace): void
    {
        $files = $this->phpFiles(dirname(__DIR__, 2)."/app/{$layer}");
        $this->assertNotEmpty($files, "Không tìm thấy file nào trong tầng {$layer}.");

        foreach ($files as $file) {
            $this->assertStringNotContainsString(
                $forbiddenNamespace,
                file_get_contents($file),
                "{$file} không được phụ thuộc {$forbiddenNamespace}",
            );
        }
    }

    /**
     * @return string[]
     */
    private function phpFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
