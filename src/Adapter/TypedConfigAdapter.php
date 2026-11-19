<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter;

use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\Php\ClassRenderer;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\PhpType;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/**
 * Base for framework adapters that read settings from a key/value source (Laravel config,
 * Symfony parameters, Yii3 params or any custom store).
 *
 * Generates per module:
 *  - {Module}Config: readonly DTO with load(<reader>), fromArray(array) and toArray();
 *  - {Module}Definition: raw fields, defaults, validation rules and property => key map.
 *
 * Options: reader {type, variable, expression}, key_prefix, config_suffix, definition_suffix,
 * definition (bool), readonly_class, docs, string_cast ("scalar" | "string" | "non_empty_string"),
 * definition_fields ("normalized" | "source").
 */
abstract class TypedConfigAdapter implements AdapterInterface
{
    private const DOCS = [
        'config_class' => [
            'Typed settings of the {module} module.',
            '',
            'DO NOT EDIT: generated from {source} by tikhomirov/settings-codegen.',
        ],
        'definition_class' => [
            'Schema metadata of the {module} module: fields, defaults and validation rules.',
            '',
            'DO NOT EDIT: generated from {source} by tikhomirov/settings-codegen.',
        ],
    ];

    /**
     * Default reader: PHP type of the load() argument, variable name and read expression.
     * "{key}" in the expression is replaced by the exported key literal, "{var}" by "$variable".
     *
     * @return array{type: string, variable: string, expression: string}
     */
    abstract protected function defaultReader(): array;

    /**
     * Extra private static methods the reader expression needs (PHP code, 4-space indented).
     *
     * @param array{type: string, variable: string, expression: string} $reader
     */
    protected function readerHelpers(array $reader): string
    {
        return '';
    }

    public function reservedKeys(): array
    {
        return ['property', 'php_type'];
    }

    public function generate(Module $module, Context $context): array
    {
        $dir = $context->outputDirFor($module);
        $config = $context->config;
        $files = [
            new GeneratedFile("{$dir}/" . $this->configClassName($module, $config) . '.php', $this->configClass($module, $context)),
        ];

        if ($config->bool('definition', true)) {
            $files[] = new GeneratedFile("{$dir}/" . $this->definitionClassName($module, $config) . '.php', $this->definitionClass($module, $context));
        }

        return $files;
    }

    public function configClassName(Module $module, CodegenConfig $config): string
    {
        return $module->name . $config->string('config_suffix', 'Config');
    }

    public function definitionClassName(Module $module, CodegenConfig $config): string
    {
        return $module->name . $config->string('definition_suffix', 'Definition');
    }

    /**
     * @return array{type: string, variable: string, expression: string}
     */
    protected function reader(CodegenConfig $config): array
    {
        $options = array_merge($this->defaultReader(), $config->array('reader'));
        $value = static function (string $key) use ($options): string {
            $value = $options[$key] ?? null;

            return is_string($value) && $value !== ''
                ? $value
                : throw new ConfigException("Option \"reader.{$key}\" must be a non-empty string");
        };

        return [
            'type' => $value('type'),
            'variable' => ltrim($value('variable'), '$'),
            'expression' => $value('expression'),
        ];
    }

