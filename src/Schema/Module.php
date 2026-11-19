<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

/**
 * A settings module: one settings.json file.
 */
final class Module
{
    /**
     * Entries exactly as written in the file, before "key_aliases" are applied.
     *
     * @var list<array<string, mixed>>
     */
    public readonly array $sourceEntries;

    /**
     * @param list<array<string, mixed>>      $entries       All schema entries in file order (UI-only ones included), after key aliases.
     * @param list<Setting>                   $settings      Typed settings in file order.
     * @param list<array<string, mixed>>|null $sourceEntries Entries as written in the file; defaults to $entries.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $sourcePath,
        public readonly string $relativeSourcePath,
        public readonly array $entries,
        public readonly array $settings,
        ?array $sourceEntries = null,
    ) {
        $this->sourceEntries = $sourceEntries ?? $entries;
    }
}
