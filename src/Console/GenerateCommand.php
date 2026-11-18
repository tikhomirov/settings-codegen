<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Tikhomirov\SettingsCodegen\Cli\Application;
use Tikhomirov\SettingsCodegen\Codegen;

/**
 * "settings:generate" for symfony/console based applications (Symfony bundle, Yii3 console).
 *
 * Requires symfony/console (suggested, not required by the package).
 */
#[AsCommand(name: 'settings:generate', description: 'Generate typed settings classes from settings.json schemas')]
final class GenerateCommand extends Command
{
    public function __construct(private readonly ?string $projectDir = null)
    {
        parent::__construct('settings:generate');
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generate typed settings classes from settings.json schemas')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Do not write; fail when generated files are outdated')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'composer.json or settings-codegen.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir ?? (string) getcwd();
        $config = $input->getOption('config');

        try {
            $codegen = Codegen::fromFile(Application::resolveConfigFile($dir, is_string($config) ? $config : null));
            $check = (bool) $input->getOption('check');
            $result = $check ? $codegen->check() : $codegen->generate();
        } catch (Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Application::EXIT_ERROR;
        }

        $total = count($result->files);

        if ($check && ! $result->isUpToDate()) {
            $output->writeln('<error>Generated settings are outdated (' . count($result->changed) . " of {$total} files).</error>");
            foreach ($result->changed as $path) {
                $output->writeln("  - {$path}");
            }

            return Application::EXIT_OUTDATED;
        }

        foreach ($check ? [] : $result->changed as $path) {
            $output->writeln("  written {$path}", OutputInterface::VERBOSITY_VERBOSE);
        }

        $output->writeln($check
            ? "Generated settings are up to date ({$total} files)."
            : 'Generated ' . count($result->changed) . " changed of {$total} files.");

        return Application::EXIT_OK;
    }
}
