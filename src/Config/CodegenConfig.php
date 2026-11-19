<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Config;

use Tikhomirov\SettingsCodegen\Php\ExportStyle;

/**
 * Generator configuration, usually read from composer.json "extra.settings-codegen".
 *
 * Common keys: adapter, schemas, namespace, output, module_names, strip_prefixes, key_aliases,
 * style, extensions, post_steps, bootstrap. Adapter-specific keys live in the same object.
 */
final class CodegenConfig
{
    public const EXTRA_KEY = 'settings-codegen';

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public readonly string $projectDir,
        private readonly array $options = [],
    ) {
    }

    /**
     * Load from a composer.json ("extra.settings-codegen") or a standalone JSON file.
     */
    public static function fromFile(string $file): self
    {
        if (! is_file($file)) {
            throw new ConfigException("Configuration file not found: {$file}");
        }

        $json = file_get_contents($file);

        try {
            $data = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConfigException("Invalid JSON in {$file}: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($data)) {
            throw new ConfigException("Expected a JSON object in {$file}");
        }

        $isComposer = basename($file) === 'composer.json';

        if ($isComposer) {
            $extra = is_array($data['extra'] ?? null) ? ($data['extra'][self::EXTRA_KEY] ?? null) : null;
            if (! is_array($extra)) {
                throw new ConfigException("Missing \"extra.settings-codegen\" object in {$file}");
            }
            $data = $extra;
        }

        return new self(dirname((string) realpath($file)), self::object($data));
    }

    public function adapter(): string
    {
        return $this->string('adapter', 'wordpress');
    }

    /**
     * @return list<string>
     */
    public function schemas(): array
    {
        return $this->stringList('schemas', ['settings.json']);
    }

    public function namespaceTemplate(): string
    {
        return trim($this->string('namespace', 'App\\Settings\\Generated'), '\\');
    }

    public function outputTemplate(): string
    {
        return $this->string('output', '{dir}/Generated');
    }

    /**
     * @return array<string, string> Relative schema path => module name.
     */
    public function moduleNames(): array
    {
        return $this->stringMap('module_names');
    }

    /**
     * @return list<string>
     */
    public function stripPrefixes(): array
    {
        return $this->stringList('strip_prefixes', []);
    }

    /**
     * @return array<string, string> Alias key => canonical key, applied to every schema entry.
     */
    public function keyAliases(): array
    {
        return $this->stringMap('key_aliases');
    }

    public function style(): ExportStyle
    {
        return ExportStyle::fromArray($this->array('style'));
    }

    /**
     * @return list<string>
     */
    public function extensions(): array
    {
        return $this->stringList('extensions', []);
    }

    /**
     * @return list<array{class: string, options: array<string, mixed>}>
     */
    public function postSteps(): array
    {
        $steps = [];

        foreach ($this->list('post_steps') as $i => $step) {
            if (is_string($step)) {
                $steps[] = ['class' => $step, 'options' => []];

                continue;
            }

            if (! is_array($step) || ! is_string($step['class'] ?? null)) {
                throw new ConfigException("post_steps[{$i}]: expected a class name or {\"class\": ..., \"options\": {...}}");
            }

            $options = $step['options'] ?? [];
            $steps[] = ['class' => $step['class'], 'options' => is_array($options) ? self::object($options) : []];
        }

        return $steps;
    }

    /**
     * @return list<string> Absolute paths of PHP files to require before generation.
     */
    public function bootstrapFiles(): array
    {
        return array_map(fn (string $file): string => $this->path($file), $this->stringList('bootstrap', []));
    }

    public function path(string $relative): string
    {
        if ($relative === '' || $relative[0] === '/' || preg_match('#^[A-Za-z]:[\\\\/]#', $relative) === 1) {
            return $relative;
        }

        return $this->projectDir . '/' . $relative;
    }

    public function relative(string $absolute): string
    {
        $prefix = rtrim($this->projectDir, '/') . '/';

        return str_starts_with($absolute, $prefix) ? substr($absolute, strlen($prefix)) : $absolute;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->options[$key] ?? $default;

        if (! is_string($value)) {
            throw new ConfigException("Option \"{$key}\" must be a string");
        }

        return $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->options[$key] ?? $default;

        if (! is_bool($value)) {
            throw new ConfigException("Option \"{$key}\" must be a boolean");
        }

        return $value;
    }

    /**
     * Lines of text: accepts a string (split on "\n") or a list of strings.
     *
     * @param list<string> $default
     *
     * @return list<string>
     */
    public function lines(string $key, array $default): array
    {
        $value = $this->options[$key] ?? null;

        if (is_string($value)) {
            return explode("\n", $value);
        }

        return $value === null ? $default : $this->stringList($key, $default);
    }

    /**
     * @param list<string> $default
     *
     * @return list<string>
     */
    public function stringList(string $key, array $default): array
    {
        if (! array_key_exists($key, $this->options)) {
            return $default;
        }

        $value = $this->options[$key];
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw new ConfigException("Option \"{$key}\" must be a list of strings");
        }

        $strings = [];
        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new ConfigException("Option \"{$key}\" must be a list of strings");
            }
            $strings[] = $item;
        }

        return $strings;
    }

    /**
     * @return array<string, mixed>
     */
    public function array(string $key): array
    {
        $value = $this->options[$key] ?? [];

        if (! is_array($value)) {
            throw new ConfigException("Option \"{$key}\" must be an object");
        }

        return self::object($value);
    }

    /**
     * JSON object as an array with string keys (integer-like keys become strings).
     *
     * @param array<mixed> $value
     *
     * @return array<string, mixed>
     */
    private static function object(array $value): array
    {
        $object = [];
        foreach ($value as $key => $item) {
            $object[(string) $key] = $item;
        }

        return $object;
    }

    /**
     * @return list<mixed>
     */
    private function list(string $key): array
    {
        $value = $this->options[$key] ?? [];

        if (! is_array($value) || ! array_is_list($value)) {
            throw new ConfigException("Option \"{$key}\" must be a list");
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private function stringMap(string $key): array
    {
        $map = [];

        foreach ($this->array($key) as $from => $to) {
            if (! is_string($to)) {
                throw new ConfigException("Option \"{$key}\" must map strings to strings");
            }
            $map[(string) $from] = $to;
        }

        return $map;
    }
}
