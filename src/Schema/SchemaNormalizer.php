<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

/**
 * Turns the structured settings.json format into the flat list of entries the generator uses.
 *
 * Structured format (a JSON object):
 *
 *     {"fields": [...], "sections": [{"title": "Shop", "fields": [...]}]}
 *
 * - a section becomes a "title" entry (all section keys except "fields") + its fields + a "sectionend";
 * - a field with an "id" gets "property" = camelCase of its storage key, unless "property" is given
 *   or set to false (UI-only field);
 * - "php_type" is inferred from "default" (bool, int, float, array, otherwise string);
 * - WordPress: a missing "type" becomes checkbox/number/text, and boolean checkbox defaults become "yes"/"no".
 *
 * The flat list format (a JSON array) is passed through unchanged.
 */
final class SchemaNormalizer
{
    public const ROOT_KEYS = ['$schema', 'fields', 'sections'];

    /** WooCommerce field types that never hold a value. */
    public const NON_VALUE_TYPES = ['title', 'sectionend'];

    /**
     * @param \Closure(string): string $storageKey Maps an id to its storage key (strip_prefixes).
     * @param bool                     $wordpress  Apply WooCommerce conveniences (field type, "yes"/"no").
     */
    public function __construct(
        private readonly \Closure $storageKey,
        private readonly bool $wordpress,
    ) {
    }

    public static function isStructured(mixed $data): bool
    {
        return is_array($data) && ! array_is_list($data);
    }

    /**
     * Flattens a structured schema.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array{entries: list<array<string, mixed>>, paths: list<string>, errors: list<string>}
     */
    public function expand(array $data): array
    {
        $entries = [];
        $paths = [];
        $errors = [];

        foreach (array_keys($data) as $key) {
            if (! in_array($key, self::ROOT_KEYS, true)) {
                $errors[] = "root.{$key}: unknown key, expected \"fields\" and/or \"sections\"";
            }
        }

        if (! array_key_exists('fields', $data) && ! array_key_exists('sections', $data)) {
            $errors[] = 'root: expected "fields" and/or "sections"';
        }

        if (array_key_exists('fields', $data)) {
            $this->fields($data['fields'], 'fields', $entries, $paths, $errors);
        }

        if (array_key_exists('sections', $data)) {
            if (! self::isList($data['sections'])) {
                $errors[] = 'sections: expected a list of section objects';
            }

            foreach (self::isList($data['sections']) ? $data['sections'] : [] as $i => $section) {
                $path = "sections[{$i}]";

                if (! self::isObject($section)) {
                    $errors[] = "{$path}: expected an object";

                    continue;
                }

                $title = ['type' => 'title'];
                foreach ($section as $key => $value) {
                    if ($key !== 'fields' && $key !== 'type') {
                        $title[(string) $key] = $value;
                    }
                }

                $entries[] = $title;
                $paths[] = $path;

                if (! array_key_exists('fields', $section)) {
                    $errors[] = "{$path}.fields: required";
                } else {
                    $this->fields($section['fields'], "{$path}.fields", $entries, $paths, $errors);
                }

                $end = ['type' => 'sectionend'];
                if (array_key_exists('id', $section)) {
                    $end['id'] = $section['id'];
                }

                $entries[] = $end;
                $paths[] = "{$path} (end)";
            }
        }

        return ['entries' => $entries, 'paths' => $paths, 'errors' => $errors];
    }

    /**
     * Fills in "property", "php_type" and (WordPress) "type" for structured entries.
     *
     * @param list<array<string, mixed>> $entries
     *
     * @return list<array<string, mixed>>
     */
    public function complete(array $entries): array
    {
        return array_map($this->entry(...), $entries);
    }

    /**
     * @param array<string, mixed> $entry
     *
     * @return array<string, mixed>
     */
    private function entry(array $entry): array
    {
        if (($entry['property'] ?? null) === false) {
            unset($entry['property']);

            return $entry;
        }

        $id = $entry['id'] ?? null;

        if (! array_key_exists('property', $entry)
            && is_string($id) && $id !== ''
            && ! in_array($entry['type'] ?? null, self::NON_VALUE_TYPES, true)) {
            $entry['property'] = self::camelCase(($this->storageKey)($id));
        }

        if (! array_key_exists('property', $entry)) {
            return $entry;
        }

        $entry['php_type'] ??= self::inferType($entry['default'] ?? null)->value;

        if ($this->wordpress) {
            $entry['type'] ??= match ($entry['php_type']) {
                'bool' => 'checkbox',
                'int', 'float' => 'number',
                'array' => null,
                default => 'text',
            };

            if ($entry['type'] === null) {
                unset($entry['type']);
            }

            if (($entry['type'] ?? null) === 'checkbox' && is_bool($entry['default'] ?? null)) {
                $entry['default'] = $entry['default'] ? 'yes' : 'no';
            }
        }

        return $entry;
    }

    public static function inferType(mixed $default): PhpType
    {
        return match (true) {
            is_bool($default) => PhpType::Bool,
            is_int($default) => PhpType::Int,
            is_float($default) => PhpType::Float,
            is_array($default) => PhpType::Array,
            default => PhpType::String,
        };
    }

    /**
     * "api_key" → "apiKey", "max-retries" → "maxRetries".
     */
    public static function camelCase(string $key): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $key, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = array_shift($words) ?? '';

        return lcfirst($first) . implode('', array_map(ucfirst(...), $words));
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param list<string>               $paths
     * @param list<string>               $errors
     */
    private function fields(mixed $fields, string $path, array &$entries, array &$paths, array &$errors): void
    {
        if (! self::isList($fields)) {
            $errors[] = "{$path}: expected a list of field objects";

            return;
        }

        foreach ($fields as $i => $field) {
            if (! self::isObject($field)) {
                $errors[] = "{$path}[{$i}]: expected an object";

                continue;
            }

            $entry = [];
            foreach ($field as $key => $value) {
                $entry[(string) $key] = $value;
            }

            $entries[] = $entry;
            $paths[] = "{$path}[{$i}]";
        }
    }

    /**
     * @phpstan-assert-if-true list<mixed> $value
     */
    private static function isList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value);
    }

    /**
     * @phpstan-assert-if-true array<array-key, mixed> $value
     */
    private static function isObject(mixed $value): bool
    {
        return is_array($value) && ($value === [] || ! array_is_list($value));
    }
}
