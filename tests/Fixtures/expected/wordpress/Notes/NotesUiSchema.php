<?php

declare(strict_types=1);

namespace Fixture\WordPress\Notes;

/**
 * WooCommerce settings fields of the Notes module.
 *
 * DO NOT EDIT: generated from modules/Notes/settings.json by tikhomirov/settings-codegen.
 */
final class NotesUiSchema
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getFields(): array
    {
        return [
            [
                'id' => 'notes_title',
                'title' => \__('Notes', 'fixture'),
                'type' => 'title',
            ],
            [
                'id' => 'notes_info',
                'type' => 'info',
                'text' => \sprintf(\__('Nothing to configure yet: %s', 'fixture'), '—'),
            ],
            [
                'id' => 'notes_end',
                'type' => 'sectionend',
            ],
        ];
    }

    /**
     * All option ids of the module (including "ui": false).
     *
     * @return list<string>
     */
    public static function getAllOptionKeys(): array
    {
        return [];
    }

    /**
     * Option id by Config property name.
     */
    public static function optionIdFor(string $property): string
    {
        throw new \InvalidArgumentException("Unknown settings property: {$property}");
    }
}
