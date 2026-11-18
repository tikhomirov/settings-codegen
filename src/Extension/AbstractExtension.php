<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Extension;

use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/**
 * No-op base class: override only what you need.
 */
abstract class AbstractExtension implements ExtensionInterface
{
    public function reservedKeys(): array
    {
        return [];
    }

    public function validate(Module $module): array
    {
        return [];
    }

    public function classAttributes(Module $module): array
    {
        return [];
    }

    public function propertyAttributes(Module $module, Setting $setting): array
    {
        return [];
    }
}
