<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Codegen;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Schema\SchemaException;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class MoonShineAdapterTest extends TestCase
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
    private function fields(mixed $schema, array $options = []): string
    {
        $this->project->write('x/Demo/settings.json', (string) json_encode($schema));
        $files = (new Codegen(new CodegenConfig($this->project->dir, ['adapter' => 'moonshine', 'schemas' => ['x/*/settings.json'], ...$options])))->build();
        $this->assertCount(3, $files);
        $this->assertMatchesRegularExpression('#/Demo(Fields|Form)\.php$#', $files[2]->path);

        return $files[2]->contents;
    }

    public function testFieldsetWithoutTranslationAndPropertyNames(): void
    {
        $code = $this->fields(
            ['sections' => [['title' => false, 'fields' => [
                ['id' => 'size', 'title' => 'Size', 'default' => 3, 'options' => ['1' => 1, '3' => 3]],
                ['id' => 'mode', 'type' => 'radio', 'options' => ['a' => 'A']],
            ]]]],
            ['section' => 'fieldset', 'translate' => '', 'field_name' => 'form[{property}]', 'fields_suffix' => 'Form'],
        );

        $this->assertStringContainsString("use MoonShine\\UI\\Fields\\Fieldset;\n", $code);
        $this->assertStringContainsString("Fieldset::make('', [", $code);
        $this->assertStringContainsString("Select::make('Size', 'form[size]')", $code);
        $this->assertStringContainsString("1 => 1,\n", $code);
        $this->assertStringContainsString("Select::make('mode', 'form[mode]')", $code);
        $this->assertStringNotContainsString('->multiple()', $code);
    }

    public function testSectionEndAndUiSectionGrouping(): void
    {
        $code = $this->fields([
            ['id' => 'a', 'property' => 'a', 'ui' => ['section' => 'One']],
            ['id' => 'b', 'property' => 'b', 'ui' => ['section' => 'One']],
            ['type' => 'title', 'title' => 'Two'],
            ['id' => 'c', 'property' => 'c', 'ui' => ['section' => 'Ignored inside an explicit section']],
            ['type' => 'sectionend'],
            ['id' => 'd', 'property' => 'd'],
            ['type' => 'info', 'text' => 'not a field'],
        ]);

        $this->assertSame(1, substr_count($code, "Box::make(\\__('One'), ["));
        $this->assertStringContainsString("Box::make(\\__('Two'), [\n                Text::make(\\__('c'), 'c'),\n            ]),\n            Text::make(\\__('d'), 'd'),", $code);
        $this->assertStringNotContainsString('Ignored', $code);
    }

    public function testUnknownFieldIsAnError(): void
    {
        $this->expectException(SchemaException::class);
        $this->expectExceptionMessage('"a": unknown MoonShine field "slider"');

        $this->fields([['id' => 'a', 'property' => 'a', 'ui' => ['field' => 'slider']]]);
    }

    public function testInvalidSectionOption(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Option "section" must be one of: box, fieldset');

        $this->fields([['id' => 'a', 'property' => 'a']], ['section' => 'tabs']);
    }
}
