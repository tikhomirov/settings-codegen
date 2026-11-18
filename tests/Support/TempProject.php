<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Support;

/**
 * Copies tests/Fixtures/project into a temporary directory and removes it afterwards.
 */
final class TempProject
{
    public readonly string $dir;

    public function __construct(string $source = __DIR__ . '/../Fixtures/project')
    {
        $this->dir = sys_get_temp_dir() . '/settings-codegen-test-' . bin2hex(random_bytes(6));
        self::copy($source, $this->dir);
    }

    public function path(string $relative): string
    {
        return $this->dir . '/' . $relative;
    }

    public function write(string $relative, string $contents): void
    {
        $path = $this->path($relative);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0o775, true);
        }
        file_put_contents($path, $contents);
    }

    public function remove(): void
    {
        self::delete($this->dir);
    }

    /**
     * @return array<string, string> Relative path => contents of every file under $relativeDir.
     */
    public function files(string $relativeDir): array
    {
        return self::listFiles($this->path($relativeDir));
    }

    /**
     * @return array<string, string>
     */
    public static function listFiles(string $dir): array
    {
        $files = [];
        if (! is_dir($dir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $info) {
            /** @var \SplFileInfo $info */
            if ($info->isFile()) {
                $files[substr($info->getPathname(), strlen($dir) + 1)] = (string) file_get_contents($info->getPathname());
            }
        }
        ksort($files);

        return $files;
    }

    private static function copy(string $from, string $to): void
    {
        mkdir($to, 0o775, true);
        foreach (new \FilesystemIterator($from) as $info) {
            /** @var \SplFileInfo $info */
            if ($info->getFilename() === 'build') {
                continue;
            }
            $target = $to . '/' . $info->getFilename();
            $info->isDir() ? self::copy($info->getPathname(), $target) : copy($info->getPathname(), $target);
        }
    }

    private static function delete(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);

            return;
        }
        if (! is_dir($path)) {
            return;
        }
        foreach (new \FilesystemIterator($path) as $info) {
            /** @var \SplFileInfo $info */
            self::delete($info->getPathname());
        }
        rmdir($path);
    }
}
