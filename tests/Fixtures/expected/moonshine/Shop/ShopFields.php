<?php

declare(strict_types=1);

namespace Fixture\MoonShine\Shop;

use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * MoonShine form fields of the Shop module.
 *
 * DO NOT EDIT: generated from modules/Shop/settings.json by tikhomirov/settings-codegen.
 */
final class ShopFields
{
    /**
     * Form components: fields grouped by section.
     *
     * @return list<\MoonShine\Contracts\UI\ComponentContract>
     */
    public static function components(): array
    {
        return [
            Box::make(\__('Shop API'), [
                Switcher::make(\__('Enable integration'), 'setting_enabled')
                    ->default(true),
                Text::make(\__('API key'), 'setting_api_key')
                    ->default('')
                    ->required(),
                Number::make(\__('Timeout, seconds'), 'setting_timeout')
                    ->default(30),
            ]),
            Box::make(\__('Newsletter'), [
                Switcher::make(\__('Send newsletter'), 'setting_newsletter_enabled')
                    ->default(false),
                Select::make(\__('Mailing lists'), 'setting_newsletter_lists')
                    ->options([
                        'news' => \__('News'),
                        'offers' => \__('Offers'),
                    ])
                    ->multiple()
                    ->default([
                        'news',
                    ]),
            ]),
        ];
    }

    /**
     * Form field name by storage key.
     *
     * @return array<string, string>
     */
    public static function names(): array
    {
        return [
            'enabled' => 'setting_enabled',
            'api_key' => 'setting_api_key',
            'timeout' => 'setting_timeout',
            'newsletter_enabled' => 'setting_newsletter_enabled',
            'newsletter_lists' => 'setting_newsletter_lists',
        ];
    }

    /**
     * Form values (field name => value), e.g. for FormBuilder::fill().
     *
     * @return array<string, mixed>
     */
    public static function values(ShopConfig $config): array
    {
        return [
            'setting_enabled' => $config->enabled,
            'setting_api_key' => $config->apiKey,
            'setting_timeout' => $config->timeout,
            'setting_newsletter_enabled' => $config->newsletterEnabled,
            'setting_newsletter_lists' => $config->newsletterLists,
        ];
    }

    /**
     * Submitted form input as storage key => raw value, e.g. for ShopConfig::fromArray().
     *
     * @param array<array-key, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function input(array $input): array
    {
        return [
            'enabled' => $input['setting_enabled'] ?? null,
            'api_key' => $input['setting_api_key'] ?? null,
            'timeout' => $input['setting_timeout'] ?? null,
            'newsletter_enabled' => $input['setting_newsletter_enabled'] ?? null,
            'newsletter_lists' => $input['setting_newsletter_lists'] ?? null,
        ];
    }
}
