<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Extension;

use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/**
 * Project-specific additions to generated config classes (custom schema keys, attributes).
 *
 * Register FQCNs in "extensions"; classes must have a no-argument constructor.
 */
interface ExtensionInterface
{
    /**
     * Schema keys owned by the extension: they are validated by it and never copied to UI schemas.
     *
     * @return list<string>
     */
    public function reservedKeys(): array;

    /**
     * @return list<string> Errors; empty when the module is valid for this extension.
     */
    public function validate(Module $module): array;

    /**
     * @return list<AttributeSpec>
     */
    public function classAttributes(Module $module): array;

    /**
     * @return list<AttributeSpec>
     */
    public function propertyAttributes(Module $module, Setting $setting): array;
}
