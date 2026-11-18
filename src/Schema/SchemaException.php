<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

use RuntimeException;

/**
 * Thrown when a settings.json file cannot be read or is invalid.
 */
final class SchemaException extends RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public static function invalid(string $file, array $errors): self
    {
        return new self("Invalid settings schema {$file}:\n  - " . implode("\n  - ", $errors));
    }
}
