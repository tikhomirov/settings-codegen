<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen;

use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Extension\AttributeSpec;
use Tikhomirov\SettingsCodegen\Extension\ExtensionInterface;
use Tikhomirov\SettingsCodegen\Php\ClassRenderer;
use Tikhomirov\SettingsCodegen\Php\PhpExporter;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/**
 * Everything an adapter or post step needs: configuration, exporter and extensions.
 */
final class Context
{
    public readonly PhpExporter $exporter;

    public readonly ClassRenderer $renderer;

    /**
     * @param list<ExtensionInterface> $extensions
     */
    public function __construct(
        public readonly CodegenConfig $config,
        public readonly array $extensions = [],
    ) {
        $this->exporter = new PhpExporter($config->style());
        $this->renderer = new ClassRenderer($this->exporter);
    }

    public function namespaceFor(Module $module): string
    {
        return strtr($this->config->namespaceTemplate(), ['{module}' => $module->name]);
    }

    public function outputDirFor(Module $module): string
    {
        $dir = dirname($module->sourcePath);
        $path = strtr($this->config->outputTemplate(), [
            '{dir}' => $this->config->relative($dir),
            '{module}' => $module->name,
        ]);

        return rtrim($this->config->path($path), '/');
    }

    /**
     * @return list<string>
     */
    public function reservedKeys(): array
    {
        $keys = [];
        foreach ($this->extensions as $extension) {
            array_push($keys, ...$extension->reservedKeys());
        }

        return $keys;
    }

    /**
     * @return list<AttributeSpec>
     */
    public function classAttributes(Module $module): array
    {
        $attributes = [];
        foreach ($this->extensions as $extension) {
            array_push($attributes, ...$extension->classAttributes($module));
        }

        return $attributes;
    }

    /**
     * @return list<AttributeSpec>
     */
    public function propertyAttributes(Module $module, Setting $setting): array
    {
        $attributes = [];
        foreach ($this->extensions as $extension) {
            array_push($attributes, ...$extension->propertyAttributes($module, $setting));
        }

        return $attributes;
    }
}
