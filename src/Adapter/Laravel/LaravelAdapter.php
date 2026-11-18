<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter\Laravel;

use Tikhomirov\SettingsCodegen\Adapter\TypedConfigAdapter;

/**
 * Laravel adapter: load() reads from the config repository by default
 * (Illuminate\Contracts\Config\Repository), e.g. key_prefix "settings." for config/settings.php.
 * Override "reader" to read from a database-backed store instead.
 */
final class LaravelAdapter extends TypedConfigAdapter
{
    public static function name(): string
    {
        return 'laravel';
    }

    protected function defaultReader(): array
    {
        return [
            'type' => '\\Illuminate\\Contracts\\Config\\Repository',
            'variable' => 'config',
            'expression' => '{var}->get({key})',
        ];
    }
}
