<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Php;

/**
 * Formatting options for generated PHP. Output is always deterministic; these options only
 * pick a flavour so generated files can pass a project's own code style checks untouched.
 */
final class ExportStyle
{
    public const EMPTY_INLINE = 'inline';

    public const EMPTY_MULTILINE = 'multiline';

    public function __construct(
        /** Align "=>" inside multi-line arrays (php-cs-fixer "align_single_space_minimal"). */
        public readonly bool $alignArrows = false,
        /** Export explicit integer keys for lists (var_export flavour: [0 => 'a']). */
        public readonly bool $listKeys = false,
        /** How nested empty arrays are written: "[]" or "[\n]". */
        public readonly string $emptyArray = self::EMPTY_INLINE,
        /** Indentation unit. */
        public readonly string $indent = '    ',
    ) {
        if (! in_array($emptyArray, [self::EMPTY_INLINE, self::EMPTY_MULTILINE], true)) {
            throw new \InvalidArgumentException("Unknown empty_array style \"{$emptyArray}\"");
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        return new self(
            alignArrows: self::bool($options, 'align_arrows'),
            listKeys: self::bool($options, 'list_keys'),
            emptyArray: self::string($options, 'empty_array', self::EMPTY_INLINE),
            indent: self::string($options, 'indent', '    '),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function bool(array $options, string $key): bool
    {
        $value = $options[$key] ?? false;

        return is_bool($value) ? $value : throw new \InvalidArgumentException("Option \"style.{$key}\" must be a boolean");
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function string(array $options, string $key, string $default): string
    {
        $value = $options[$key] ?? $default;

        return is_string($value) ? $value : throw new \InvalidArgumentException("Option \"style.{$key}\" must be a string");
    }
}