    private function configClass(Module $module, Context $context): string
    {
        $config = $context->config;
        $exporter = $context->exporter;
        $renderer = $context->renderer;
        $reader = $this->reader($config);
        $prefix = $config->string('key_prefix', '');
        $readonlyClass = $config->bool('readonly_class', true);
        $stringCast = self::choice($config, 'string_cast', ['scalar', 'string', 'non_empty_string']);
        $class = $this->configClassName($module, $config);

        $imports = [];
        $classAttributes = '';
        foreach ($context->classAttributes($module) as $attribute) {
            $imports[] = $attribute->class;
            $classAttributes .= $renderer->attribute($attribute) . "\n";
        }

        $props = [];
        $loadArgs = [];
        $fromArrayArgs = [];
        $toArray = [];
        $usedTypes = [];

        foreach ($module->settings as $setting) {
            $type = $setting->type;
            $usedTypes[$type->value] = $type;
            $prop = '';

            foreach ($context->propertyAttributes($module, $setting) as $attribute) {
                $imports[] = $attribute->class;
                $prop .= '        ' . $renderer->attribute($attribute, 2) . "\n";
            }

            if ($type === PhpType::Array) {
                $prop .= "        /** @var array<array-key, mixed> */\n";
            }

            $prop .= '        public ' . ($readonlyClass ? '' : 'readonly ') . "{$type->value} \${$setting->property},";
            $props[] = $prop;

            $default = $exporter->value(self::normalizeDefault($setting), 3, inlineEmpty: true);
            $read = strtr($reader['expression'], [
                '{key}' => $exporter->string($prefix . $setting->storageKey),
                '{var}' => '$' . $reader['variable'],
            ]);
            $loadArgs[] = "            {$setting->property}: self::{$type->value}({$read}, {$default}),";
            $fromArrayArgs[] = "            {$setting->property}: self::{$type->value}(\$values[" . $exporter->string($setting->storageKey) . "] ?? null, {$default}),";
            $toArray[] = [$exporter->string($setting->storageKey), '$this->' . $setting->property];
        }

        $out = $renderer->prologue($context->namespaceFor($module), $imports);
        $out .= $renderer->docblock($this->doc($config, 'config_class', $module));
        $out .= $classAttributes;
        $out .= 'final ' . ($readonlyClass ? 'readonly ' : '') . "class {$class}\n{\n";

        if ($props !== []) {
            $out .= "    public function __construct(\n" . implode("\n", $props) . "\n    ) {}\n\n";
        }

        $readerType = $reader['type'];
        $out .= "    public static function load({$readerType} \${$reader['variable']}): self\n    {\n";
        $out .= $loadArgs === []
            ? "        return new self();\n"
            : "        return new self(\n" . implode("\n", $loadArgs) . "\n        );\n";
        $out .= "    }\n\n";

        $out .= $renderer->docblock(['@param array<string, mixed> $values Storage key => raw value.'], 1);
        $out .= "    public static function fromArray(array \$values): self\n    {\n";
        $out .= $fromArrayArgs === []
            ? "        return new self();\n"
            : "        return new self(\n" . implode("\n", $fromArrayArgs) . "\n        );\n";
        $out .= "    }\n\n";

        $out .= $renderer->docblock(['@return array<string, mixed>'], 1);
        $out .= "    public function toArray(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($toArray, 2) . ";\n    }\n";

        foreach (PhpType::cases() as $type) {
            if (isset($usedTypes[$type->value])) {
                $out .= "\n" . self::castHelper($type, $stringCast);
            }
        }

        if ($loadArgs !== []) {
            $out .= $this->readerHelpers($reader);
        }

