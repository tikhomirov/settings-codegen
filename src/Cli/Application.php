<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Cli;

use Throwable;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Result;

/**
 * Dependency-free CLI behind bin/settings-codegen.
 */
final class Application
{
    public const EXIT_OK = 0;

    public const EXIT_OUTDATED = 1;

    public const EXIT_ERROR = 2;

    /** @var resource */
    private $stdout;

    /** @var resource */
    private $stderr;

    /**
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function __construct($stdout = null, $stderr = null)
    {
        $this->stdout = $stdout ?? STDOUT;
        $this->stderr = $stderr ?? STDERR;
    }

    /**
     * @param list<string> $argv Arguments without the script name.
     */
    public function run(array $argv): int
    {
        $command = null;
        $check = false;
        $quiet = false;
        $config = null;
        $workingDir = null;

        foreach ($argv as $arg) {
            if ($arg === '--check') {
                $check = true;
            } elseif ($arg === '-q' || $arg === '--quiet') {
                $quiet = true;
            } elseif ($arg === '-h' || $arg === '--help') {
                $this->out($this->help());

                return self::EXIT_OK;
            } elseif (str_starts_with($arg, '--config=')) {
                $config = substr($arg, 9);
            } elseif (str_starts_with($arg, '--working-dir=')) {
                $workingDir = substr($arg, 14);
            } elseif ($command === null && ! str_starts_with($arg, '-')) {
                $command = $arg;
            } else {
                $this->err("Unknown argument: {$arg}\n\n" . $this->help());

                return self::EXIT_ERROR;
            }
        }

        $command ??= 'generate';

        if ($command === 'check') {
            [$command, $check] = ['generate', true];
        }

        if ($command !== 'generate') {
            $this->err("Unknown command: {$command}\n\n" . $this->help());

            return self::EXIT_ERROR;
        }

        try {
            $codegen = Codegen::fromFile(self::resolveConfigFile($workingDir ?? (string) getcwd(), $config));

            return $check ? $this->report($codegen->check(), true, $quiet) : $this->report($codegen->generate(), false, $quiet);
        } catch (Throwable $e) {
            $this->err('settings-codegen: ' . $e->getMessage() . "\n");

            return self::EXIT_ERROR;
        }
    }

    public static function resolveConfigFile(string $workingDir, ?string $config): string
    {
        if ($config !== null && $config !== '') {
            return $config[0] === '/' ? $config : rtrim($workingDir, '/') . '/' . $config;
        }

        foreach (['settings-codegen.json', 'composer.json'] as $candidate) {
            $file = rtrim($workingDir, '/') . '/' . $candidate;
            if (is_file($file) && ($candidate !== 'composer.json' || self::composerHasConfig($file))) {
                return $file;
            }
        }

        return rtrim($workingDir, '/') . '/composer.json';
    }

    private static function composerHasConfig(string $file): bool
    {
        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) && isset($data['extra'][CodegenConfig::EXTRA_KEY]);
    }

    private function report(Result $result, bool $check, bool $quiet): int
    {
        $total = count($result->files);

        if ($check) {
            if ($result->isUpToDate()) {
                $quiet || $this->out("Generated settings are up to date ({$total} files, {$result->modules} schemas).\n");

                return self::EXIT_OK;
            }

            $this->err('Generated settings are outdated (' . count($result->changed) . " of {$total} files). Run: settings-codegen generate\n");
            foreach ($result->changed as $path) {
                $this->err("  - {$path}\n");
            }

            return self::EXIT_OUTDATED;
        }

        if (! $quiet) {
            foreach ($result->changed as $path) {
                $this->out("  written {$path}\n");
            }
            $this->out('Generated ' . count($result->changed) . " changed of {$total} files from {$result->modules} schemas.\n");
        }

        return self::EXIT_OK;
    }

    private function help(): string
    {
        return <<<'TXT'
Usage: settings-codegen [generate] [--check] [--config=FILE] [--working-dir=DIR] [-q]

  generate            Generate classes from settings.json schemas (default command).
  check, --check      Do not write; exit 1 when generated files are missing or outdated.
  --config=FILE       composer.json with "extra.settings-codegen" or a standalone JSON file.
                      Default: settings-codegen.json, then composer.json in the working dir.
  --working-dir=DIR   Project directory (default: current directory).
  -q, --quiet         Print errors only.

TXT;
    }

    private function out(string $text): void
    {
        fwrite($this->stdout, $text);
    }

    private function err(string $text): void
    {
        fwrite($this->stderr, $text);
    }
}
