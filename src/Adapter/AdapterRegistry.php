<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter;

use Tikhomirov\SettingsCodegen\Adapter\Laravel\LaravelAdapter;
use Tikhomirov\SettingsCodegen\Adapter\MoonShine\MoonShineAdapter;
use Tikhomirov\SettingsCodegen\Adapter\Symfony\SymfonyAdapter;
use Tikhomirov\SettingsCodegen\Adapter\WordPress\WordPressAdapter;
use Tikhomirov\SettingsCodegen\Adapter\Yii3\Yii3Adapter;
use Tikhomirov\SettingsCodegen\Config\ConfigException;

/**
 * Resolves an adapter by built-in name or class name.
 */
final class AdapterRegistry
{
    /** @var array<string, class-string<AdapterInterface>> */
    private const BUILT_IN = [
        'wordpress' => WordPressAdapter::class,
        'laravel' => LaravelAdapter::class,
        'symfony' => SymfonyAdapter::class,
        'yii3' => Yii3Adapter::class,
        'moonshine' => MoonShineAdapter::class,
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::BUILT_IN);
    }

    public static function create(string $nameOrClass): AdapterInterface
    {
        $class = self::BUILT_IN[strtolower($nameOrClass)] ?? $nameOrClass;

        if (! class_exists($class) || ! is_subclass_of($class, AdapterInterface::class)) {
            throw new ConfigException("Unknown adapter \"{$nameOrClass}\". Use one of: " . implode(', ', self::names()) . ' or a class implementing ' . AdapterInterface::class);
        }

        return new $class();
    }
}