        return $out . "}\n";
    }

    private function definitionClass(Module $module, Context $context): string
    {
        $config = $context->config;
        $exporter = $context->exporter;
        $renderer = $context->renderer;

        $entries = self::choice($config, 'definition_fields', ['normalized', 'source']) === 'source'
            ? $module->sourceEntries
            : $module->entries;
        $fields = [];
        foreach ($entries as $entry) {
            $fields[] = [null, $exporter->value($entry, 3)];
        }

        $defaults = [];
        $rules = [];
        $keys = [];
        foreach ($module->settings as $setting) {
            $key = $exporter->string($setting->storageKey);
            $defaults[] = [$key, $exporter->value(self::normalizeDefault($setting), 3, inlineEmpty: true)];
            $keys[] = [$exporter->string($setting->property), $key];

            if (array_key_exists('rules', $setting->entry)) {
                $rules[] = [$key, $exporter->value($setting->entry['rules'], 3, inlineEmpty: true)];
            }
        }

        $out = $renderer->prologue($context->namespaceFor($module));
        $out .= $renderer->docblock($this->doc($config, 'definition_class', $module));
        $out .= 'final class ' . $this->definitionClassName($module, $config) . "\n{\n";
        $out .= $renderer->docblock(['All schema entries in file order.', '', '@return list<array<string, mixed>>'], 1);
        $out .= "    public static function fields(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($fields, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock(['Default value by storage key.', '', '@return array<string, mixed>'], 1);
        $out .= "    public static function defaults(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($defaults, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock(['Validation rules by storage key (the "rules" schema key, passed through as-is).', '', '@return array<string, mixed>'], 1);
        $out .= "    public static function rules(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($rules, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock(['Storage key by Config property name.', '', '@return array<string, string>'], 1);
        $out .= "    public static function keys(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($keys, 2) . ";\n    }\n";

        return $out . "}\n";
    }

    /**
     * Default cast to the property type, so literals match the constructor under strict_types.
     */
    public static function normalizeDefault(Setting $setting): mixed
    {
        $value = $setting->hasDefault ? $setting->default : null;

        return match ($setting->type) {
            PhpType::Bool => is_bool($value) ? $value : in_array($value, [1, '1', 'yes', 'on', 'true'], true),
            PhpType::Int => is_numeric($value) ? (int) $value : 0,
            PhpType::Float => is_numeric($value) ? (float) $value : 0.0,
            PhpType::Array => is_array($value) ? $value : [],
            PhpType::String => is_scalar($value) ? (string) $value : '',
        };
    }

    /**
     * @param list<string> $allowed First value is the default.
     */
    private static function choice(CodegenConfig $config, string $key, array $allowed): string
    {
        $value = $config->string($key, $allowed[0]);

        if (! in_array($value, $allowed, true)) {
            throw new ConfigException("Option \"{$key}\" must be one of: " . implode(', ', $allowed));
        }

        return $value;
    }

    /**
     * @param string $stringCast "scalar": any scalar is cast to string; "string": only strings;
     *                           "non_empty_string": only non-empty strings, "" falls back to the default.
     */
    private static function castHelper(PhpType $type, string $stringCast = 'scalar'): string
    {
        $stringBody = match ($stringCast) {
            'string' => 'is_string($value) ? $value : $default',
            'non_empty_string' => "is_string(\$value) && \$value !== '' ? \$value : \$default",
            default => 'is_scalar($value) ? (string) $value : $default',
        };

        return match ($type) {
            PhpType::String => "    private static function string(mixed \$value, string \$default): string\n    {\n"
                . "        return {$stringBody};\n    }\n",
            PhpType::Int => "    private static function int(mixed \$value, int \$default): int\n    {\n"
                . "        return is_numeric(\$value) ? (int) \$value : \$default;\n    }\n",
            PhpType::Float => "    private static function float(mixed \$value, float \$default): float\n    {\n"
                . "        return is_numeric(\$value) ? (float) \$value : \$default;\n    }\n",
            PhpType::Bool => "    private static function bool(mixed \$value, bool \$default): bool\n    {\n"
                . "        if (is_bool(\$value)) {\n            return \$value;\n        }\n\n"
                . "        if (is_string(\$value) || is_int(\$value)) {\n"
                . "            return filter_var(\$value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? \$default;\n        }\n\n"
                . "        return \$default;\n    }\n",
            PhpType::Array => "    /**\n     * @param array<array-key, mixed> \$default\n     *\n     * @return array<array-key, mixed>\n     */\n"
                . "    private static function array(mixed \$value, array \$default): array\n    {\n"
                . "        return is_array(\$value) ? \$value : \$default;\n    }\n",
        };
    }

    /**
     * @return list<string>
     */
    protected function doc(CodegenConfig $config, string $key, Module $module): array
    {
        return ClassRenderer::docLines($config, $key, self::DOCS[$key] ?? [], $module);
    }
}
