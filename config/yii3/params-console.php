<?php

declare(strict_types=1);

use Tikhomirov\SettingsCodegen\Console\GenerateCommand;

/*
 * yiisoft/config plugin: registers "settings:generate" in yiisoft/yii-console.
 * The command reads composer.json (extra.settings-codegen) from the current directory.
 */
return [
    'yiisoft/yii-console' => [
        'commands' => [
            'settings:generate' => GenerateCommand::class,
        ],
    ],
];
