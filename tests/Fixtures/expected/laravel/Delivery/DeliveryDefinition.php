<?php

declare(strict_types=1);

namespace Fixture\Laravel\Delivery;

/**
 * Schema metadata of the Delivery module: fields, defaults and validation rules.
 *
 * DO NOT EDIT: generated from modules/Delivery/settings.json by tikhomirov/settings-codegen.
 */
final class DeliveryDefinition
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
                'id' => 'shop_delivery_title',
                'type' => 'title',
                'title' => 'Delivery',
                'desc' => 'Delivery calendar and slots. Docs: %s',
                'desc_args_expr' => [
                    '\'<a href="https://example.com">example.com</a>\'',
                ],
                'modes' => [
                    'delivery',
                ],
            ],
            [
                'id' => 'shop_enable_calendar',
                'property' => 'enableCalendar',
                'php_type' => 'bool',
                'type' => 'checkbox',
                'title' => 'Enable delivery calendar',
                'desc_tip' => 'Shows the date picker on checkout',
                'default' => 'yes',
                'rules' => [
                    'boolean',
                ],
            ],
            [
                'id' => 'shop_rest_time',
                'property' => 'restTime',
                'type' => 'text',
                'title' => 'Order\'s preparation time',
                'placeholder' => '00:30',
                'default' => '00:30',
                'rules' => [
                    'required',
                    'regex:/^\\d{2}:\\d{2}$/',
                ],
                'default_for_mode' => [
                    'pickup' => '00:15',
                ],
            ],
            [
                'id' => 'shop_slot_minutes',
                'property' => 'slotMinutes',
                'php_type' => 'int',
                'type' => 'number',
                'title' => 'Slot length, "minutes"',
                'default' => '30',
                'custom_attributes' => [
                    'min' => 5,
                    'max' => 240,
                    'step' => 5,
                ],
            ],
            [
                'id' => 'shop_surcharge',
                'property' => 'surcharge',
                'php_type' => 'float',
                'type' => 'number',
                'title' => 'Night surcharge',
                'default' => 1.5,
            ],
            [
                'id' => 'shop_mode',
                'property' => 'mode',
                'type' => 'select',
                'title' => 'Mode',
                'options' => [
                    'exact' => 'Exact time',
                    'range' => 'Time range',
                    0 => 'Disabled',
                ],
                'default' => 'exact',
            ],
            [
                'id' => 'shop_weekdays',
                'property' => 'weekdays',
                'php_type' => 'array',
                'type' => 'multiselect',
                'title' => 'Working days',
                'options_expr' => '\\App\\Calendar::weekdayOptions()',
                'depends_expr' => 'static fn (): bool => true',
                'default' => [
                    'mon',
                    'tue',
                    'wed',
                ],
            ],
            [
                'id' => 'shop_excluded',
                'property' => 'excluded',
                'php_type' => 'array',
                'type' => 'multiselect',
                'title' => 'Excluded products',
                'default' => [],
            ],
            [
                'id' => 'shop_internal_token',
                'property' => 'internalToken',
                'ui' => false,
                'default' => null,
            ],
            [
                'id' => 'shop_delivery_end',
                'type' => 'sectionend',
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
            'enable_calendar' => true,
            'rest_time' => '00:30',
            'slot_minutes' => 30,
            'surcharge' => 1.5,
            'mode' => 'exact',
            'weekdays' => [
                'mon',
                'tue',
                'wed',
            ],
            'excluded' => [],
            'internal_token' => '',
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
            'enable_calendar' => [
                'boolean',
            ],
            'rest_time' => [
                'required',
                'regex:/^\\d{2}:\\d{2}$/',
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
            'enableCalendar' => 'enable_calendar',
            'restTime' => 'rest_time',
            'slotMinutes' => 'slot_minutes',
            'surcharge' => 'surcharge',
            'mode' => 'mode',
            'weekdays' => 'weekdays',
            'excluded' => 'excluded',
            'internalToken' => 'internal_token',
        ];
    }
}
