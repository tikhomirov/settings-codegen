<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen;

/**
 * A file produced by an adapter or a post step.
 */
final class GeneratedFile
{
    public function __construct(
        public readonly string $path,
        public readonly string $contents,
    ) {
    }
}
