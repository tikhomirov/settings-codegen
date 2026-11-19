<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Support;

use Tikhomirov\SettingsCodegen\Context;
use Tikhomirov\SettingsCodegen\GeneratedFile;
use Tikhomirov\SettingsCodegen\PostStep\PostStepInterface;

/** Writes to the path of the first module's Config class to trigger the conflict check. */
final class DuplicatePathPostStep implements PostStepInterface
{
    public function run(array $modules, Context $context, array $options): array
    {
        return [new GeneratedFile($context->outputDirFor($modules[0]) . '/' . $modules[0]->name . 'Config.php', '')];
    }
}
