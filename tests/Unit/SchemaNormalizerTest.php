<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Schema\Module;
use Tikhomirov\SettingsCodegen\Schema\PhpType;
use Tikhomirov\SettingsCodegen\Schema\SchemaException;
use Tikhomirov\SettingsCodegen\Schema\SchemaLoader;
use Tikhomirov\SettingsCodegen\Schema\SchemaNormalizer;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class SchemaNormalizerTest extends TestCase
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

    /**
     * @param array<string, mixed> $options
     */
    private function load(mixed $schema, array $options = []): Module
    {
        $this->project->write('x/Demo/settings.json', (string) json_encode($schema));

        return (new SchemaLoader(new CodegenConfig($this->project->dir, ['schemas' => ['x/*/settings.json'], ...$options])))->loadAll()[0];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, string>
     */
    private function generate(mixed $schema, array $options): array
    {
        $this->project->write('x/Demo/settings.json', (string) json_encode($schema));
        $files = [];
        foreach ((new Codegen(new CodegenConfig($this->project->dir, ['schemas' => ['x/*/settings.json'], ...$options])))->build() as $file) {
            $files[basename($file->path)] = $file->contents;
        }

        return $files;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adapters(): iterable
    {
        foreach (['wordpress', 'laravel', 'symfony', 'yii3'] as $adapter) {
            yield $adapter => [$adapter];
        }
    }

    /**
     * The structured format is sugar: it generates exactly what the equivalent flat list generates.
     */
    #[DataProvider('adapters')]
    public function testStructuredFormatGeneratesTheSameAsFlatList(string $adapter): void
    {
        $wordpress = $adapter === 'wordpress';
        $structured = [
            'fields' => [['id' => 'shop_mode', 'title' => 'Mode', 'default' => 'live']],
            'sections' => [[
                'id' => 'api',
                'title' => 'API',
                'desc' => 'Connection.',
                'fields' => [
                    ['id' => 'shop_enabled', 'title' => 'Enable', 'default' => true],
                    ['id' => 'shop_timeout', 'title' => 'Timeout', 'default' => 30, 'rules' => ['integer']],
                    ['id' => 'shop_ratio', 'title' => 'Ratio', 'default' => 1.5],
                    ['id' => 'shop_tags', 'title' => 'Tags', 'type' => 'multiselect', 'default' => ['a']],
                    ['id' => 'shop_hint', 'type' => 'info', 'text' => 'Hint', 'property' => false],
                    ['type' => 'title', 'id' => 'sub', 'title' => 'Sub-heading'],
                    ['id' => 'shop_token', 'property' => 'secret', 'php_type' => 'string', 'ui' => false],
                ],
            ]],
        ];
        $flat = [
            ['id' => 'shop_mode', 'title' => 'Mode', 'default' => 'live', 'property' => 'mode', 'php_type' => 'string'] + ($wordpress ? ['type' => 'text'] : []),
            ['type' => 'title', 'id' => 'api', 'title' => 'API', 'desc' => 'Connection.'],
            ['id' => 'shop_enabled', 'title' => 'Enable', 'default' => $wordpress ? 'yes' : true, 'property' => 'enabled', 'php_type' => 'bool'] + ($wordpress ? ['type' => 'checkbox'] : []),
            ['id' => 'shop_timeout', 'title' => 'Timeout', 'default' => 30, 'rules' => ['integer'], 'property' => 'timeout', 'php_type' => 'int'] + ($wordpress ? ['type' => 'number'] : []),
            ['id' => 'shop_ratio', 'title' => 'Ratio', 'default' => 1.5, 'property' => 'ratio', 'php_type' => 'float'] + ($wordpress ? ['type' => 'number'] : []),
            ['id' => 'shop_tags', 'title' => 'Tags', 'type' => 'multiselect', 'default' => ['a'], 'property' => 'tags', 'php_type' => 'array'],
            ['id' => 'shop_hint', 'type' => 'info', 'text' => 'Hint'],
            ['type' => 'title', 'id' => 'sub', 'title' => 'Sub-heading'],
            ['id' => 'shop_token', 'property' => 'secret', 'php_type' => 'string', 'ui' => false] + ($wordpress ? ['type' => 'text'] : []),
            ['type' => 'sectionend', 'id' => 'api'],
        ];
        $options = ['adapter' => $adapter, 'strip_prefixes' => ['shop_']];

        $this->assertSame($this->generate($flat, $options), $this->generate($structured, $options));
    }

    public function testSectionWithoutIdAndInference(): void
    {
        $module = $this->load(['sections' => [[
            'title' => 'Newsletter',
            'fields' => [
                ['id' => 'newsletter-max_items', 'default' => '10'],
                ['id' => 'lists', 'default' => []],
                ['id' => 'off', 'type' => 'checkbox', 'default' => false],
            ],
        ]]]);

        $this->assertSame(['type' => 'title', 'title' => 'Newsletter'], $module->entries[0]);
        $this->assertSame(['type' => 'sectionend'], $module->entries[4]);
        $this->assertSame(['newsletterMaxItems', 'lists', 'off'], array_map(static fn ($s) => $s->property, $module->settings));
        $this->assertSame([PhpType::String, PhpType::Array, PhpType::Bool], array_map(static fn ($s) => $s->type, $module->settings));
        $this->assertSame('text', $module->entries[1]['type']);
        $this->assertArrayNotHasKey('type', $module->entries[2]);
        $this->assertSame('no', $module->entries[3]['default']);
    }

    public function testKeyAliasesAndSourceEntries(): void
    {
        $module = $this->load(
            ['fields' => [['key' => 'api.base_url', 'default' => '']]],
            ['adapter' => 'laravel', 'key_aliases' => ['key' => 'id']],
        );

        $this->assertSame('apiBaseUrl', $module->settings[0]->property);
        $this->assertSame([['key' => 'api.base_url', 'default' => '']], $module->sourceEntries);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalid(): iterable
    {
        yield 'unknown root key' => [['fields' => [], 'groups' => []], 'root.groups: unknown key'];
        yield 'no fields or sections' => [['$schema' => 'x.json'], 'root: expected "fields" and/or "sections"'];
        yield 'fields not a list' => [['fields' => ['a' => 1]], 'fields: expected a list of field objects'];
        yield 'field not an object' => [['fields' => ['x']], 'fields[0]: expected an object'];
        yield 'sections not a list' => [['sections' => 'x'], 'sections: expected a list of section objects'];
        yield 'section not an object' => [['sections' => [[1, 2]]], 'sections[0]: expected an object'];
        yield 'section without fields' => [['sections' => [['title' => 'A']]], 'sections[0].fields: required'];
        yield 'field error path' => [
            ['sections' => [['title' => 'A', 'fields' => [['id' => 'a', 'default' => 1], ['id' => 'b', 'php_type' => 'int', 'default' => 'x']]]]],
            'sections[0].fields[1].default: expected a number',
        ];
        yield 'invalid derived property' => [['fields' => [['id' => '1st']]], 'fields[0].property: "1st" is not a valid PHP property name'];
        yield 'empty derived property' => [['fields' => [['id' => '--']]], 'fields[0].property: expected a non-empty string'];
        yield 'duplicate derived property' => [['fields' => [['id' => 'a_b'], ['id' => 'a-b']]], 'fields[1].property: duplicate property "aB" (first at [0])'];
    }

    #[DataProvider('invalid')]
    public function testInvalidStructuredSchema(mixed $schema, string $error): void
    {
        try {
            $this->load($schema);
            $this->fail('Expected SchemaException');
        } catch (SchemaException $e) {
            $this->assertStringContainsString($error, $e->getMessage());
        }
    }

    public function testHelpers(): void
    {
        $this->assertSame('apiKey', SchemaNormalizer::camelCase('api_key'));
        $this->assertSame('maxRetries', SchemaNormalizer::camelCase('max-retries'));
        $this->assertSame('httpTimeout', SchemaNormalizer::camelCase('http.timeout'));
        $this->assertSame('', SchemaNormalizer::camelCase('__'));
        $this->assertSame(PhpType::Float, SchemaNormalizer::inferType(0.5));
        $this->assertSame(PhpType::String, SchemaNormalizer::inferType(null));
        $this->assertTrue(SchemaNormalizer::isStructured(['fields' => []]));
        $this->assertFalse(SchemaNormalizer::isStructured([]));
        $this->assertFalse(SchemaNormalizer::isStructured([['id' => 'a']]));
    }
}
