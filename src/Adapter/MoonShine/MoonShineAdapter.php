<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter\MoonShine;

use Tikhomirov\SettingsCodegen\Adapter\TypedConfigAdapter;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\Php\ClassRenderer;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\PhpType;
use Tikhomirov\SettingsCodegen\Schema\SchemaException;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/**
 * MoonShine admin panel adapter.
 *
 * Generates the typed {Module}Config and {Module}Definition of the typed adapters (load() reads
 * the Laravel config repository by default, override "reader" for a settings store) plus
 * {Module}Fields: MoonShine form components built from the schema.
 *
 * Field component, in order of precedence: "ui.field" (switcher, text, textarea, number, select,
 * password, email, url, color, date, phone, hidden, json, markdown), the WooCommerce "type"
 * (checkbox, multiselect, ...), then the value: bool → Switcher, "options" → Select (multiple for
 * arrays), array → Json, string → Text, int/float → Number.
 *
 * Sections ("title" ... "sectionend", the structured "sections" or consecutive "ui.section")
 * become Box (default) or Fieldset components.
 *
 * Options (besides the typed adapter ones): fields_suffix ("Fields"), section ("box" | "fieldset"),
 * translate (function for labels, "__" by default, "" for none), field_name (template with
 * {key}, {key_snake} and {property}, "{key}" by default).
 */
final class MoonShineAdapter extends TypedConfigAdapter
{
    private const UI = 'MoonShine\\UI\\Fields\\';

    /** ui.field / WooCommerce type => MoonShine field class (short name). */
    private const FIELDS = [
        'switcher' => 'Switcher',
        'checkbox' => 'Switcher',
        'text' => 'Text',
        'textarea' => 'Textarea',
        'markdown' => 'Textarea',
        'number' => 'Number',
        'select' => 'Select',
        'multiselect' => 'Select',
        'radio' => 'Select',
        'password' => 'Password',
        'email' => 'Email',
        'url' => 'Url',
        'color' => 'Color',
        'date' => 'Date',
        'phone' => 'Phone',
        'tel' => 'Phone',
        'hidden' => 'Hidden',
        'json' => 'Json',
    ];

    private const SECTIONS = [
        'box' => 'MoonShine\\UI\\Components\\Layout\\Box',
        'fieldset' => 'MoonShine\\UI\\Fields\\Fieldset',
    ];

    private const DOCS = [
        'MoonShine form fields of the {module} module.',
        '',
        'DO NOT EDIT: generated from {source} by tikhomirov/settings-codegen.',
    ];

    public static function name(): string
    {
        return 'moonshine';
    }

    public function reservedKeys(): array
    {
        return [...parent::reservedKeys(), 'ui'];
    }

    protected function defaultReader(): array
    {
        return [
            'type' => '\\Illuminate\\Contracts\\Config\\Repository',
            'variable' => 'config',
            'expression' => '{var}->get({key})',
        ];
    }

    public function generate(Module $module, Context $context): array
    {
        $files = parent::generate($module, $context);
        $class = $module->name . $context->config->string('fields_suffix', 'Fields');
        $files[] = new GeneratedFile($context->outputDirFor($module) . "/{$class}.php", $this->fieldsClass($module, $context, $class));

        return $files;
    }

