<?php

declare(strict_types=1);

namespace Fixture\MoonShine\Notes;

/**
 * MoonShine form fields of the Notes module.
 *
 * DO NOT EDIT: generated from modules/Notes/settings.json by tikhomirov/settings-codegen.
 */
final class NotesFields
{
    /**
     * Form components: fields grouped by section.
     *
     * @return list<\MoonShine\Contracts\UI\ComponentContract>
     */
    public static function components(): array
    {
        return [];
    }

    /**
     * Form field name by storage key.
     *
     * @return array<string, string>
     */
    public static function names(): array
    {
        return [];
    }

    /**
     * Form values (field name => value), e.g. for FormBuilder::fill().
     *
     * @return array<string, mixed>
     */
    public static function values(NotesConfig $config): array
    {
        return [];
    }

    /**
     * Submitted form input as storage key => raw value, e.g. for NotesConfig::fromArray().
     *
     * @param array<array-key, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function input(array $input): array
    {
        return [];
    }
}
