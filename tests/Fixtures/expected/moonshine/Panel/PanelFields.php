<?php

declare(strict_types=1);

namespace Fixture\MoonShine\Panel;

use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Textarea;

/**
 * MoonShine form fields of the Panel module.
 *
 * DO NOT EDIT: generated from moonshine/Panel/settings.json by tikhomirov/settings-codegen.
 */
final class PanelFields
{
    /**
     * Form components: fields grouped by section.
     *
     * @return list<\MoonShine\Contracts\UI\ComponentContract>
     */
    public static function components(): array
    {
        return [
            Box::make(\__('Logging'), [
                Switcher::make(\__('Debug mode'), 'setting_panel_debug')
                    ->default(false)
                    ->hint(\__('Log all events.')),
                Select::make(\__('Log level'), 'setting_panel_log_level')
                    ->options([
                        'info' => \__('Info'),
                        'error' => \__('Errors only'),
                    ])
                    ->default('info'),
            ]),
            Number::make(\__('Ratio'), 'setting_panel_ratio')
                ->default(0.5)
                ->customAttributes([
                    'step' => 0.1,
                    'min' => 0,
                    'readonly' => true,
                ]),
            Select::make(\__('Period'), 'setting_panel_period')
                ->options([
                    0 => \__('Today'),
                    1 => \__('Week'),
                ])
                ->default(1)
                ->showWhen('setting_panel_debug', '=', true),
            Select::make(\__('Channels'), 'setting_panel_channels')
                ->options([
                    'mail' => \__('Mail'),
                    'sms' => \__('SMS'),
                ])
                ->multiple()
                ->default([
                    'mail',
                ])
                ->showWhen('external.flag', '!=', 0),
            Password::make(\__('API token'), 'setting_panel_api_token')
                ->default('')
                ->placeholder(\__('token'))
                ->required(),
            Json::make(\__('Tags'), 'setting_panel_tags')
                ->onlyValue()
                ->default([]),
            Textarea::make(\__('note'), 'setting_panel_note'),
        ];
    }

    /**
     * Form field name by storage key.
     *
     * @return array<string, string>
     */
    public static function names(): array
    {
        return [
            'panel.debug' => 'setting_panel_debug',
            'panel.log.level' => 'setting_panel_log_level',
            'panel.ratio' => 'setting_panel_ratio',
            'panel.period' => 'setting_panel_period',
            'panel.channels' => 'setting_panel_channels',
            'panel.api_token' => 'setting_panel_api_token',
            'panel.tags' => 'setting_panel_tags',
            'panel.internal' => 'setting_panel_internal',
            'panel.note' => 'setting_panel_note',
        ];
    }

    /**
     * Form values (field name => value), e.g. for FormBuilder::fill().
     *
     * @return array<string, mixed>
     */
    public static function values(PanelConfig $config): array
    {
        return [
            'setting_panel_debug' => $config->debug,
            'setting_panel_log_level' => $config->logLevel,
            'setting_panel_ratio' => $config->ratio,
            'setting_panel_period' => $config->period,
            'setting_panel_channels' => $config->channels,
            'setting_panel_api_token' => $config->apiToken,
            'setting_panel_tags' => $config->tags,
            'setting_panel_internal' => $config->internal,
            'setting_panel_note' => $config->note,
        ];
    }

    /**
     * Submitted form input as storage key => raw value, e.g. for PanelConfig::fromArray().
     *
     * @param array<array-key, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function input(array $input): array
    {
        return [
            'panel.debug' => $input['setting_panel_debug'] ?? null,
            'panel.log.level' => $input['setting_panel_log_level'] ?? null,
            'panel.ratio' => $input['setting_panel_ratio'] ?? null,
            'panel.period' => $input['setting_panel_period'] ?? null,
            'panel.channels' => $input['setting_panel_channels'] ?? null,
            'panel.api_token' => $input['setting_panel_api_token'] ?? null,
            'panel.tags' => $input['setting_panel_tags'] ?? null,
            'panel.internal' => $input['setting_panel_internal'] ?? null,
            'panel.note' => $input['setting_panel_note'] ?? null,
        ];
    }
}
