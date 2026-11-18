<?php

declare(strict_types=1);

namespace Fixture\WordPress\Delivery;

use Fixture\Attribute\ModeDefaults;
use Fixture\Attribute\Modes;

/**
 * Typed settings of the Delivery module.
 *
 * DO NOT EDIT: generated from modules/Delivery/settings.json by tikhomirov/settings-codegen.
 */
#[Modes(modes: [
    'delivery',
])]
final readonly class DeliveryConfig
{
    public function __construct(
        public bool $enableCalendar,
        #[ModeDefaults(values: [
            'pickup' => '00:15',
        ])]
        public string $restTime,
        public int $slotMinutes,
        public float $surcharge,
        public string $mode,
        public array $weekdays,
        public array $excluded,
        public string $internalToken,
    ) {}

    public static function load(): self
    {
        return new self(
            enableCalendar: self::asBool(\fixture_get_option('enable_calendar', 'yes')),
            restTime: (string) \fixture_get_option('rest_time', '00:30'),
            slotMinutes: (int) \fixture_get_option('slot_minutes', '30'),
            surcharge: (float) \fixture_get_option('surcharge', 1.5),
            mode: (string) \fixture_get_option('mode', 'exact'),
            weekdays: (array) \fixture_get_option('weekdays', [
                'mon',
                'tue',
                'wed',
            ]),
            excluded: (array) \fixture_get_option('excluded', []),
            internalToken: (string) \fixture_get_option('internal_token', ''),
        );
    }

    public function toArray(): array
    {
        return [
            'enable_calendar' => $this->enableCalendar,
            'rest_time' => $this->restTime,
            'slot_minutes' => $this->slotMinutes,
            'surcharge' => $this->surcharge,
            'mode' => $this->mode,
            'weekdays' => $this->weekdays,
            'excluded' => $this->excluded,
            'internal_token' => $this->internalToken,
        ];
    }

    private static function asBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'yes'], true);
    }
}
