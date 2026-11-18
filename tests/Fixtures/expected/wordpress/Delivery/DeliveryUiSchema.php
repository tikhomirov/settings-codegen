<?php

declare(strict_types=1);

namespace Fixture\WordPress\Delivery;

/**
 * WooCommerce settings fields of the Delivery module.
 *
 * DO NOT EDIT: generated from modules/Delivery/settings.json by tikhomirov/settings-codegen.
 */
final class DeliveryUiSchema
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getFields(): array
    {
        return [
            [
                'id' => 'shop_delivery_title',
                'title' => \__('Delivery', 'fixture'),
                'desc' => \sprintf(\__('Delivery calendar and slots. Docs: %s', 'fixture'), '<a href="https://example.com">example.com</a>'),
                'type' => 'title',
            ],
            [
                'id' => 'shop_enable_calendar',
                'title' => \__('Enable delivery calendar', 'fixture'),
                'desc_tip' => \__('Shows the date picker on checkout', 'fixture'),
                'type' => 'checkbox',
                'default' => 'yes',
            ],
            [
                'id' => 'shop_rest_time',
                'title' => \__("Order's preparation time", 'fixture'),
                'type' => 'text',
                'default' => '00:30',
                'placeholder' => \__('00:30', 'fixture'),
            ],
            [
                'id' => 'shop_slot_minutes',
                'title' => \__('Slot length, "minutes"', 'fixture'),
                'type' => 'number',
                'default' => '30',
                'custom_attributes' => [
                    'min' => 5,
                    'max' => 240,
                    'step' => 5,
                ],
            ],
            [
                'id' => 'shop_surcharge',
                'title' => \__('Night surcharge', 'fixture'),
                'type' => 'number',
                'default' => 1.5,
            ],
            [
                'id' => 'shop_mode',
                'title' => \__('Mode', 'fixture'),
                'type' => 'select',
                'options' => [
                    'exact' => \__('Exact time', 'fixture'),
                    'range' => \__('Time range', 'fixture'),
                    '0' => \__('Disabled', 'fixture'),
                ],
                'default' => 'exact',
            ],
            [
                'id' => 'shop_weekdays',
                'title' => \__('Working days', 'fixture'),
                'type' => 'multiselect',
                'default' => [
                    'mon',
                    'tue',
                    'wed',
                ],
                'options' => \App\Calendar::weekdayOptions(),
                'depends' => static fn (): bool => true,
            ],
            [
                'id' => 'shop_excluded',
                'title' => \__('Excluded products', 'fixture'),
                'type' => 'multiselect',
                'default' => [],
            ],
            [
                'id' => 'shop_delivery_end',
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
            'shop_enable_calendar',
            'shop_rest_time',
            'shop_slot_minutes',
            'shop_surcharge',
            'shop_mode',
            'shop_weekdays',
            'shop_excluded',
            'shop_internal_token',
        ];
    }

    /**
     * Option id by Config property name.
     */
    public static function optionIdFor(string $property): string
    {
        $map = [
            'enableCalendar' => 'shop_enable_calendar',
            'restTime' => 'shop_rest_time',
            'slotMinutes' => 'shop_slot_minutes',
            'surcharge' => 'shop_surcharge',
            'mode' => 'shop_mode',
            'weekdays' => 'shop_weekdays',
            'excluded' => 'shop_excluded',
            'internalToken' => 'shop_internal_token',
        ];

        if (! array_key_exists($property, $map)) {
            throw new \InvalidArgumentException("Unknown settings property: {$property}");
        }

        return $map[$property];
    }
}
