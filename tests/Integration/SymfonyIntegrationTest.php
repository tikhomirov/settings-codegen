<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tikhomirov\SettingsCodegen\Adapter\Symfony\SettingsCodegenBundle;
use Tikhomirov\SettingsCodegen\Console\GenerateCommand;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class SymfonyIntegrationTest extends TestCase
{
    public function testConsoleCommandGeneratesAndChecks(): void
    {
        $project = new TempProject();

        try {
            $tester = new CommandTester(new GenerateCommand($project->dir));

            $this->assertSame(1, $tester->execute(['--check' => true, '--config' => 'symfony.json']));
            $this->assertStringContainsString('outdated', $tester->getDisplay());

            $this->assertSame(0, $tester->execute(['--config' => 'symfony.json']));
            $this->assertStringContainsString('Generated 4 changed of 4 files.', $tester->getDisplay());

            $this->assertSame(0, $tester->execute(['--check' => true, '--config' => 'symfony.json']));
            $this->assertSame(2, $tester->execute(['--config' => 'missing.json']));
        } finally {
            $project->remove();
        }
    }

    public function testBundleRegistersCommand(): void
    {
        $container = new ContainerBuilder();
        (new SettingsCodegenBundle())->build($container);

        $definition = $container->getDefinition('tikhomirov.settings_codegen.generate_command');
        $this->assertSame(GenerateCommand::class, $definition->getClass());
        $this->assertSame(['%kernel.project_dir%'], $definition->getArguments());
        $this->assertTrue($definition->hasTag('console.command'));
    }

    public function testYii3ConfigPluginRegistersCommand(): void
    {
        $params = require __DIR__ . '/../../config/yii3/params-console.php';
        $composer = json_decode((string) file_get_contents(__DIR__ . '/../../composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(GenerateCommand::class, $params['yiisoft/yii-console']['commands']['settings:generate']);
        $this->assertSame('config/yii3/params-console.php', $composer['extra']['config-plugin']['params-console']);
    }
}
