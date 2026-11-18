<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen;

/**
 * Outcome of a generate or check run.
 */
final class Result
{
    /**
     * @param list<GeneratedFile> $files   All generated files.
     * @param list<string>        $changed Paths that differ from disk (written in generate mode).
     */
    public function __construct(
        public readonly array $files,
        public readonly array $changed,
        public readonly int $modules,
    ) {
    }

    public function isUpToDate(): bool
    {
        return $this->changed === [];
    }
}
