<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Schema\PhpType;
use Tikhomirov\SettingsCodegen\Schema\SchemaException;
use Tikhomirov\SettingsCodegen\Schema\SchemaLoader;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class SchemaLoaderTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->remove();
    }

    public function testKeyAliasesAndModuleNames(): void
    {
        // edakit-style entries: "key" / "type" instead of "id" / "php_type".
        $this->project->write('app/Settings/settings.json', (string) json_encode([
            ['key' => 'api.base_url', 'property' => 'apiBaseUrl', 'type' => 'string', 'default' => ''],
            ['key' => 'checkout.cash_enabled', 'property' => 'cashEnabled', 'type' => 'bool', 'default' => true],
        ]));
        $config = new CodegenConfig($this->project->dir, [
            'schemas' => ['app/**/settings.json'],
            'key_aliases' => ['key' => 'id', 'type' => 'php_type'],
            'module_names' => ['app/Settings/settings.json' => 'Mvp'],
        ]);

        $modules = (new SchemaLoader($config))->loadAll();

        $this->assertCount(1, $modules);
        $this->assertSame('Mvp', $modules[0]->name);
        $this->assertSame('api.base_url', $modules[0]->settings[0]->id);
        $this->assertSame(PhpType::Bool, $modules[0]->settings[1]->type);
    }

    public function testStripPrefixesRepeatedly(): void
    {
        $loader = new SchemaLoader(new CodegenConfig($this->project->dir, ['strip_prefixes' => ['iiko_', 'wc_']]));

        $this->assertSame('rest_time', $loader->storageKey('iiko_iiko_wc_rest_time'));
        $this->assertSame('plain', $loader->storageKey('plain'));
    }

    public function testDiscoverIsSortedAndRecursive(): void
    {
        $config = new CodegenConfig($this->project->dir, ['schemas' => ['modules/**/settings.json', 'modules/*/settings.json']]);
        $files = array_map(fn (string $f): string => $config->relative($f), (new SchemaLoader($config))->discover());

        $this->assertSame(['modules/Delivery/settings.json', 'modules/Notes/settings.json', 'modules/Shop/settings.json'], $files);
    }

    public function testDuplicateModuleNames(): void
    {
        $this->project->write('other/Delivery/settings.json', '[]');
        $config = new CodegenConfig($this->project->dir, ['schemas' => ['modules/*/settings.json', 'other/*/settings.json']]);

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Duplicate module name "Delivery"');
        (new SchemaLoader($config))->loadAll();
    }

    public function testInvalidJson(): void
    {
        $this->project->write('modules/Bad/settings.json', '[{');

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Invalid JSON in modules/Bad/settings.json');
        (new SchemaLoader(new CodegenConfig($this->project->dir, ['schemas' => ['modules/*/settings.json']])))->loadAll();
    }

    public function testExtensionValidationStopsGeneration(): void
    {
        $this->project->write('modules/Bad/settings.json', '[{"id": "a", "property": "a", "modes": ["x"]}]');

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('"modes" is only allowed on UI blocks (a)');
        Codegen::fromFile($this->project->path('wordpress.json'))->build();
    }

    public function testUnknownAdapterExtensionAndPostStep(): void
    {
        foreach ([
            ['adapter' => 'drupal'],
            ['extensions' => ['Nope\\Extension']],
            ['post_steps' => ['Nope\\Step']],
            ['post_steps' => [['options' => []]]],
            ['bootstrap' => ['missing.php']],
        ] as $options) {
            try {
                (new Codegen(new CodegenConfig($this->project->dir, $options + ['schemas' => ['modules/*/settings.json']])))->build();
                $this->fail('Expected ConfigException for ' . json_encode($options));
            } catch (ConfigException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testCustomAdapterByClassName(): void
    {
        $files = (new Codegen(new CodegenConfig($this->project->dir, [
            'adapter' => \Tikhomirov\SettingsCodegen\Tests\Support\KeysAdapter::class,
            'schemas' => ['modules/*/settings.json'],
        ])))->build();

        $this->assertCount(3, $files);
        $this->assertStringEndsWith('modules/Delivery/keys.txt', $files[0]->path);
        $this->assertStringContainsString('shop_rest_time', $files[0]->contents);
    }

    public function testComposerJsonWithoutExtra(): void
    {
        $this->project->write('composer.json', '{"name": "x/y"}');

        $this->expectException(ConfigException::class);
        CodegenConfig::fromFile($this->project->path('composer.json'));
    }
}
