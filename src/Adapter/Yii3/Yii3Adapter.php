<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter\Yii3;

use Tikhomirov\SettingsCodegen\Adapter\TypedConfigAdapter;

/**
 * Yii3 adapter: load() takes the params array (yiisoft/config "params" group) and resolves
 * dot-separated keys in nested arrays, e.g. key_prefix "app.settings." reads
 * $params['app']['settings'][...]. Override "reader" to use your own resolver.
 */
final class Yii3Adapter extends TypedConfigAdapter
{
    public static function name(): string
    {
        return 'yii3';
    }

    protected function defaultReader(): array
    {
        return [
            'type' => 'array',
            'variable' => 'params',
            'expression' => 'self::param({var}, {key})',
        ];
    }

    protected function readerHelpers(array $reader): string
    {
        if (str_contains($reader['expression'], 'self::param(')) {
            return "\n    /**\n     * @param array<array-key, mixed> \$params\n     */\n"
                . "    private static function param(array \$params, string \$path): mixed\n    {\n"
                . "        \$value = \$params;\n\n"
                . "        foreach (explode('.', \$path) as \$segment) {\n"
                . "            if (! is_array(\$value) || ! array_key_exists(\$segment, \$value)) {\n"
                . "                return null;\n            }\n\n"
                . "            \$value = \$value[\$segment];\n        }\n\n"
                . "        return \$value;\n    }\n";
        }

        return '';
    }
}
