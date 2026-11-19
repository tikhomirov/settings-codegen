<?php

declare(strict_types=1);

namespace Fixture\WordPressPint\Shop;

/**
 * Settings of Shop (settings.json).
 */
final class ShopConfig
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $apiKey,
        public readonly int $timeout,
        public readonly bool $newsletterEnabled,
        public readonly array $newsletterLists,
    ) {}

    public static function load(): self
    {
        return new self(
            enabled: self::asBool(\fixture_get_option('enabled', 'yes')),
            apiKey: (string) \fixture_get_option('api_key', ''),
            timeout: (int) \fixture_get_option('timeout', 30),
            newsletterEnabled: self::asBool(\fixture_get_option('newsletter_enabled', 'no')),
            newsletterLists: (array) \fixture_get_option('newsletter_lists', [
                0 => 'news',
            ]),
        );
    }

    public function toArray(): array
    {
        return [
            'enabled'            => $this->enabled,
            'api_key'            => $this->apiKey,
            'timeout'            => $this->timeout,
            'newsletter_enabled' => $this->newsletterEnabled,
            'newsletter_lists'   => $this->newsletterLists,
        ];
    }

    private static function asBool(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'yes'], true);
    }
}
