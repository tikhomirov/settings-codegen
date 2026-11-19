<?php

declare(strict_types=1);

namespace Fixture\Yii3\Shop;

/**
 * Schema metadata of the Shop module: fields, defaults and validation rules.
 *
 * DO NOT EDIT: generated from modules/Shop/settings.json by tikhomirov/settings-codegen.
 */
final class ShopDefinition
{
    /**
     * All schema entries in file order.
     *
     * @return list<array<string, mixed>>
     */
    public static function fields(): array
    {
        return [
            [
                'type' => 'title',
                'title' => 'Shop API',
                'desc' => 'Connection to the shop backend.',
            ],
            [
                'id' => 'enabled',
                'title' => 'Enable integration',
                'default' => true,
                'property' => 'enabled',
                'php_type' => 'bool',
            ],
            [
                'id' => 'api_key',
                'title' => 'API key',
                'default' => '',
                'rules' => [
                    'required',
                ],
                'property' => 'apiKey',
                'php_type' => 'string',
            ],
            [
                'id' => 'timeout',
                'title' => 'Timeout, seconds',
                'default' => 30,
                'rules' => [
                    'integer',
                    'min:1',
                ],
                'property' => 'timeout',
                'php_type' => 'int',
            ],
            [
                'type' => 'sectionend',
            ],
            [
                'type' => 'title',
                'id' => 'newsletter',
                'title' => 'Newsletter',
            ],
            [
                'id' => 'newsletter_enabled',
                'title' => 'Send newsletter',
                'default' => false,
                'property' => 'newsletterEnabled',
                'php_type' => 'bool',
            ],
            [
                'id' => 'newsletter_lists',
                'type' => 'multiselect',
                'title' => 'Mailing lists',
                'options' => [
                    'news' => 'News',
                    'offers' => 'Offers',
                ],
                'default' => [
                    'news',
                ],
                'property' => 'newsletterLists',
                'php_type' => 'array',
            ],
            [
                'type' => 'sectionend',
                'id' => 'newsletter',
            ],
        ];
    }

    /**
     * Default value by storage key.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'api_key' => '',
            'timeout' => 30,
            'newsletter_enabled' => false,
            'newsletter_lists' => [
                'news',
            ],
        ];
    }

    /**
     * Validation rules by storage key (the "rules" schema key, passed through as-is).
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'api_key' => [
                'required',
            ],
            'timeout' => [
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Storage key by Config property name.
     *
     * @return array<string, string>
     */
    public static function keys(): array
    {
        return [
            'enabled' => 'enabled',
            'apiKey' => 'api_key',
            'timeout' => 'timeout',
            'newsletterEnabled' => 'newsletter_enabled',
            'newsletterLists' => 'newsletter_lists',
        ];
    }
}
