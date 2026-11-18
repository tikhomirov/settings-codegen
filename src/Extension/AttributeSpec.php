<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Extension;

/**
 * A PHP attribute to put on a generated class or property.
 */
final class AttributeSpec
{
    /**
     * @param string                   $class     Fully-qualified attribute class name.
     * @param array<int|string, mixed> $arguments Named (string keys) or positional arguments; values are exported.
     */
    public function __construct(
        public readonly string $class,
        public readonly array $arguments = [],
    ) {
    }

    public function shortName(): string
    {
        $class = ltrim($this->class, '\\');
        $pos = strrpos($class, '\\');

        return $pos === false ? $class : substr($class, $pos + 1);
    }
}
