<?php

namespace Javaabu\PostTypes\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The views, styles and components work right to left: logical directions only
 */
class RtlTest extends TestCase
{
    #[Test]
    public function it_uses_logical_directions_only(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (['resources/views', 'resources/js', 'resources/css'] as $folder) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$folder}")) as $file) {
                if (! preg_match('/\.(php|tsx?|css)$/', $file->getFilename())) {
                    continue;
                }

                foreach (file($file->getPathname()) as $number => $line) {
                    preg_match_all('/class(?:Name)?="([^"]*)"|^\s*[a-z-]+\s*:[^;]*;/', $line, $matches);

                    foreach ([...$matches[1], ...$matches[0]] as $value) {
                        $this->assertDoesNotMatchRegularExpression(
                            '/(?<![\w-])(?:m[lr]|p[lr]|left|right|text-left|text-right|float-left|float-right|rounded-[lr]|border-[lr])-?\d*(?![\w-])|margin-(?:left|right)|padding-(?:left|right)|^\s*(?:left|right)\s*:/',
                            $value,
                            "{$file->getFilename()}:" . ($number + 1) . ' uses a physical direction',
                        );
                    }
                }
            }
        }
    }
}
