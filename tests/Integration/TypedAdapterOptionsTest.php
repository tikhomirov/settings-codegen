<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

/**
 * "string_cast" and "definition_fields" options of the typed adapters (Laravel, Symfony, Yii3).
 */
final class TypedAdapterOptionsTest extends TestCase
{
    private const SCHEMA = <<<'JSON'
[
    {"key": "legacy.title", "property": "title", "type": "string", "default": "Shop", "group": "legacy"},
    {"key": "legacy.limit", "property": "limit", "type": "int", "default": 5, "group": "legacy"}
]
JSON;

    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->project->write('legacy/Legacy/settings.json', self::SCHEMA);
    }

    protected function tearDown(): void
    {
        $this->project->remove();
    }

    public function testDefaultsKeepNormalizedFieldsAndScalarStrings(): void
    {
        $files = $this->generate([]);

        $this->assertStringContainsString("'id' => 'legacy.title'", $files['LegacyDefinition.php']);
        $this->assertStringContainsString("'php_type' => 'string'", $files['LegacyDefinition.php']);
        $this->assertStringContainsString('return is_scalar($value) ? (string) $value : $default;', $files['LegacyConfig.php']);
    }

    public function testSourceFieldsKeepKeysAsWritten(): void
    {
        $definition = $this->generate(['definition_fields' => 'source'])['LegacyDefinition.php'];

        $this->assertStringContainsString("'key' => 'legacy.title'", $definition);
        $this->assertStringContainsString("'type' => 'string'", $definition);
        $this->assertStringNotContainsString("'php_type'", $definition);
        // defaults() and keys() still come from the normalized schema.
        $this->assertStringContainsString("'title' => 'legacy.title'", $definition);
    }

    public function testNonEmptyStringCastFallsBackToDefault(): void
    {
        $config = $this->generate(['string_cast' => 'non_empty_string', 'namespace' => 'Fixture\\NonEmpty'])['LegacyConfig.php'];

        $this->assertStringContainsString("return is_string(\$value) && \$value !== '' ? \$value : \$default;", $config);

        if (PHP_VERSION_ID < 80200) {
            return;
        }

        require_once $this->project->path('build/options/LegacyConfig.php');
        $class = 'Fixture\\NonEmpty\\LegacyConfig';

        $this->assertSame('Shop', $class::fromArray(['legacy.title' => ''])->title);
        $this->assertSame('Shop', $class::fromArray(['legacy.title' => 42])->title);
        $this->assertSame('Cafe', $class::fromArray(['legacy.title' => 'Cafe'])->title);
    }

    public function testStringCastAcceptsOnlyStrings(): void
    {
        $config = $this->generate(['string_cast' => 'string'])['LegacyConfig.php'];

        $this->assertStringContainsString('return is_string($value) ? $value : $default;', $config);
    }

    public function testUnknownChoiceIsRejected(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Option "string_cast" must be one of: scalar, string, non_empty_string');

        $this->generate(['string_cast' => 'trim']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, string>
     */
    private function generate(array $options): array
    {
        $config = $options + [
            'adapter' => 'laravel',
            'schemas' => ['legacy/*/settings.json'],
            'namespace' => 'Fixture\\Options',
            'output' => 'build/options',
            'key_aliases' => ['key' => 'id', 'type' => 'php_type'],
        ];
        $this->project->write('options.json', (string) json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        Codegen::fromFile($this->project->path('options.json'))->generate();

        return $this->project->files('build/options');
    }
}
