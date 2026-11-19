<?php

declare(strict_types=1);

namespace Fixture\MoonShine\Panel;

/**
 * Schema metadata of the Panel module: fields, defaults and validation rules.
 *
 * DO NOT EDIT: generated from moonshine/Panel/settings.json by tikhomirov/settings-codegen.
 */
final class PanelDefinition
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
                'id' => 'panel.debug',
                'property' => 'debug',
                'php_type' => 'bool',
                'default' => false,
                'ui' => [
                    'section' => 'Logging',
                    'label' => 'Debug mode',
                    'field' => 'switcher',
                    'help' => 'Log all events.',
                ],
            ],
            [
                'id' => 'panel.log.level',
                'property' => 'logLevel',
                'php_type' => 'string',
                'default' => 'info',
                'ui' => [
                    'section' => 'Logging',
                    'label' => 'Log level',
                    'field' => 'select',
                    'options' => [
                        'info' => 'Info',
                        'error' => 'Errors only',
                    ],
                ],
            ],
            [
                'id' => 'panel.ratio',
                'property' => 'ratio',
                'php_type' => 'float',
                'default' => 0.5,
                'ui' => [
                    'label' => 'Ratio',
                    'field' => 'number',
                    'attributes' => [
                        'step' => 0.1,
                        'min' => 0,
                        'readonly' => true,
                        'dependency' => [
                            [
                                'x',
                                '=',
                                '1',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'panel.period',
                'property' => 'period',
                'php_type' => 'int',
                'default' => 1,
                'ui' => [
                    'label' => 'Period',
                    'field' => 'select',
                    'options' => [
                        'Today',
                        'Week',
                    ],
                    'show_when' => [
                        'key' => 'panel.debug',
                        'value' => true,
                    ],
                ],
            ],
            [
                'id' => 'panel.channels',
                'property' => 'channels',
                'php_type' => 'array',
                'default' => [
                    'mail',
                ],
                'ui' => [
                    'label' => 'Channels',
                    'field' => 'select',
                    'multiple' => true,
                    'options' => [
                        'mail' => 'Mail',
                        'sms' => 'SMS',
                    ],
                    'show_when' => [
                        'key' => 'external.flag',
                        'operator' => '!=',
                        'value' => 0,
                    ],
                ],
            ],
            [
                'id' => 'panel.api_token',
                'property' => 'apiToken',
                'php_type' => 'string',
                'default' => '',
                'rules' => [
                    'required',
                ],
                'ui' => [
                    'label' => 'API token',
                    'field' => 'password',
                    'placeholder' => 'token',
                ],
            ],
            [
                'id' => 'panel.tags',
                'property' => 'tags',
                'php_type' => 'array',
                'default' => [],
                'ui' => [
                    'label' => 'Tags',
                ],
            ],
            [
                'id' => 'panel.internal',
                'property' => 'internal',
                'php_type' => 'string',
                'ui' => false,
            ],
            [
                'id' => 'panel.note',
                'property' => 'note',
                'php_type' => 'string',
                'default' => null,
                'ui' => [
                    'field' => 'markdown',
                ],
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
        return [
            'panel.debug' => false,
            'panel.log.level' => 'info',
            'panel.ratio' => 0.5,
            'panel.period' => 1,
            'panel.channels' => [
                'mail',
            ],
            'panel.api_token' => '',
            'panel.tags' => [],
            'panel.internal' => '',
            'panel.note' => '',
        ];
    }

    /**
     * Validation rules by storage key (the "rules" schema key, passed through as-is).
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'panel.api_token' => [
                'required',
            ],
        ];
    }

    /**
     * Storage key by Config property name.
     *
     * @return array<string, string>
     */
    public static function keys(): array
    {
        return [
            'debug' => 'panel.debug',
            'logLevel' => 'panel.log.level',
            'ratio' => 'panel.ratio',
            'period' => 'panel.period',
            'channels' => 'panel.channels',
            'apiToken' => 'panel.api_token',
            'tags' => 'panel.tags',
            'internal' => 'panel.internal',
            'note' => 'panel.note',
        ];
    }
}
