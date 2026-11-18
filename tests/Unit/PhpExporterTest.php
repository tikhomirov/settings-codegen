<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Php\ExportStyle;
use Tikhomirov\SettingsCodegen\Php\PhpExporter;

final class PhpExporterTest extends TestCase
{
    public function testScalars(): void
    {
        $e = new PhpExporter();

        $this->assertSame('null', $e->value(null));
        $this->assertSame('true', $e->value(true));
        $this->assertSame('42', $e->value(42));
        $this->assertSame('0.0', $e->value(0.0));
        $this->assertSame('1.5', $e->value(1.5));
        $this->assertSame("'it\\'s \\\\ ok'", $e->value('it\'s \\ ok'));
        $this->assertSame('[]', $e->value([]));
    }

    public function testMinimalString(): void
    {
        $e = new PhpExporter();

        $this->assertSame("'plain \$x'", $e->minimalString('plain $x'));
        $this->assertSame('"it\'s \\$x"', $e->minimalString('it\'s $x'));
        $this->assertSame("'say \"hi\" it\\'s'", $e->minimalString('say "hi" it\'s'));
    }

    public function testListsAndMaps(): void
    {
        $this->assertSame("[\n    'a',\n    'b',\n]", (new PhpExporter())->value(['a', 'b']));
        $this->assertSame("[\n    0 => 'a',\n]", (new PhpExporter(new ExportStyle(listKeys: true)))->value(['a']));
        $this->assertSame("[\n    'k' => [\n    ],\n]", (new PhpExporter(new ExportStyle(emptyArray: 'multiline')))->value(['k' => []]));
        $this->assertSame('[]', (new PhpExporter(new ExportStyle(emptyArray: 'multiline')))->value([], inlineEmpty: true));
    }

    public function testArrowAlignmentGroupsEndAtMultilineValues(): void
    {
        $e = new PhpExporter(new ExportStyle(alignArrows: true));
        $code = $e->value(['id' => 1, 'default' => ['x' => 1], 'class' => 'c', 'custom_attributes' => 2]);

        $expected = <<<'PHP'
[
    'id'      => 1,
    'default' => [
        'x' => 1,
    ],
    'class'             => 'c',
    'custom_attributes' => 2,
]
PHP;
        $this->assertSame($expected, $code);
    }

    public function testRejectsObjects(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new PhpExporter())->value(new \stdClass());
    }

    public function testUnknownEmptyStyle(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ExportStyle(emptyArray: 'weird');
    }
}
