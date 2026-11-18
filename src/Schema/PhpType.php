<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Schema;

/**
 * PHP type of a generated config property.
 */
enum PhpType: string
{
    case String = 'string';
    case Int = 'int';
    case Float = 'float';
    case Bool = 'bool';
    case Array = 'array';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
