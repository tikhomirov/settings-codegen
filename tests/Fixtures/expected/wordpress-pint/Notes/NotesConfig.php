<?php

declare(strict_types=1);

namespace Fixture\WordPressPint\Notes;

/**
 * Settings of Notes (settings.json).
 */
final class NotesConfig
{
    public static function load(): self
    {
        return new self();
    }

    public function toArray(): array
    {
        return [];
    }
}
