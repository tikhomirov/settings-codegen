<?php

declare(strict_types=1);

namespace Fixture\Codegen;

use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\Extension\AbstractExtension;
use Tikhomirov\SettingsCodegen\Extension\AttributeSpec;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\PostStep\PostStepInterface;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/** Example extension: "modes" on UI blocks and "default_for_mode" on settings become attributes. */
final class ModesExtension extends AbstractExtension
{
    public function reservedKeys(): array
    {
        return ['modes', 'default_for_mode'];
    }

    public function validate(Module $module): array
    {
        $errors = [];
        foreach ($module->settings as $setting) {
            if (array_key_exists('modes', $setting->entry)) {
                $errors[] = "\"modes\" is only allowed on UI blocks ({$setting->id})";
            }
        }

        return $errors;
    }

    public function classAttributes(Module $module): array
    {
        $modes = [];
        foreach ($module->entries as $entry) {
            if (! isset($entry['property']) && is_array($entry['modes'] ?? null)) {
                array_push($modes, ...$entry['modes']);
            }
        }

        return $modes === [] ? [] : [new AttributeSpec('Fixture\\Attribute\\Modes', ['modes' => $modes])];
    }

    public function propertyAttributes(Module $module, Setting $setting): array
    {
        $values = $setting->get('default_for_mode');

        return is_array($values) ? [new AttributeSpec('Fixture\\Attribute\\ModeDefaults', ['values' => $values])] : [];
    }
}

/** Example post step: a registry of all generated modules. */
final class RegistryPostStep implements PostStepInterface
{
    public function run(array $modules, Context $context, array $options): array
    {
        $entries = [];
        foreach ($modules as $module) {
            $entries[] = [$context->exporter->string($module->name), $context->exporter->value(array_map(
                static fn (Setting $setting): string => $setting->storageKey,
                $module->settings,
            ), 1)];
        }

        $code = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $context->exporter->arrayLiteral($entries) . ";\n";

        return [new GeneratedFile($context->config->path((string) $options['path']), $code)];
    }
}
