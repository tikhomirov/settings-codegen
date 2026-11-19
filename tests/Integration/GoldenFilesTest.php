<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

/**
 * Generated output must match tests/Fixtures/expected byte for byte.
 *
 * Regenerate expectations after an intended change: UPDATE_GOLDEN=1 composer test
 */
final class GoldenFilesTest extends TestCase
{
    private const EXPECTED = __DIR__ . '/../Fixtures/expected';

    /**
     * @return iterable<string, array{string}>
     */
    public static function configs(): iterable
    {
        foreach (['wordpress', 'wordpress-pint', 'laravel', 'symfony', 'yii3', 'custom-reader', 'moonshine'] as $config) {
            yield $config => [$config];
        }
    }

    #[DataProvider('configs')]
    public function testOutputMatchesGoldenFiles(string $config): void
    {
        $project = new TempProject();

        try {
            $result = Codegen::fromFile($project->path("{$config}.json"))->generate();
            $actual = $project->files('build');
            $this->assertNotSame([], $actual);
            $this->assertCount(count($actual), $result->changed);

            $outputDir = $this->outputDir($config);
            $expectedDir = self::EXPECTED . '/' . $outputDir;

            if (getenv('UPDATE_GOLDEN') === '1') {
                foreach ($actual as $relative => $contents) {
                    if (! str_starts_with($relative, $outputDir . '/')) {
                        continue;
                    }
                    $target = self::EXPECTED . '/' . $relative;
                    is_dir(dirname($target)) || mkdir(dirname($target), 0o775, true);
                    file_put_contents($target, $contents);
                }
            }

            $expected = TempProject::listFiles($expectedDir);
            $actualForConfig = [];
            foreach ($actual as $relative => $contents) {
                $actualForConfig[substr($relative, strlen($outputDir) + 1)] = $contents;
            }

            $this->assertSame(array_keys($expected), array_keys($actualForConfig), 'Generated file list differs');
            foreach ($expected as $relative => $contents) {
                $this->assertSame($contents, $actualForConfig[$relative], "{$outputDir}/{$relative} differs from the golden file");
            }

            // Second run: nothing to write, check passes.
            $this->assertSame([], Codegen::fromFile($project->path("{$config}.json"))->generate()->changed);
            $this->assertTrue(Codegen::fromFile($project->path("{$config}.json"))->check()->isUpToDate());
        } finally {
            $project->remove();
        }
    }

    #[DataProvider('configs')]
    public function testGoldenFilesAreValidPhp(string $config): void
    {
        foreach (array_keys(TempProject::listFiles(self::EXPECTED . '/' . $this->outputDir($config))) as $relative) {
            $file = self::EXPECTED . '/' . $this->outputDir($config) . '/' . $relative;
            if (PHP_VERSION_ID < 80200 && str_contains((string) file_get_contents($file), 'readonly class')) {
                $this->addToAssertionCount(1);

                continue;
            }
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
            $this->assertSame(0, $code, implode("\n", $output));
        }
    }

    private function outputDir(string $config): string
    {
        return $config === 'custom-reader' ? 'custom' : $config;
    }
}
