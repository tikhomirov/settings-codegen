<?php

declare(strict_types=1);

namespace Fixture\WordPress\Shop;

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
        public array $newsletterLists,
    ) {}

    public static function load(): self
    {
        return new self(
            enabled: self::asBool(\fixture_get_option('enabled', 'yes')),
            apiKey: (string) \fixture_get_option('api_key', ''),
            timeout: (int) \fixture_get_option('timeout', 30),
            newsletterEnabled: self::asBool(\fixture_get_option('newsletter_enabled', 'no')),
            newsletterLists: (array) \fixture_get_option('newsletter_lists', [
                'news',
            ]),
        );
    }

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

    private static function asBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'yes'], true);
    }
}
