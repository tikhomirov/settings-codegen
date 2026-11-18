<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

/**
 * A settings module: one settings.json file.
 */
final class Module
{
    /**
     * @param list<array<string, mixed>> $entries  All schema entries in file order (UI-only ones included).
     * @param list<Setting>              $settings Typed settings in file order.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $sourcePath,
        public readonly string $relativeSourcePath,
        public readonly array $entries,
        public readonly array $settings,
    ) {
    }

    public function hasSettings(): bool
    {
        return $this->settings !== [];
    }
}
