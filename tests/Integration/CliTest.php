<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tikhomirov\SettingsCodegen\Cli\Application;
use Tikhomirov\SettingsCodegen\Tests\Support\TempProject;

final class CliTest extends TestCase
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

    public function testCheckFailsUntilGeneratedThenPasses(): void
    {
        [$code, , $err] = $this->run_('--check', '--config=laravel.json');
        $this->assertSame(Application::EXIT_OUTDATED, $code);
        $this->assertStringContainsString('outdated (6 of 6 files)', $err);

        [$code, $out] = $this->run_('generate', '--config=laravel.json');
        $this->assertSame(Application::EXIT_OK, $code);
        $this->assertStringContainsString('Generated 6 changed of 6 files from 3 schemas.', $out);

        [$code, $out] = $this->run_('check', '--config=laravel.json');
        $this->assertSame(Application::EXIT_OK, $code);
        $this->assertStringContainsString('up to date', $out);
    }

    public function testCheckDetectsManualEditAndSchemaChange(): void
    {
        $this->run_('--config=yii3.json', '-q');
        $file = $this->project->path('build/yii3/Delivery/DeliveryConfig.php');
        file_put_contents($file, file_get_contents($file) . "// edited\n");

        [$code, , $err] = $this->run_('--check', '--config=yii3.json');
        $this->assertSame(Application::EXIT_OUTDATED, $code);
        $this->assertStringContainsString('DeliveryConfig.php', $err);

        $this->run_('--config=yii3.json', '-q');
        $this->assertSame(Application::EXIT_OK, $this->run_('--check', '--config=yii3.json')[0]);

        $schema = $this->project->path('modules/Notes/settings.json');
        file_put_contents($schema, str_replace('"notes_info",', '"notes_info", "property": "info",', (string) file_get_contents($schema)));
        $this->assertSame(Application::EXIT_OUTDATED, $this->run_('--check', '--config=yii3.json')[0]);
    }

    public function testInvalidSchemaIsAnError(): void
    {
        $this->project->write('modules/Broken/settings.json', '[{"property": "x"}]');

        [$code, , $err] = $this->run_('--config=symfony.json');
        $this->assertSame(Application::EXIT_ERROR, $code);
        $this->assertStringContainsString('"property" requires "id"', $err);
        $this->assertStringContainsString('modules/Broken/settings.json', $err);
    }

    public function testReadsComposerExtraByDefault(): void
    {
        $this->project->write('composer.json', json_encode([
            'name' => 'fixture/app',
            'extra' => ['settings-codegen' => [
                'adapter' => 'laravel',
                'schemas' => ['modules/Notes/settings.json'],
                'namespace' => 'App\\Settings',
                'output' => 'generated',
            ]],
        ], JSON_THROW_ON_ERROR));

        [$code] = $this->run_('--working-dir=' . $this->project->dir, '-q');
        $this->assertSame(Application::EXIT_OK, $code);
        $this->assertFileExists($this->project->path('generated/NotesConfig.php'));
    }

    public function testUnknownArgumentAndHelp(): void
    {
        $this->assertSame(Application::EXIT_ERROR, $this->run_('--nope')[0]);
        $this->assertSame(Application::EXIT_ERROR, $this->run_('build')[0]);
        [$code, $out] = $this->run_('--help');
        $this->assertSame(Application::EXIT_OK, $code);
        $this->assertStringContainsString('Usage: settings-codegen', $out);
    }

    public function testBinaryExitCode(): void
    {
        $bin = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__, 2) . '/bin/settings-codegen');
        exec($bin . ' --check --config=' . escapeshellarg($this->project->path('wordpress.json')) . ' 2>&1', $output, $code);
        $this->assertSame(1, $code, implode("\n", $output));
    }

    /**
     * @return array{int, string, string}
     */
    private function run_(string ...$args): array
    {
        $out = fopen('php://memory', 'w+');
        $err = fopen('php://memory', 'w+');
        $this->assertIsResource($out);
        $this->assertIsResource($err);

        $cwd = getcwd();
        chdir($this->project->dir);
        try {
            $code = (new Application($out, $err))->run(array_values($args));
        } finally {
            chdir((string) $cwd);
        }

        rewind($out);
        rewind($err);

        return [$code, (string) stream_get_contents($out), (string) stream_get_contents($err)];
    }
}
