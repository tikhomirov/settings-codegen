<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Php;

use Tikhomirov\SettingsCodegen\Extension\AttributeSpec;

/**
 * Small helpers shared by adapters: file header, docblocks, imports and attributes.
 */
final class ClassRenderer
{
    public function __construct(private readonly PhpExporter $exporter)
    {
    }

    /**
     * @param list<string> $lines
     */
    public function docblock(array $lines, int $level = 0): string
    {
        $pad = str_repeat($this->exporter->style()->indent, $level);
        $out = $pad . "/**\n";

        foreach ($lines as $line) {
            $out .= $line === '' ? $pad . " *\n" : $pad . ' * ' . $line . "\n";
        }

        return $out . $pad . " */\n";
    }

    /**
     * File prologue: "<?php", strict types, namespace and sorted imports.
     *
     * @param list<string> $imports Fully-qualified class names.
     */
    public function prologue(string $namespace, array $imports = []): string
    {
        $out = "<?php\n\ndeclare(strict_types=1);\n\n";

        if ($namespace !== '') {
            $out .= "namespace {$namespace};\n\n";
        }

        $imports = array_values(array_unique(array_map(static fn (string $fqcn): string => ltrim($fqcn, '\\'), $imports)));
        sort($imports, SORT_STRING | SORT_FLAG_CASE);

        foreach ($imports as $import) {
            $out .= "use {$import};\n";
        }

        return $imports === [] ? $out : $out . "\n";
    }

    /**
     * Render "#[Name(arg: value, ...)]" using the short class name (imported via prologue()).
     */
    public function attribute(AttributeSpec $attribute, int $level = 0): string
    {
        $parts = [];

        foreach ($attribute->arguments as $name => $value) {
            $code = $this->exporter->value($value, $level, inlineEmpty: true);
            $parts[] = is_int($name) ? $code : "{$name}: {$code}";
        }

        $short = $attribute->shortName();

        return $parts === [] ? "#[{$short}]" : "#[{$short}(" . implode(', ', $parts) . ')]';
    }

    /**
     * @param array<string, string> $vars
     */
    public static function interpolate(string $template, array $vars): string
    {
        return strtr($template, $vars);
    }
}
