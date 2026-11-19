<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter\WordPress;

use Tikhomirov\SettingsCodegen\Adapter\AdapterInterface;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\Php\ClassRenderer;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\PhpType;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/**
 * WordPress / WooCommerce Settings API adapter.
 *
 * Generates per module:
 *  - {Module}Config: readonly DTO with load() reading options through a configurable function;
 *  - {Module}UiSchema: the WooCommerce settings array with __() calls, option keys and a
 *    property => option id map.
 *
 * Options: option_reader, text_domain, config_suffix, schema_suffix, readonly_class, docs.
 */
final class WordPressAdapter implements AdapterInterface
{
    private const RESERVED = ['property', 'php_type', 'desc_args_expr', 'text_args_expr', 'depends_expr', 'options_expr', 'ui', 'rules'];

    private const KEY_ORDER = ['id', 'title', 'desc', 'desc_tip', 'css', 'type', 'options', 'default'];

    private const TRANSLATED = ['title', 'desc_tip', 'button_text', 'placeholder'];

    private const TRANSLATED_WITH_ARGS = ['desc', 'text'];

    private const DOCS = [
        'config_class' => [
            'Typed settings of the {module} module.',
            '',
            'DO NOT EDIT: generated from {source} by tikhomirov/settings-codegen.',
        ],
        'schema_class' => [
            'WooCommerce settings fields of the {module} module.',
            '',
            'DO NOT EDIT: generated from {source} by tikhomirov/settings-codegen.',
        ],
        'all_option_keys' => ['All option ids of the module (including "ui": false).'],
        'option_id_for' => ['Option id by Config property name.'],
    ];

    public static function name(): string
    {
        return 'wordpress';
    }

    public function reservedKeys(): array
    {
        return self::RESERVED;
    }

    public function generate(Module $module, Context $context): array
    {
        $dir = $context->outputDirFor($module);
        $config = $context->config;

        return [
            new GeneratedFile("{$dir}/{$module->name}" . $config->string('config_suffix', 'Config') . '.php', $this->configClass($module, $context)),
            new GeneratedFile("{$dir}/{$module->name}" . $config->string('schema_suffix', 'UiSchema') . '.php', $this->uiSchemaClass($module, $context)),
        ];
    }

    private function configClass(Module $module, Context $context): string
    {
        $config = $context->config;
        $exporter = $context->exporter;
        $renderer = $context->renderer;
        $reader = '\\' . ltrim($config->string('option_reader', 'get_option'), '\\');
        $class = $module->name . $config->string('config_suffix', 'Config');
        $readonlyClass = $config->bool('readonly_class', true);

        $imports = [];
        $classAttributes = '';
        foreach ($context->classAttributes($module) as $attribute) {
            $imports[] = $attribute->class;
            $classAttributes .= $renderer->attribute($attribute) . "\n";
        }

        $props = [];
        $loadArgs = [];
        $toArray = [];
        $hasBool = false;

        foreach ($module->settings as $setting) {
            $type = $setting->type->value;
            $hasBool = $hasBool || $setting->type === PhpType::Bool;
            $prop = '';

            foreach ($context->propertyAttributes($module, $setting) as $attribute) {
                $imports[] = $attribute->class;
                $prop .= '        ' . $renderer->attribute($attribute, 2) . "\n";
            }

            $prop .= '        public ' . ($readonlyClass ? '' : 'readonly ') . "{$type} \${$setting->property},";
            $props[] = $prop;
            $loadArgs[] = '            ' . $setting->property . ': ' . $this->loadExpression($setting, $reader, $context) . ',';
            $toArray[] = [$exporter->string($setting->storageKey), '$this->' . $setting->property];
        }

        $out = $renderer->prologue($context->namespaceFor($module), $imports);
        $out .= $renderer->docblock($this->doc($config, 'config_class', $module));
        $out .= $classAttributes;
        $out .= 'final ' . ($readonlyClass ? 'readonly ' : '') . "class {$class}\n{\n";

        if ($props !== []) {
            $out .= "    public function __construct(\n" . implode("\n", $props) . "\n    ) {}\n\n";
        }

        $out .= "    public static function load(): self\n    {\n";
        $out .= $loadArgs === []
            ? "        return new self();\n"
            : "        return new self(\n" . implode("\n", $loadArgs) . "\n        );\n";
        $out .= "    }\n\n";
        $out .= "    public function toArray(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($toArray, 2) . ";\n    }\n";

        if ($hasBool) {
            $out .= "\n    private static function asBool(mixed \$value): bool\n    {\n"
                . "        return in_array(\$value, [true, 1, '1', 'yes'], true);\n    }\n";
        }

