<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter\Symfony;

use Tikhomirov\SettingsCodegen\Adapter\TypedConfigAdapter;

/**
 * Symfony adapter: load() reads container parameters through ParameterBagInterface
 * (e.g. key_prefix "app.settings."). Missing parameters fall back to schema defaults.
 * Override "reader" to use your own resolver service.
 */
final class SymfonyAdapter extends TypedConfigAdapter
{
    public static function name(): string
    {
        return 'symfony';
    }

    protected function defaultReader(): array
    {
        return [
            'type' => '\\Symfony\\Component\\DependencyInjection\\ParameterBag\\ParameterBagInterface',
            'variable' => 'parameters',
            'expression' => '{var}->has({key}) ? {var}->get({key}) : null',
        ];
    }
}
