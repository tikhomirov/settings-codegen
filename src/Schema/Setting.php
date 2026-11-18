<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

/**
 * One typed setting: an entry of settings.json that has both "id" and "property".
 */
final class Setting
{
    /**
     * @param array<string, mixed> $entry Raw schema entry (after key aliases).
     */
    public function __construct(
        public readonly string $id,
        public readonly string $storageKey,
        public readonly string $property,
        public readonly PhpType $type,
        public readonly bool $hasDefault,
        public readonly mixed $default,
        public readonly array $entry,
    ) {
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return array_key_exists($key, $this->entry) ? $this->entry[$key] : $fallback;
    }
}