    private function fieldsClass(Module $module, Context $context, string $class): string
    {
        $config = $context->config;
        $exporter = $context->exporter;
        $sectionOption = $config->string('section', 'box');
        $sectionClass = self::SECTIONS[$sectionOption] ?? throw new ConfigException('Option "section" must be one of: ' . implode(', ', array_keys(self::SECTIONS)));
        $translate = ltrim($config->string('translate', '__'), '\\');
        $configClass = $this->configClassName($module, $config);

        $names = [];
        $ids = [];
        foreach ($module->settings as $setting) {
            $names[$setting->storageKey] = $this->fieldName($setting, $config->string('field_name', '{key}'));
            $ids[$setting->id] = $setting;
        }

        $imports = [];
        $label = function (string $text) use ($exporter, $translate): string {
            return $translate === '' ? $exporter->string($text) : "\\{$translate}(" . $exporter->string($text) . ')';
        };

        /** @var list<array{label: ?string, fields: list<array{Setting, array<array-key, mixed>}>}> $groups */
        $groups = [];
        $fields = [];
        $section = null;
        $explicit = false;

        foreach ($module->entries as $entry) {
            $type = $entry['type'] ?? null;

            if ($type === 'title' || $type === 'sectionend') {
                $groups = self::group($groups, $section, $fields);
                $fields = [];
                $explicit = $type === 'title';
                $title = $entry['title'] ?? null;
                $section = $explicit ? (is_string($title) ? $title : '') : null;

                continue;
            }

            $setting = is_string($entry['id'] ?? null) ? ($ids[$entry['id']] ?? null) : null;

            if ($setting === null || ($entry['ui'] ?? true) === false) {
                continue;
            }

            $ui = is_array($entry['ui'] ?? null) ? $entry['ui'] : [];

            if (! $explicit) {
                // edakit style: consecutive fields with the same "ui.section" form a section.
                $wanted = is_string($ui['section'] ?? null) ? $ui['section'] : null;

                if ($wanted !== $section) {
                    $groups = self::group($groups, $section, $fields);
                    $fields = [];
                    $section = $wanted;
                }
            }

            $fields[] = [$setting, $ui];
        }

        $groups = self::group($groups, $section, $fields);

        $components = [];
        foreach ($groups as $group) {
            $level = $group['label'] === null ? 3 : 4;
            $codes = [];

            foreach ($group['fields'] as [$setting, $ui]) {
                [$short, $code] = $this->field($module, $setting, $ui, $names, $ids, $label, $context, $level);
                $imports[] = self::UI . $short;
                $codes[] = $code . ',';
            }

            if ($group['label'] === null) {
                array_push($components, ...$codes);

                continue;
            }

            $imports[] = $sectionClass;
            $components[] = '            ' . substr($sectionClass, strrpos($sectionClass, '\\') + 1) . '::make(' . $label($group['label']) . ", [\n" . implode("\n", $codes) . "\n            ]),";
        }

        $renderer = $context->renderer;
        $out = $renderer->prologue($context->namespaceFor($module), $imports);
        $out .= $renderer->docblock(ClassRenderer::docLines($config, 'fields_class', self::DOCS, $module));
        $out .= "final class {$class}\n{\n";
        $out .= $renderer->docblock(['Form components: fields grouped by section.', '', '@return list<\\MoonShine\\Contracts\\UI\\ComponentContract>'], 1);
        $out .= "    public static function components(): array\n    {\n";
        $out .= $components === [] ? "        return [];\n    }\n\n" : "        return [\n" . implode("\n", $components) . "\n        ];\n    }\n\n";

        $nameEntries = [];
        $valueEntries = [];
        $inputEntries = [];
        foreach ($module->settings as $setting) {
            $name = $exporter->string($names[$setting->storageKey]);
            $nameEntries[] = [$exporter->string($setting->storageKey), $name];
            $valueEntries[] = [$name, '$config->' . $setting->property];
            $inputEntries[] = [$exporter->string($setting->storageKey), "\$input[{$name}] ?? null"];
        }

        $out .= $renderer->docblock(['Form field name by storage key.', '', '@return array<string, string>'], 1);
        $out .= "    public static function names(): array\n    {\n        return " . $exporter->arrayLiteral($nameEntries, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock(['Form values (field name => value), e.g. for FormBuilder::fill().', '', '@return array<string, mixed>'], 1);
        $out .= "    public static function values({$configClass} \$config): array\n    {\n        return " . $exporter->arrayLiteral($valueEntries, 2) . ";\n    }\n\n";
        $out .= $renderer->docblock(['Submitted form input as storage key => raw value, e.g. for ' . $configClass . '::fromArray().', '', '@param array<array-key, mixed> $input', '', '@return array<string, mixed>'], 1);
        $out .= "    public static function input(array \$input): array\n    {\n        return " . $exporter->arrayLiteral($inputEntries, 2) . ";\n    }\n";

        return $out . "}\n";
    }

    /**
     * @param array<array-key, mixed>  $ui
     * @param array<string, string>    $names
     * @param array<string, Setting>   $ids
     * @param \Closure(string): string $label
     * @param int                      $level Indentation level of the field line.
     *
     * @return array{string, string} Short class name and the field expression.
     */
    private function field(Module $module, Setting $setting, array $ui, array $names, array $ids, \Closure $label, Context $context, int $level): array
    {
        $inner = $level + 1;
        $exporter = $context->exporter;
        $entry = $setting->entry;
        $kind = $ui['field'] ?? $entry['type'] ?? null;
        $options = $ui['options'] ?? $entry['options'] ?? null;

        if (is_string($kind)) {
            $short = self::FIELDS[$kind] ?? throw new SchemaException("{$module->relativeSourcePath}: \"{$setting->id}\": unknown MoonShine field \"{$kind}\". Use one of: " . implode(', ', array_keys(self::FIELDS)));
        } else {
            $short = match (true) {
                $setting->type === PhpType::Bool => 'Switcher',
                is_array($options) => 'Select',
                $setting->type === PhpType::Array => 'Json',
                $setting->type === PhpType::String => 'Text',
                default => 'Number',
            };
        }

        $text = static fn (mixed ...$values): ?string => array_values(array_filter($values, static fn (mixed $v): bool => is_string($v) && $v !== ''))[0] ?? null;
        $title = $text($ui['label'] ?? null, $entry['title'] ?? null) ?? $setting->property;
        $calls = [];

        if ($short === 'Select') {
            $list = [];
            foreach (is_array($options) ? $options : [] as $value => $optionLabel) {
                $list[] = [$exporter->scalar($value), is_string($optionLabel) ? $label($optionLabel) : $exporter->value($optionLabel)];
            }
            if ($list !== []) {
                $calls[] = '->options(' . $exporter->arrayLiteral($list, $inner) . ')';
            }

            if (($ui['multiple'] ?? false) === true || $kind === 'multiselect' || ($kind === null && $setting->type === PhpType::Array)) {
                $calls[] = '->multiple()';
            }
        }

        if ($short === 'Json') {
            $calls[] = '->onlyValue()';
        }

        if ($setting->hasDefault && $setting->default !== null) {
            $calls[] = '->default(' . $exporter->value(self::normalizeDefault($setting), $inner, inlineEmpty: true) . ')';
        }

        $placeholder = $text($ui['placeholder'] ?? null, $entry['placeholder'] ?? null);
        if ($placeholder !== null) {
            $calls[] = '->placeholder(' . $label($placeholder) . ')';
        }

        $hint = $text($ui['help'] ?? null, $entry['desc_tip'] ?? null, $entry['desc'] ?? null);
        if ($hint !== null) {
            $calls[] = '->hint(' . $label($hint) . ')';
        }

        $rules = $entry['rules'] ?? [];
        if (is_array($rules) && in_array('required', $rules, true)) {
            $calls[] = '->required()';
        }

        $attributes = [];
        $source = $ui['attributes'] ?? $entry['custom_attributes'] ?? [];
        foreach (is_array($source) ? $source : [] as $name => $value) {
            if (is_string($name) && (is_scalar($value) || $value === null)) {
                $attributes[] = [$exporter->string($name), $exporter->scalar($value)];
            }
        }
        if ($attributes !== []) {
            $calls[] = '->customAttributes(' . $exporter->arrayLiteral($attributes, $inner) . ')';
        }

        $when = $ui['show_when'] ?? null;
        if (is_array($when) && is_string($when['key'] ?? null) && array_key_exists('value', $when)) {
            $target = $ids[$when['key']] ?? null;
            $column = $target !== null ? $names[$target->storageKey] : $when['key'];
            $operator = is_string($when['operator'] ?? null) ? $when['operator'] : '=';
            $calls[] = '->showWhen(' . $exporter->string($column) . ', ' . $exporter->string($operator) . ', ' . $exporter->value($when['value'], $inner, inlineEmpty: true) . ')';
        }

        $indent = str_repeat('    ', $level);
        $code = "{$indent}{$short}::make(" . $label($title) . ', ' . $exporter->string($names[$setting->storageKey]) . ')';
        foreach ($calls as $call) {
            $code .= "\n{$indent}    {$call}";
        }

        return [$short, $code];
    }

    /**
     * @param list<array{label: ?string, fields: list<array{Setting, array<array-key, mixed>}>}> $groups
     * @param list<array{Setting, array<array-key, mixed>}>                                      $fields
     *
     * @return list<array{label: ?string, fields: list<array{Setting, array<array-key, mixed>}>}>
     */
    private static function group(array $groups, ?string $label, array $fields): array
    {
        return $fields === [] ? $groups : [...$groups, ['label' => $label, 'fields' => $fields]];
    }

    private function fieldName(Setting $setting, string $template): string
    {
        return strtr($template, [
            '{key}' => $setting->storageKey,
            '{key_snake}' => (string) preg_replace('/[^A-Za-z0-9_]/', '_', $setting->storageKey),
            '{property}' => $setting->property,
        ]);
    }
}
