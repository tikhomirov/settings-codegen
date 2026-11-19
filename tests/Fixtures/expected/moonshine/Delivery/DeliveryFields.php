<?php

declare(strict_types=1);

namespace Fixture\MoonShine\Delivery;

use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * MoonShine form fields of the Delivery module.
 *
 * DO NOT EDIT: generated from modules/Delivery/settings.json by tikhomirov/settings-codegen.
 */
final class DeliveryFields
{
    /**
     * Form components: fields grouped by section.
     *
     * @return list<\MoonShine\Contracts\UI\ComponentContract>
     */
    public static function components(): array
    {
        return [
            Box::make(\__('Delivery'), [
                Switcher::make(\__('Enable delivery calendar'), 'setting_enable_calendar')
                    ->default(true)
                    ->hint(\__('Shows the date picker on checkout')),
                Text::make(\__('Order\'s preparation time'), 'setting_rest_time')
                    ->default('00:30')
                    ->placeholder(\__('00:30'))
                    ->required(),
                Number::make(\__('Slot length, "minutes"'), 'setting_slot_minutes')
                    ->default(30)
                    ->customAttributes([
                        'min' => 5,
                        'max' => 240,
                        'step' => 5,
                    ]),
                Number::make(\__('Night surcharge'), 'setting_surcharge')
                    ->default(1.5),
                Select::make(\__('Mode'), 'setting_mode')
                    ->options([
                        'exact' => \__('Exact time'),
                        'range' => \__('Time range'),
                        0 => \__('Disabled'),
                    ])
                    ->default('exact'),
                Select::make(\__('Working days'), 'setting_weekdays')
                    ->multiple()
                    ->default([
                        'mon',
                        'tue',
                        'wed',
                    ]),
                Select::make(\__('Excluded products'), 'setting_excluded')
                    ->multiple()
                    ->default([]),
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
            'enable_calendar' => 'setting_enable_calendar',
            'rest_time' => 'setting_rest_time',
            'slot_minutes' => 'setting_slot_minutes',
            'surcharge' => 'setting_surcharge',
            'mode' => 'setting_mode',
            'weekdays' => 'setting_weekdays',
            'excluded' => 'setting_excluded',
            'internal_token' => 'setting_internal_token',
        ];
    }

    /**
     * Form values (field name => value), e.g. for FormBuilder::fill().
     *
     * @return array<string, mixed>
     */
    public static function values(DeliveryConfig $config): array
    {
        return [
            'setting_enable_calendar' => $config->enableCalendar,
            'setting_rest_time' => $config->restTime,
            'setting_slot_minutes' => $config->slotMinutes,
            'setting_surcharge' => $config->surcharge,
            'setting_mode' => $config->mode,
            'setting_weekdays' => $config->weekdays,
            'setting_excluded' => $config->excluded,
            'setting_internal_token' => $config->internalToken,
        ];
    }

    /**
     * Submitted form input as storage key => raw value, e.g. for DeliveryConfig::fromArray().
     *
     * @param array<array-key, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function input(array $input): array
    {
        return [
            'enable_calendar' => $input['setting_enable_calendar'] ?? null,
            'rest_time' => $input['setting_rest_time'] ?? null,
            'slot_minutes' => $input['setting_slot_minutes'] ?? null,
            'surcharge' => $input['setting_surcharge'] ?? null,
            'mode' => $input['setting_mode'] ?? null,
            'weekdays' => $input['setting_weekdays'] ?? null,
            'excluded' => $input['setting_excluded'] ?? null,
            'internal_token' => $input['setting_internal_token'] ?? null,
        ];
    }
}
