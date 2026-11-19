<?php

declare(strict_types=1);

namespace Fixture\MoonShine\Panel;

/**
 * Typed settings of the Panel module.
 *
 * DO NOT EDIT: generated from moonshine/Panel/settings.json by tikhomirov/settings-codegen.
 */
final readonly class PanelConfig
{
    public function __construct(
        public bool $debug,
        public string $logLevel,
        public float $ratio,
        public int $period,
        /** @var array<array-key, mixed> */
        public array $channels,
        public string $apiToken,
        /** @var array<array-key, mixed> */
        public array $tags,
        public string $internal,
        public string $note,
    ) {}

    public static function load(\Illuminate\Contracts\Config\Repository $config): self
    {
        return new self(
            debug: self::bool($config->get('settings.panel.debug'), false),
            logLevel: self::string($config->get('settings.panel.log.level'), 'info'),
            ratio: self::float($config->get('settings.panel.ratio'), 0.5),
            period: self::int($config->get('settings.panel.period'), 1),
            channels: self::array($config->get('settings.panel.channels'), [
                'mail',
            ]),
            apiToken: self::string($config->get('settings.panel.api_token'), ''),
            tags: self::array($config->get('settings.panel.tags'), []),
            internal: self::string($config->get('settings.panel.internal'), ''),
            note: self::string($config->get('settings.panel.note'), ''),
        );
    }

    /**
     * @param array<string, mixed> $values Storage key => raw value.
     */
    public static function fromArray(array $values): self
    {
        return new self(
            debug: self::bool($values['panel.debug'] ?? null, false),
            logLevel: self::string($values['panel.log.level'] ?? null, 'info'),
            ratio: self::float($values['panel.ratio'] ?? null, 0.5),
            period: self::int($values['panel.period'] ?? null, 1),
            channels: self::array($values['panel.channels'] ?? null, [
                'mail',
            ]),
            apiToken: self::string($values['panel.api_token'] ?? null, ''),
            tags: self::array($values['panel.tags'] ?? null, []),
            internal: self::string($values['panel.internal'] ?? null, ''),
            note: self::string($values['panel.note'] ?? null, ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'panel.debug' => $this->debug,
            'panel.log.level' => $this->logLevel,
            'panel.ratio' => $this->ratio,
            'panel.period' => $this->period,
            'panel.channels' => $this->channels,
            'panel.api_token' => $this->apiToken,
            'panel.tags' => $this->tags,
            'panel.internal' => $this->internal,
            'panel.note' => $this->note,
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
