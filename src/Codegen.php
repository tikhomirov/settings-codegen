<?php

declare(strict_types=1);

namespace Tikhomirov\SettingsCodegen;

use Tikhomirov\SettingsCodegen\Adapter\AdapterRegistry;
use Tikhomirov\SettingsCodegen\Config\CodegenConfig;
use Tikhomirov\SettingsCodegen\Config\ConfigException;
use Tikhomirov\SettingsCodegen\Extension\ExtensionInterface;
use Tikhomirov\SettingsCodegen\PostStep\PostStepInterface;
use Tikhomirov\SettingsCodegen\Schema\SchemaException;
use Tikhomirov\SettingsCodegen\Schema\SchemaLoader;

/**
 * Entry point: load schemas, run the adapter, extensions and post steps, then write or check.
 */
final class Codegen
{
    public function __construct(private readonly CodegenConfig $config)
    {
    }

    public static function fromFile(string $file): self
    {
        return new self(CodegenConfig::fromFile($file));
    }

    /**
     * Build all files in memory without touching the disk.
     *
     * @return list<GeneratedFile>
     */
    public function build(): array
    {
        foreach ($this->config->bootstrapFiles() as $file) {
            if (! is_file($file)) {
                throw new ConfigException("Bootstrap file not found: {$file}");
            }
            require_once $file;
        }

        $adapter = AdapterRegistry::create($this->config->adapter());
        $context = new Context($this->config, $this->extensions());
        $modules = (new SchemaLoader($this->config))->loadAll();
        $files = [];

        foreach ($modules as $module) {
            $errors = [];
            foreach ($context->extensions as $extension) {
                array_push($errors, ...$extension->validate($module));
            }
            if ($errors !== []) {
                throw SchemaException::invalid($module->relativeSourcePath, $errors);
            }

            array_push($files, ...$adapter->generate($module, $context));
        }

        foreach ($this->config->postSteps() as $step) {
            array_push($files, ...$this->postStep($step['class'])->run($modules, $context, $step['options']));
        }

        $paths = [];
        foreach ($files as $file) {
            if (isset($paths[$file->path])) {
                throw new ConfigException("Two generated files share the path {$file->path}");
            }
            $paths[$file->path] = true;
        }

        return $files;
    }

    /**
     * Write files whose contents changed.
     */
    public function generate(): Result
    {
        $files = $this->build();
        $changed = $this->diff($files);

        foreach ($files as $file) {
            if (! in_array($file->path, $changed, true)) {
                continue;
            }

            $dir = dirname($file->path);
            if (! is_dir($dir) && ! @mkdir($dir, 0o775, true) && ! is_dir($dir)) {
                throw new \RuntimeException("Cannot create directory {$dir}");
            }

            if (@file_put_contents($file->path, $file->contents) === false) {
                throw new \RuntimeException("Cannot write {$file->path}");
            }
        }

        return new Result($files, $changed, $this->countModules());
    }

    /**
     * Report files that are missing or differ, without writing.
     */
    public function check(): Result
    {
        $files = $this->build();

        return new Result($files, $this->diff($files), $this->countModules());
    }

    /**
     * @param list<GeneratedFile> $files
     *
     * @return list<string>
     */
    private function diff(array $files): array
    {
        $changed = [];

        foreach ($files as $file) {
            if (! is_file($file->path) || file_get_contents($file->path) !== $file->contents) {
                $changed[] = $file->path;
            }
        }

        return $changed;
    }

    private function countModules(): int
    {
        return count((new SchemaLoader($this->config))->discover());
    }

    /**
     * @return list<ExtensionInterface>
     */
    private function extensions(): array
    {
        $extensions = [];

        foreach ($this->config->extensions() as $class) {
            if (! class_exists($class) || ! is_subclass_of($class, ExtensionInterface::class)) {
                throw new ConfigException("Extension {$class} not found or does not implement " . ExtensionInterface::class);
            }
            $extensions[] = new $class();
        }

        return $extensions;
    }

    private function postStep(string $class): PostStepInterface
    {
        if (! class_exists($class) || ! is_subclass_of($class, PostStepInterface::class)) {
            throw new ConfigException("Post step {$class} not found or does not implement " . PostStepInterface::class);
        }

        return new $class();
    }
}
