<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\PostStep;

use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\Schema\Module;

/**
 * Runs once after all modules were generated, e.g. to build a registry or an index file.
 *
 * Register in "post_steps" as a FQCN or {"class": FQCN, "options": {...}}.
 */
interface PostStepInterface
{
    /**
     * @param list<Module>         $modules All modules, sorted by schema path.
     * @param array<string, mixed> $options Options from the configuration.
     *
     * @return list<GeneratedFile>
     */
    public function run(array $modules, Context $context, array $options): array;
}
