<?php

declare(strict_types=1);

namespace Fixture\Custom;

/**
 * Typed settings of the Delivery module.
 *
 * DO NOT EDIT: generated from modules/Delivery/settings.json by tikhomirov/settings-codegen.
 */
final class DeliveryConfig
{
    public function __construct(
        public readonly bool $enableCalendar,
        public readonly string $restTime,
        public readonly int $slotMinutes,
        public readonly float $surcharge,
        public readonly string $mode,
        /** @var array<array-key, mixed> */
        public readonly array $weekdays,
        /** @var array<array-key, mixed> */
        public readonly array $excluded,
        public readonly string $internalToken,
    ) {}

    public static function load(\Fixture\SettingsStore $store): self
    {
        return new self(
            enableCalendar: self::bool($store->get('enable_calendar'), true),
            restTime: self::string($store->get('rest_time'), '00:30'),
            slotMinutes: self::int($store->get('slot_minutes'), 30),
            surcharge: self::float($store->get('surcharge'), 1.5),
            mode: self::string($store->get('mode'), 'exact'),
            weekdays: self::array($store->get('weekdays'), [
                'mon',
                'tue',
                'wed',
            ]),
            excluded: self::array($store->get('excluded'), []),
            internalToken: self::string($store->get('internal_token'), ''),
        );
    }

    /**
     * @param array<string, mixed> $values Storage key => raw value.
     */
    public static function fromArray(array $values): self
    {
        return new self(
            enableCalendar: self::bool($values['enable_calendar'] ?? null, true),
            restTime: self::string($values['rest_time'] ?? null, '00:30'),
            slotMinutes: self::int($values['slot_minutes'] ?? null, 30),
            surcharge: self::float($values['surcharge'] ?? null, 1.5),
            mode: self::string($values['mode'] ?? null, 'exact'),
            weekdays: self::array($values['weekdays'] ?? null, [
                'mon',
                'tue',
                'wed',
            ]),
            excluded: self::array($values['excluded'] ?? null, []),
            internalToken: self::string($values['internal_token'] ?? null, ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
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

    private static function string(mixed $value, string $default): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    private static function int(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private static function float(mixed $value, float $default): float
    {
        return is_numeric($value) ? (float) $value : $default;
    }

    private static function bool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
        }

        return $default;
    }

    /**
     * @param array<array-key, mixed> $default
     *
     * @return array<array-key, mixed>
     */
    private static function array(mixed $value, array $default): array
    {
        return is_array($value) ? $value : $default;
    }
}