        return $out . "}\n";
    }

    private function loadExpression(Setting $setting, string $reader, Context $context): string
    {
        $default = $setting->default ?? self::defaultFor($setting->type);
        $call = $reader . '(' . $context->exporter->string($setting->storageKey) . ', '
            . $context->exporter->value($default, 3, inlineEmpty: true) . ')';

        return match ($setting->type) {
            PhpType::Bool => "self::asBool({$call})",
            default => "({$setting->type->value}) {$call}",
        };
    }

    /**
     * WordPress-style storage defaults ("no" for checkboxes).
     */
    public static function defaultFor(PhpType $type): mixed
    {
        return match ($type) {
            PhpType::Bool => 'no',
            PhpType::Int => 0,
            PhpType::Float => 0.0,
            PhpType::Array => [],
            PhpType::String => '',
        };
    }

    private function uiSchemaClass(Module $module, Context $context): string
    {
        $config = $context->config;
        $exporter = $context->exporter;
        $renderer = $context->renderer;
        $class = $module->name . $config->string('schema_suffix', 'UiSchema');
        $skip = array_merge(self::RESERVED, $context->reservedKeys());

        $fields = [];
        $optionKeys = [];
        $map = [];

        foreach ($module->entries as $entry) {
            if (is_string($entry['property'] ?? null) && is_string($entry['id'] ?? null) && $entry['id'] !== '') {
                $optionKeys[] = [null, $exporter->string($entry['id'])];
                $map[] = [$exporter->string($entry['property']), $exporter->string($entry['id'])];
            }

            if (($entry['ui'] ?? true) === false) {
                continue;
            }

            $fields[] = [null, $this->fieldArray($entry, $skip, $context)];
        }

        $out = $renderer->prologue($context->namespaceFor($module));
        $out .= $renderer->docblock($this->doc($config, 'schema_class', $module));
        $out .= "final class {$class}\n{\n";
        $out .= $renderer->docblock(['@return array<int, array<string, mixed>>'], 1);
        $out .= "    public static function getFields(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($fields, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock([...$this->doc($config, 'all_option_keys', $module), '', '@return list<string>'], 1);
        $out .= "    public static function getAllOptionKeys(): array\n    {\n";
        $out .= '        return ' . $exporter->arrayLiteral($optionKeys, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock($this->doc($config, 'option_id_for', $module), 1);
        $out .= "    public static function optionIdFor(string \$property): string\n    {\n";

        if ($map === []) {
            $out .= "        throw new \\InvalidArgumentException(\"Unknown settings property: {\$property}\");\n";
        } else {
            $out .= '        $map = ' . $exporter->arrayLiteral($map, 2) . ";\n\n";
            $out .= "        if (! array_key_exists(\$property, \$map)) {\n";
            $out .= "            throw new \\InvalidArgumentException(\"Unknown settings property: {\$property}\");\n";
            $out .= "        }\n\n        return \$map[\$property];\n";
        }

        return $out . "    }\n}\n";
    }

    /**
     * @param array<string, mixed> $field
     * @param list<string>         $skip
     */
    private function fieldArray(array $field, array $skip, Context $context): string
    {
        $exporter = $context->exporter;
        $entries = [];
        $keys = array_values(array_unique(array_merge(self::KEY_ORDER, array_keys($field))));

        foreach ($keys as $key) {
            if (! array_key_exists($key, $field) || in_array($key, $skip, true)) {
                continue;
            }

            $value = $field[$key];
            $keyCode = $exporter->string($key);

            if (in_array($key, self::TRANSLATED, true)) {
                $code = is_bool($value) ? $exporter->scalar($value) : $this->translate($this->stringify($value), $context);
            } elseif (in_array($key, self::TRANSLATED_WITH_ARGS, true)) {
                $code = $this->translate($this->stringify($value), $context);
                $args = $field[$key . '_args_expr'] ?? null;
                if (is_array($args)) {
                    $code = '\\sprintf(' . $code . ', ' . implode(', ', array_filter($args, 'is_string')) . ')';
                }
            } elseif ($key === 'options' && is_array($value)) {
                $options = [];
                foreach ($value as $optionKey => $label) {
                    $options[] = [$exporter->string((string) $optionKey), $this->translate($this->stringify($label), $context)];
                }
                $code = $exporter->arrayLiteral($options, 4, inlineEmpty: false);
            } else {
                $code = $exporter->value($value, 4);
            }

            $entries[] = [$keyCode, $code];
        }

        if (isset($field['options_expr']) && is_string($field['options_expr'])) {
            $entries[] = ["'options'", $field['options_expr']];
        }

        if (isset($field['depends_expr']) && is_string($field['depends_expr'])) {
            $entries[] = ["'depends'", $field['depends_expr']];
        }

        return $exporter->arrayLiteral($entries, 3, inlineEmpty: false);
    }

    private function translate(string $text, Context $context): string
    {
        $domain = $context->config->string('text_domain', 'default');

        return '\\__(' . $context->exporter->minimalString($text) . ', ' . $context->exporter->string($domain) . ')';
    }

    private function stringify(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return list<string>
     */
    private function doc(CodegenConfig $config, string $key, Module $module): array
    {
        return ClassRenderer::docLines($config, $key, self::DOCS[$key] ?? [], $module);
    }
}
