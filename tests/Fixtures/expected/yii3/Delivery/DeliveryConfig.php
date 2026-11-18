<?php

declare(strict_types=1);

namespace Fixture\Yii3\Delivery;

/**
 * Typed settings of the Delivery module.
 *
 * DO NOT EDIT: generated from modules/Delivery/settings.json by tikhomirov/settings-codegen.
 */
final readonly class DeliveryConfig
{
    public function __construct(
        public bool $enableCalendar,
        public string $restTime,
        public int $slotMinutes,
        public float $surcharge,
        public string $mode,
        /** @var array<array-key, mixed> */
        public array $weekdays,
        /** @var array<array-key, mixed> */
        public array $excluded,
        public string $internalToken,
    ) {}

    public static function load(array $params): self
    {
        return new self(
            enableCalendar: self::bool(self::param($params, 'app.settings.enable_calendar'), true),
            restTime: self::string(self::param($params, 'app.settings.rest_time'), '00:30'),
            slotMinutes: self::int(self::param($params, 'app.settings.slot_minutes'), 30),
            surcharge: self::float(self::param($params, 'app.settings.surcharge'), 1.5),
            mode: self::string(self::param($params, 'app.settings.mode'), 'exact'),
            weekdays: self::array(self::param($params, 'app.settings.weekdays'), [
                'mon',
                'tue',
                'wed',
            ]),
            excluded: self::array(self::param($params, 'app.settings.excluded'), []),
            internalToken: self::string(self::param($params, 'app.settings.internal_token'), ''),
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

    /**
     * @param array<array-key, mixed> $params
     */
    private static function param(array $params, string $path): mixed
    {
        $value = $params;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
