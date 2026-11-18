<?php

declare(strict_types=1);

namespace Fixture\Symfony\Notes;

/**
 * Typed settings of the Notes module.
 *
 * DO NOT EDIT: generated from modules/Notes/settings.json by tikhomirov/settings-codegen.
 */
final readonly class NotesConfig
{
    public static function load(\Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameters): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> $values Storage key => raw value.
     */
    public static function fromArray(array $values): self
    {
        return new self();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [];
    }
}
