<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Schema\SchemaNormalizer;
use Tikhomirov\SettingsCodegen\Schema\SchemaValidator;

final class SchemaValidatorTest extends TestCase
{
    public function testValidSchema(): void
    {
        $data = json_decode((string) file_get_contents(__DIR__ . '/../Fixtures/project/modules/Delivery/settings.json'), true);

        $this->assertSame([], (new SchemaValidator())->validate($data));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalid(): iterable
    {
        yield 'root object' => [['id' => 'x'], 'root: expected a JSON array'];
        yield 'entry not object' => [['x'], '[0]: expected an object'];
        yield 'property without id' => [[['property' => 'a']], '"property" requires "id"'];
        yield 'bad property' => [[['id' => 'a', 'property' => '1a']], 'not a valid PHP property name'];
        yield 'duplicate property' => [[['id' => 'a', 'property' => 'a'], ['id' => 'b', 'property' => 'a']], 'duplicate property "a"'];
        yield 'duplicate id' => [[['id' => 'a', 'property' => 'a'], ['id' => 'a', 'property' => 'b']], 'duplicate id "a"'];
        yield 'bad php_type' => [[['id' => 'a', 'property' => 'a', 'php_type' => 'object']], 'php_type: expected one of'];
        yield 'bad args' => [[['id' => 'a', 'desc_args_expr' => 'x']], 'desc_args_expr: expected a list'];
        yield 'non-string arg' => [[['id' => 'a', 'text_args_expr' => ['ok', 1]]], 'text_args_expr: expected a list'];
        yield 'non-string property' => [[['id' => 'a', 'property' => 5]], 'property: expected a non-empty string'];
        yield 'empty expr' => [[['id' => 'a', 'options_expr' => '']], 'options_expr: expected a non-empty string'];
        yield 'bad ui' => [[['id' => 'a', 'ui' => 'no']], 'ui: expected a boolean or an object'];
        yield 'int default' => [[['id' => 'a', 'property' => 'a', 'php_type' => 'int', 'default' => 'x']], 'expected a number'];
        yield 'array default' => [[['id' => 'a', 'property' => 'a', 'php_type' => 'array', 'default' => '']], 'expected an array'];
        yield 'bool default' => [[['id' => 'a', 'property' => 'a', 'php_type' => 'bool', 'default' => 'maybe']], 'expected a boolean'];
        yield 'string default' => [[['id' => 'a', 'property' => 'a', 'default' => ['x']]], 'expected a scalar'];
    }

    #[DataProvider('invalid')]
    public function testInvalidSchema(mixed $data, string $error): void
    {
        $errors = (new SchemaValidator())->validate($data);

        $this->assertNotSame([], $errors);
        $this->assertStringContainsString($error, implode("\n", $errors));
    }

    public function testNonStrictDefaultsAndNullDefault(): void
    {
        $data = [
            ['id' => 'a', 'property' => 'a', 'php_type' => 'array', 'default' => ''],
            ['id' => 'b', 'property' => 'b', 'php_type' => 'int', 'default' => null],
        ];

        $this->assertSame([], (new SchemaValidator(strictDefaults: false))->validate($data));
        $this->assertCount(1, (new SchemaValidator())->validate($data));
    }

    public function testJsonSchemaFileDocumentsTheSameKeys(): void
    {
        $schema = json_decode((string) file_get_contents(__DIR__ . '/../../resources/settings.schema.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($schema);
        $field = $schema['$defs']['field']['properties'];
        $this->assertSame(['string', 'int', 'float', 'bool', 'array'], $field['php_type']['enum']);
        foreach (['id', 'property', 'default', 'options_expr', 'depends_expr', 'desc_args_expr', 'text_args_expr', 'ui', 'rules'] as $key) {
            $this->assertArrayHasKey($key, $field);
        }
        $this->assertSame(['$schema', 'fields', 'sections'], array_keys($schema['oneOf'][0]['properties']));
        $this->assertSame(SchemaNormalizer::ROOT_KEYS, array_keys($schema['oneOf'][0]['properties']));
        $this->assertSame(['fields'], $schema['$defs']['section']['required']);
    }
}
