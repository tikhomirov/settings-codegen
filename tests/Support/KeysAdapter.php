<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Support;

use Tikhomirov\SettingsCodegen\Adapter\AdapterInterface;
use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\Setting;

/** Minimal custom adapter used by tests. */
final class KeysAdapter implements AdapterInterface
{
    public static function name(): string
    {
        return 'keys';
    }

    public function reservedKeys(): array
    {
        return [];
    }

    public function generate(Module $module, Context $context): array
    {
        $keys = array_map(static fn (Setting $s): string => $s->id, $module->settings);

        return [new GeneratedFile(dirname($module->sourcePath) . '/keys.txt', implode("\n", $keys) . "\n")];
    }
}
