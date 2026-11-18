<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Adapter\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tikhomirov\SettingsCodegen\Console\GenerateCommand;

/**
 * Optional bundle: registers the "settings:generate" console command (dev environment).
 *
 * config/bundles.php:
 *     Tikhomirov\SettingsCodegen\Adapter\Symfony\SettingsCodegenBundle::class => ['dev' => true],
 */
final class SettingsCodegenBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $definition = new Definition(GenerateCommand::class, ['%kernel.project_dir%']);
        $definition->addTag('console.command', ['command' => 'settings:generate']);
        $container->setDefinition('tikhomirov.settings_codegen.generate_command', $definition);
    }
}
