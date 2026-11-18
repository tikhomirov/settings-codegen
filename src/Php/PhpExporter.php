<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Php;

use InvalidArgumentException;

/**
 * Turns PHP values and pre-rendered expressions into deterministic PHP source code.
 */
final class PhpExporter
{
    public function __construct(private readonly ExportStyle $style = new ExportStyle())
    {
    }

    public function style(): ExportStyle
    {
        return $this->style;
    }

    /**
     * Export a PHP value as source code.
     *
     * @param int  $level       Indentation level of the line where the value starts.
     * @param bool $inlineEmpty Force "[]" for an empty array regardless of style.
     */
    public function value(mixed $value, int $level = 0, bool $inlineEmpty = false): string
    {
        if (is_array($value)) {
            if ($value === [] && ($inlineEmpty || $this->style->emptyArray === ExportStyle::EMPTY_INLINE)) {
                return '[]';
            }

            $withKeys = $this->style->listKeys || ! array_is_list($value);
            $entries = [];

            foreach ($value as $key => $item) {
                $entries[] = [$withKeys ? $this->scalar($key) : null, $this->value($item, $level + 1)];
            }

            return $this->arrayLiteral($entries, $level, inlineEmpty: false);
        }

        return $this->scalar($value);
    }

    /**
     * Export a scalar or null.
     */
    public function scalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_float($value) => var_export($value, true),
            is_string($value) => $this->string($value),
            default => throw new InvalidArgumentException('Cannot export value of type ' . get_debug_type($value)),
        };
    }

    /**
     * Single-quoted PHP string literal.
     */
    public function string(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

    /**
     * String literal with the least escaping: single quotes unless the value contains a single
     * quote and no double quote (php-cs-fixer "single_quote" compatible).
     */
    public function minimalString(string $value): string
    {
        if (! str_contains($value, "'") || str_contains($value, '"')) {
            return $this->string($value);
        }

        return '"' . str_replace(['\\', '$'], ['\\\\', '\\$'], $value) . '"';
    }

    /**
     * Render a multi-line array literal from pre-rendered entries.
     *
     * @param list<array{0: ?string, 1: string}> $entries [key code or null, value code]
     * @param int                                $level   Indentation level of the opening line.
     */
    public function arrayLiteral(array $entries, int $level = 0, bool $inlineEmpty = true): string
    {
        $pad = str_repeat($this->style->indent, $level);

        if ($entries === []) {
            return $inlineEmpty ? '[]' : "[\n{$pad}]";
        }

        $inner = str_repeat($this->style->indent, $level + 1);
        $lines = [];

        foreach ($this->alignedKeys($entries) as $i => $key) {
            $lines[] = $inner . ($key === null ? '' : $key . ' => ') . $entries[$i][1] . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n{$pad}]";
    }

    /**
     * @param list<array{0: ?string, 1: string}> $entries
     *
     * @return list<?string>
     */
    private function alignedKeys(array $entries): array
    {
        $keys = array_map(static fn (array $entry): ?string => $entry[0], $entries);

        if (! $this->style->alignArrows) {
            return $keys;
        }

        // A group of consecutive entries is aligned together; an entry whose value spans
        // several lines closes the group it belongs to.
        $groupOf = [];
        $group = 0;
        foreach ($entries as $i => [, $value]) {
            $groupOf[$i] = $group;
            if (str_contains($value, "\n")) {
                ++$group;
            }
        }

        $widths = [];
        foreach ($keys as $i => $key) {
            if ($key !== null) {
                $widths[$groupOf[$i]] = max($widths[$groupOf[$i]] ?? 0, mb_strlen($key));
            }
        }

        $aligned = [];
        foreach ($keys as $i => $key) {
            $aligned[] = $key === null ? null : $key . str_repeat(' ', $widths[$groupOf[$i]] - mb_strlen($key));
        }

        return $aligned;
    }
}
