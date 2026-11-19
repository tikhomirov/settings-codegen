<?php

declare(strict_types=1);

namespace Fixture\WordPressPint\Shop;

/**
 * WooCommerce settings fields of the Shop module.
 *
 * DO NOT EDIT: generated from modules/Shop/settings.json by tikhomirov/settings-codegen.
 */
final class ShopUiSchema
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getFields(): array
    {
        return [
            [
                'title' => \__('Shop API', 'fixture'),
                'desc'  => \__('Connection to the shop backend.', 'fixture'),
                'type'  => 'title',
            ],
            [
                'id'      => 'enabled',
                'title'   => \__('Enable integration', 'fixture'),
                'type'    => 'checkbox',
                'default' => 'yes',
            ],
            [
                'id'      => 'api_key',
                'title'   => \__('API key', 'fixture'),
                'type'    => 'text',
                'default' => '',
            ],
            [
                'id'      => 'timeout',
                'title'   => \__('Timeout, seconds', 'fixture'),
                'type'    => 'number',
                'default' => 30,
            ],
            [
                'type' => 'sectionend',
            ],
            [
                'id'    => 'newsletter',
                'title' => \__('Newsletter', 'fixture'),
                'type'  => 'title',
            ],
            [
                'id'      => 'newsletter_enabled',
                'title'   => \__('Send newsletter', 'fixture'),
                'type'    => 'checkbox',
                'default' => 'no',
            ],
            [
                'id'      => 'newsletter_lists',
                'title'   => \__('Mailing lists', 'fixture'),
                'type'    => 'multiselect',
                'options' => [
                    'news'   => \__('News', 'fixture'),
                    'offers' => \__('Offers', 'fixture'),
                ],
                'default' => [
                    0 => 'news',
                ],
            ],
            [
                'id'   => 'newsletter',
                'type' => 'sectionend',
            ],
        ];
    }

    /**
     * All option ids of the module (including "ui": false).
     *
     * @return list<string>
     */
    public static function getAllOptionKeys(): array
    {
        return [
            'enabled',
            'api_key',
            'timeout',
            'newsletter_enabled',
            'newsletter_lists',
        ];
    }

    /**
     * Option id by Config property name.
     */
    public static function optionIdFor(string $property): string
    {
        $map = [
            'enabled'           => 'enabled',
            'apiKey'            => 'api_key',
            'timeout'           => 'timeout',
            'newsletterEnabled' => 'newsletter_enabled',
            'newsletterLists'   => 'newsletter_lists',
        ];

        if (! array_key_exists($property, $map)) {
            throw new \InvalidArgumentException("Unknown settings property: {$property}");
        }

        return $map[$property];
    }
}
