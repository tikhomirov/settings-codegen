<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter;

use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\Schema\Module;

/**
 * Turns one settings module into framework-specific PHP files.
 *
 * Built-in adapters: wordpress, laravel, symfony, yii3. A custom adapter is referenced by FQCN
 * in the "adapter" option and must have a no-argument constructor.
 */
interface AdapterInterface
{
    public static function name(): string;

    /**
     * Schema keys the adapter interprets itself (never copied verbatim to generated UI arrays).
     *
     * @return list<string>
     */
    public function reservedKeys(): array;

    /**
     * @return list<GeneratedFile>
     */
    public function generate(Module $module, Context $context): array;
}
