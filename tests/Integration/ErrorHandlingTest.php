<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Cli\Application;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Schema\SchemaException;
use Tikhomirov\SettingsCodegen\Schema\SchemaLoader;
use Tikhomirov\SettingsCodegen\Tests\Support\DuplicatePathPostStep;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

/**
 * Failure paths: unreadable or invalid input, unwritable output, conflicting files.
 */
final class ErrorHandlingTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        foreach (['modules/Locked/settings.json'] as $file) {
            if (is_file($this->project->path($file))) {
                chmod($this->project->path($file), 0o644);
            }
        }
        $this->project->remove();
    }

    public function testUnreadableSchema(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root can read any file');
        }

        $this->project->write('modules/Locked/settings.json', '[]');
        chmod($this->project->path('modules/Locked/settings.json'), 0o000);

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Cannot read modules/Locked/settings.json');
        (new SchemaLoader($this->config()))->loadAll();
    }

    public function testInvalidModuleName(): void
    {
        $this->project->write('modules/my-module/settings.json', '[]');

        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('Module name "my-module"');
        (new SchemaLoader($this->config()))->loadAll();
    }

    public function testAliasesOnInvalidEntriesAndCanonicalKeyWins(): void
    {
        $this->project->write('modules/Bad/settings.json', '[1]');
        $config = $this->config(['key_aliases' => ['key' => 'id']]);

        try {
            (new SchemaLoader($config))->load($this->project->path('modules/Bad/settings.json'));
            $this->fail('Expected SchemaException');
        } catch (SchemaException $e) {
            $this->assertStringContainsString('[0]: expected an object', $e->getMessage());
        }

        $this->project->write('modules/Both/settings.json', '[{"id": "real", "key": "alias", "property": "p"}]');
        $module = (new SchemaLoader($config))->load($this->project->path('modules/Both/settings.json'));

        $this->assertSame('real', $module->settings[0]->id);
        $this->assertSame('alias', $module->entries[0]['key']);
    }

    public function testRecursiveGlobWithMissingBase(): void
    {
        $config = $this->config(['schemas' => ['missing/**/settings.json']]);

        $this->assertSame([], (new SchemaLoader($config))->discover());
    }

    public function testDuplicateGeneratedPath(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Two generated files share the path');
        (new Codegen($this->config(['post_steps' => [DuplicatePathPostStep::class]])))->build();
    }

    public function testCannotCreateOutputDirectory(): void
    {
        $this->project->write('build', 'a file where a directory is expected');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create directory');
        (new Codegen($this->config()))->generate();
    }

    public function testCannotWriteOutputFile(): void
    {
        mkdir($this->project->path('build/out/Delivery/DeliveryConfig.php'), 0o775, true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot write');
        (new Codegen($this->config()))->generate();
    }

    public function testResolveConfigFileFallsBackToComposerJson(): void
    {
        $dir = $this->project->path('empty');
        mkdir($dir);

        $this->assertSame($dir . '/composer.json', Application::resolveConfigFile($dir, null));
        $this->assertSame('/abs/c.json', Application::resolveConfigFile($dir, '/abs/c.json'));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function config(array $options = []): CodegenConfig
    {
        return new CodegenConfig($this->project->dir, $options + [
            'adapter' => 'laravel',
            'schemas' => ['modules/*/settings.json'],
            'output' => 'build/out/{module}',
        ]);
    }
}
