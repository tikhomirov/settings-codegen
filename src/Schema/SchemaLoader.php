<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

use Tikhomirov\SettingsCodegen\Config\CodegenConfig;

/**
 * Finds settings.json files, applies key aliases, validates them and builds modules.
 */
final class SchemaLoader
{
    public function __construct(
        private readonly CodegenConfig $config,
        private ?SchemaValidator $validator = null,
    ) {
        $this->validator ??= new SchemaValidator($config->bool('strict_defaults', true));
    }

    /**
     * @return list<string> Absolute schema paths, sorted for deterministic output.
     */
    public function discover(): array
    {
        $files = [];

        foreach ($this->config->schemas() as $pattern) {
            foreach ($this->expand($this->config->path($pattern)) as $file) {
                $files[$file] = true;
            }
        }

        $files = array_keys($files);
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * @return list<Module>
     */
    public function loadAll(): array
    {
        $modules = [];
        $names = [];

        foreach ($this->discover() as $file) {
            $module = $this->load($file);

            if (isset($names[$module->name])) {
                throw new SchemaException("Duplicate module name \"{$module->name}\": {$names[$module->name]} and {$module->relativeSourcePath}. Use \"module_names\".");
            }

            $names[$module->name] = $module->relativeSourcePath;
            $modules[] = $module;
        }

        return $modules;
    }

    public function load(string $file): Module
    {
        $relative = $this->config->relative($file);
        $json = @file_get_contents($file);

        if ($json === false) {
            throw new SchemaException("Cannot read {$relative}");
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new SchemaException("Invalid JSON in {$relative}: {$e->getMessage()}", 0, $e);
        }

        $structured = SchemaNormalizer::isStructured($data);
        $paths = [];

        if (is_array($data) && $structured) {
            $expanded = $this->normalizer()->expand($data);

            if ($expanded['errors'] !== []) {
                throw SchemaException::invalid($relative, $expanded['errors']);
            }

            [$data, $paths] = [$expanded['entries'], $expanded['paths']];
        }

        $source = $data;
        $data = $this->applyAliases($data);

        if ($structured) {
            $data = $this->normalizer()->complete(self::objects($data));
        }

        $errors = ($this->validator ?? new SchemaValidator())->validate($data);

        if ($errors !== []) {
            throw SchemaException::invalid($relative, self::sourcePaths($errors, $paths));
        }

        $entries = self::objects($data);

        $settings = [];

        foreach ($entries as $entry) {
            $id = $entry['id'] ?? null;
            $property = $entry['property'] ?? null;

            if (! is_string($id) || ! is_string($property)) {
                continue;
            }

            $settings[] = new Setting(
                id: $id,
                storageKey: $this->storageKey($id),
                property: $property,
                type: PhpType::from(is_string($entry['php_type'] ?? null) ? $entry['php_type'] : 'string'),
                hasDefault: array_key_exists('default', $entry),
                default: $entry['default'] ?? null,
                entry: $entry,
            );
        }

        $name = $this->config->moduleNames()[$relative] ?? basename(dirname($file));

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
            throw new SchemaException("Module name \"{$name}\" derived from {$relative} is not a valid class name prefix. Use \"module_names\".");
        }

        return new Module($name, $file, $relative, $entries, $settings, self::objects($source));
    }

    private function normalizer(): SchemaNormalizer
    {
        return new SchemaNormalizer($this->storageKey(...), $this->config->string('adapter', 'wordpress') === 'wordpress');
    }

    /**
     * Rewrites "[3].default: ..." to "sections[0].fields[1].default: ..." for structured schemas.
     *
     * @param list<string> $errors
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private static function sourcePaths(array $errors, array $paths): array
    {
        return array_map(
            static fn (string $error): string => (string) preg_replace_callback(
                '/^\[(\d+)\]/',
                static fn (array $m): string => $paths[(int) $m[1]] ?? $m[0],
                $error,
            ),
            $errors,
        );
    }

    /**
     * Validated schema data as a list of string-keyed entries.
     *
     * @return list<array<string, mixed>>
     */
    private static function objects(mixed $data): array
    {
        $entries = [];
        foreach (is_array($data) ? $data : [] as $entry) {
            $object = [];
            foreach (is_array($entry) ? $entry : [] as $key => $value) {
                $object[(string) $key] = $value;
            }
            $entries[] = $object;
        }

        return $entries;
    }

    public function storageKey(string $id): string
    {
        $prefixes = array_filter($this->config->stripPrefixes(), static fn (string $p): bool => $p !== '');

        do {
            $stripped = false;
            foreach ($prefixes as $prefix) {
                if (str_starts_with($id, $prefix)) {
                    $id = substr($id, strlen($prefix));
                    $stripped = true;
                }
            }
        } while ($stripped && $id !== '');

        return $id;
    }

    private function applyAliases(mixed $data): mixed
    {
        $aliases = $this->config->keyAliases();

        if ($aliases === [] || ! is_array($data)) {
            return $data;
        }

        foreach ($data as $i => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $renamed = [];
            foreach ($entry as $key => $value) {
                $target = $aliases[$key] ?? $key;
                if ($target !== $key && array_key_exists($target, $entry)) {
                    $target = $key; // canonical key wins, alias stays as-is
                }
                $renamed[$target] = $value;
            }
            $data[$i] = $renamed;
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function expand(string $pattern): array
    {
        if (! str_contains($pattern, '**')) {
            $found = glob($pattern) ?: [];

            return array_values(array_filter($found, 'is_file'));
        }

        [$base] = explode('**', $pattern, 2);
        $base = rtrim($base, '/');

        if (! is_dir($base)) {
            return [];
        }

        $regex = '#^' . strtr(preg_quote($pattern, '#'), ['\*\*/' => '(?:.*/)?', '\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]']) . '$#';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $info) {
            if ($info instanceof \SplFileInfo && $info->isFile() && preg_match($regex, $info->getPathname()) === 1) {
                $files[] = $info->getPathname();
            }
        }

        return $files;
    }
}
