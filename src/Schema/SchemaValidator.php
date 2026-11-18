<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

/**
 * Validates decoded settings.json data against the rules of resources/settings.schema.json.
 *
 * The JSON Schema file is the documentation/IDE contract; this class enforces the same
 * rules without a runtime dependency on a JSON Schema library.
 */
final class SchemaValidator
{
    private const PROPERTY_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    private const STRING_KEYS = ['id', 'property', 'options_expr', 'depends_expr'];

    private const STRING_LIST_KEYS = ['desc_args_expr', 'text_args_expr'];

    /**
     * @param bool $strictDefaults Check that "default" matches "php_type" (disable for legacy schemas).
     */
    public function __construct(private readonly bool $strictDefaults = true)
    {
    }

    /**
     * @return list<string> Human-readable errors; empty when valid.
     */
    public function validate(mixed $data): array
    {
        if (! is_array($data) || ! array_is_list($data)) {
            return ['root: expected a JSON array of field objects'];
        }

        $errors = [];
        $ids = [];
        $properties = [];

        foreach ($data as $index => $entry) {
            $path = "[{$index}]";

            if (! is_array($entry) || ($entry !== [] && array_is_list($entry))) {
                $errors[] = "{$path}: expected an object";

                continue;
            }

            foreach (self::STRING_KEYS as $key) {
                if (array_key_exists($key, $entry) && (! is_string($entry[$key]) || $entry[$key] === '')) {
                    $errors[] = "{$path}.{$key}: expected a non-empty string";
                }
            }

            foreach (self::STRING_LIST_KEYS as $key) {
                if (array_key_exists($key, $entry) && ! $this->isStringList($entry[$key])) {
                    $errors[] = "{$path}.{$key}: expected a list of PHP expression strings";
                }
            }

            if (array_key_exists('ui', $entry) && ! is_bool($entry['ui']) && ! is_array($entry['ui'])) {
                $errors[] = "{$path}.ui: expected a boolean or an object";
            }

            if (array_key_exists('php_type', $entry)
                && (! is_string($entry['php_type']) || ! in_array($entry['php_type'], PhpType::values(), true))) {
                $errors[] = "{$path}.php_type: expected one of " . implode(', ', PhpType::values());
            }

            $hasProperty = array_key_exists('property', $entry);

            if (! $hasProperty) {
                continue;
            }

            if (! array_key_exists('id', $entry)) {
                $errors[] = "{$path}: \"property\" requires \"id\"";

                continue;
            }

            $property = $entry['property'];
            $id = $entry['id'];

            if (! is_string($property) || ! is_string($id)) {
                continue;
            }

            if (preg_match(self::PROPERTY_PATTERN, $property) !== 1) {
                $errors[] = "{$path}.property: \"{$property}\" is not a valid PHP property name";
            }

            if (isset($properties[$property])) {
                $errors[] = "{$path}.property: duplicate property \"{$property}\" (first at {$properties[$property]})";
            } else {
                $properties[$property] = $path;
            }

            if (isset($ids[$id])) {
                $errors[] = "{$path}.id: duplicate id \"{$id}\" (first at {$ids[$id]})";
            } else {
                $ids[$id] = $path;
            }

            $type = is_string($entry['php_type'] ?? null) ? PhpType::tryFrom($entry['php_type']) : PhpType::String;

            if ($this->strictDefaults && $type !== null && isset($entry['default'])) {
                $error = $this->defaultError($type, $entry['default']);

                if ($error !== null) {
                    $errors[] = "{$path}.default: {$error}";
                }
            }
        }

        return $errors;
    }

    private function isStringList(mixed $value): bool
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_string($item)) {
                return false;
            }
        }

        return true;
    }

    private function defaultError(PhpType $type, mixed $default): ?string
    {
        return match ($type) {
            PhpType::Array => is_array($default) ? null : 'expected an array for php_type "array"',
            PhpType::Int, PhpType::Float => is_int($default) || is_float($default) || (is_string($default) && is_numeric($default))
                ? null
                : "expected a number for php_type \"{$type->value}\"",
            PhpType::Bool => is_bool($default) || in_array($default, [0, 1, '0', '1', 'yes', 'no', 'on', 'off', 'true', 'false', ''], true)
                ? null
                : 'expected a boolean (or "yes"/"no") for php_type "bool"',
            PhpType::String => is_scalar($default) ? null : 'expected a scalar for php_type "string"',
        };
    }
}
