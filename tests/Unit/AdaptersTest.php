<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Adapter\AdapterRegistry;
use Tikhomirov\SettingsCodegen\Adapter\Laravel\LaravelAdapter;
use Tikhomirov\SettingsCodegen\Adapter\Symfony\SymfonyAdapter;
use Tikhomirov\SettingsCodegen\Adapter\WordPress\WordPressAdapter;
use Tikhomirov\SettingsCodegen\Adapter\Yii3\Yii3Adapter;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Extension\AbstractExtension;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\Setting;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class AdaptersTest extends TestCase
{
    public function testRegistryNamesAndReservedKeys(): void
    {
        $this->assertSame(['wordpress', 'laravel', 'symfony', 'yii3'], AdapterRegistry::names());

        foreach ([WordPressAdapter::class, LaravelAdapter::class, SymfonyAdapter::class, Yii3Adapter::class] as $class) {
            $adapter = AdapterRegistry::create($class::name());
            $this->assertInstanceOf($class, $adapter);
            $this->assertInstanceOf($class, AdapterRegistry::create(strtoupper($class::name())));
            $this->assertContains('property', $adapter->reservedKeys());
        }

        $this->assertContains('options_expr', (new WordPressAdapter())->reservedKeys());
    }

    public function testAbstractExtensionIsNoOp(): void
    {
        $extension = new class () extends AbstractExtension {};
        $module = new Module('M', '/m/settings.json', 'm/settings.json', [], []);
        $setting = new Setting('a', 'a', 'a', \Tikhomirov\SettingsCodegen\Schema\PhpType::String, false, null, []);

        $this->assertSame([], $extension->reservedKeys());
        $this->assertSame([], $extension->validate($module));
        $this->assertSame([], $extension->classAttributes($module));
        $this->assertSame([], $extension->propertyAttributes($module, $setting));
    }

    public function testTypedAdaptersRenderExtensionAttributes(): void
    {
        $project = new TempProject();

        try {
            $files = (new Codegen(new CodegenConfig($project->dir, [
                'adapter' => 'symfony',
                'schemas' => ['modules/Delivery/settings.json'],
                'namespace' => 'Fixture\\Attrs',
                'output' => 'build/attrs',
                'bootstrap' => ['codegen/bootstrap.php'],
                'extensions' => ['Fixture\\Codegen\\ModesExtension'],
            ])))->build();

            $config = $files[0]->contents;
            $this->assertStringContainsString("use Fixture\\Attribute\\ModeDefaults;\nuse Fixture\\Attribute\\Modes;\n", $config);
            $this->assertStringContainsString("#[Modes(modes: [\n    'delivery',\n])]\nfinal readonly class DeliveryConfig", $config);
            $this->assertStringContainsString("        #[ModeDefaults(values: [\n            'pickup' => '00:15',\n        ])]\n        public string \$restTime,", $config);
        } finally {
            $project->remove();
        }
    }

    public function testYii3CustomReaderSkipsParamHelper(): void
    {
        $project = new TempProject();

        try {
            $files = (new Codegen(new CodegenConfig($project->dir, [
                'adapter' => 'yii3',
                'schemas' => ['modules/Delivery/settings.json'],
                'output' => 'build/y',
                'reader' => ['type' => 'array', 'variable' => 'params', 'expression' => '{var}[{key}] ?? null'],
            ])))->build();

            $this->assertStringContainsString("\$params['shop_mode'] ?? null", $files[0]->contents);
            $this->assertStringNotContainsString('function param(', $files[0]->contents);
        } finally {
            $project->remove();
        }
    }
}
