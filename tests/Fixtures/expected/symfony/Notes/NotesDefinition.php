<?php

declare(strict_types=1);

namespace Fixture\Symfony\Notes;

/**
 * Schema metadata of the Notes module: fields, defaults and validation rules.
 *
 * DO NOT EDIT: generated from modules/Notes/settings.json by tikhomirov/settings-codegen.
 */
final class NotesDefinition
{
    /**
     * All schema entries in file order.
     *
     * @return list<array<string, mixed>>
     */
    public static function fields(): array
    {
        return [
            [
                'id' => 'notes_title',
                'type' => 'title',
                'title' => 'Notes',
            ],
            [
                'id' => 'notes_info',
                'type' => 'info',
                'text' => 'Nothing to configure yet: %s',
                'text_args_expr' => [
                    '\'—\'',
                ],
            ],
            [
                'id' => 'notes_end',
                'type' => 'sectionend',
            ],
        ];
    }

    /**
     * Default value by storage key.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [];
    }

    /**
     * Validation rules by storage key (the "rules" schema key, passed through as-is).
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * Storage key by Config property name.
     *
     * @return array<string, string>
     */
    public static function keys(): array
    {
        return [];
    }
}
