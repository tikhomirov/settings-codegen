<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Php\ClassRenderer;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class CodegenConfigTest extends TestCase
{
    public function testFromFileErrors(): void
    {
        $project = new TempProject();

        try {
            foreach (['bad.json' => '{', 'scalar.json' => '1'] as $file => $contents) {
                $project->write($file, $contents);
                try {
                    CodegenConfig::fromFile($project->path($file));
                    $this->fail("Expected ConfigException for {$file}");
                } catch (ConfigException $e) {
                    $this->assertStringContainsString($file, $e->getMessage());
                }
            }

            $this->expectException(ConfigException::class);
            CodegenConfig::fromFile($project->path('missing.json'));
        } finally {
            $project->remove();
        }
    }

    public function testPaths(): void
    {
        $config = new CodegenConfig('/project');

        $this->assertSame('/project/src/a.json', $config->path('src/a.json'));
        $this->assertSame('/abs/a.json', $config->path('/abs/a.json'));
        $this->assertSame('C:\\a.json', $config->path('C:\\a.json'));
        $this->assertSame('src/a.json', $config->relative('/project/src/a.json'));
        $this->assertSame('/elsewhere/a.json', $config->relative('/elsewhere/a.json'));
    }

    public function testDefaultsAndSingleStringList(): void
    {
        $config = new CodegenConfig('/p', ['schemas' => 'one/settings.json']);

        $this->assertSame(['one/settings.json'], $config->schemas());
        $this->assertSame('wordpress', $config->adapter());
        $this->assertSame('App\\Settings\\Generated', $config->namespaceTemplate());
        $this->assertSame('{dir}/Generated', $config->outputTemplate());
        $this->assertSame([], $config->postSteps());
        $this->assertTrue($config->bool('readonly_class', true));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, callable(CodegenConfig): mixed, string}>
     */
    public static function invalidOptions(): iterable
    {
        yield 'string' => [['adapter' => 1], static fn (CodegenConfig $c): mixed => $c->adapter(), '"adapter" must be a string'];
        yield 'bool' => [['readonly_class' => 'yes'], static fn (CodegenConfig $c): mixed => $c->bool('readonly_class'), '"readonly_class" must be a boolean'];
        yield 'list is map' => [['schemas' => ['a' => 'b']], static fn (CodegenConfig $c): mixed => $c->schemas(), '"schemas" must be a list of strings'];
        yield 'list item' => [['schemas' => [1]], static fn (CodegenConfig $c): mixed => $c->schemas(), '"schemas" must be a list of strings'];
        yield 'object' => [['style' => 'x'], static fn (CodegenConfig $c): mixed => $c->style(), '"style" must be an object'];
        yield 'post steps not list' => [['post_steps' => ['a' => 'b']], static fn (CodegenConfig $c): mixed => $c->postSteps(), '"post_steps" must be a list'];
        yield 'map values' => [['key_aliases' => ['key' => 1]], static fn (CodegenConfig $c): mixed => $c->keyAliases(), '"key_aliases" must map strings to strings'];
    }

    /**
     * @param array<string, mixed>            $options
     * @param callable(CodegenConfig): mixed $call
     */
    #[DataProvider('invalidOptions')]
    public function testInvalidOptions(array $options, callable $call, string $message): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage($message);
        $call(new CodegenConfig('/p', $options));
    }

    public function testPostStepOptions(): void
    {
        $config = new CodegenConfig('/p', ['post_steps' => [
            'A\\Step',
            ['class' => 'B\\Step', 'options' => ['path' => 'x', 0 => 'y']],
            ['class' => 'C\\Step', 'options' => 'ignored'],
        ]]);

        $this->assertSame([
            ['class' => 'A\\Step', 'options' => []],
            ['class' => 'B\\Step', 'options' => ['path' => 'x', '0' => 'y']],
            ['class' => 'C\\Step', 'options' => []],
        ], $config->postSteps());
    }

    public function testDocLines(): void
    {
        $module = new Module('Shop', '/p/m/Shop/settings.json', 'm/Shop/settings.json', [], []);

        $this->assertSame(['Shop from m/Shop/settings.json', 'settings.json'], ClassRenderer::docLines(
            new CodegenConfig('/p', ['docs' => ['x' => "{module} from {source}\n{file}"]]),
            'x',
            [],
            $module,
        ));
        $this->assertSame(['default'], ClassRenderer::docLines(new CodegenConfig('/p'), 'x', ['default'], $module));

        foreach ([5, [1]] as $invalid) {
            try {
                ClassRenderer::docLines(new CodegenConfig('/p', ['docs' => ['x' => $invalid]]), 'x', [], $module);
                $this->fail('Expected ConfigException');
            } catch (ConfigException $e) {
                $this->assertStringContainsString('"docs.x"', $e->getMessage());
            }
        }
    }
}
