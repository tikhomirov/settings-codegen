<?php

declare(strict_types=1);

namespace Fixture\Symfony\Shop;

/**
 * Typed settings of the Shop module.
 *
 * DO NOT EDIT: generated from modules/Shop/settings.json by tikhomirov/settings-codegen.
 */
final readonly class ShopConfig
{
    public function __construct(
        public bool $enabled,
        public string $apiKey,
        public int $timeout,
        public bool $newsletterEnabled,
        /** @var array<array-key, mixed> */
        public array $newsletterLists,
    ) {}

    public static function load(\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameters): self
    {
        return new self(
            enabled: self::bool($parameters->has('app.settings.enabled') ? $parameters->get('app.settings.enabled') : null, true),
            apiKey: self::string($parameters->has('app.settings.api_key') ? $parameters->get('app.settings.api_key') : null, ''),
            timeout: self::int($parameters->has('app.settings.timeout') ? $parameters->get('app.settings.timeout') : null, 30),
            newsletterEnabled: self::bool($parameters->has('app.settings.newsletter_enabled') ? $parameters->get('app.settings.newsletter_enabled') : null, false),
            newsletterLists: self::array($parameters->has('app.settings.newsletter_lists') ? $parameters->get('app.settings.newsletter_lists') : null, [
                'news',
            ]),
        );
    }

    /**
     * @param array<string, mixed> $values Storage key => raw value.
     */
    public static function fromArray(array $values): self
    {
        return new self(
            enabled: self::bool($values['enabled'] ?? null, true),
            apiKey: self::string($values['api_key'] ?? null, ''),
            timeout: self::int($values['timeout'] ?? null, 30),
            newsletterEnabled: self::bool($values['newsletter_enabled'] ?? null, false),
            newsletterLists: self::array($values['newsletter_lists'] ?? null, [
                'news',
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'api_key' => $this->apiKey,
            'timeout' => $this->timeout,
            'newsletter_enabled' => $this->newsletterEnabled,
            'newsletter_lists' => $this->newsletterLists,
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
