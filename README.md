<p align="center"><img src="docs/assets/logo.jpg" alt="settings-codegen: a settings array turned into a typed Config" width="100%"></p>

<p align="center"><b>Describe settings once in <code>settings.json</code> — get typed PHP classes, admin UI fields and validation, checked in CI.</b></p>

<p align="center">

[![CI](https://github.com/tikhomirov/settings-codegen/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/tikhomirov/settings-codegen/actions/workflows/ci.yml)
[![Coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://github.com/tikhomirov/settings-codegen/actions/workflows/ci.yml)
[![PHPStan level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen)](phpstan.neon.dist)
[![PHP 8.1–8.5](https://img.shields.io/badge/PHP-8.1%20%E2%80%93%208.5-777bb4)](composer.json)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

</p>

# settings-codegen

## The problem

Settings in a growing PHP project tend to look like this:

```php
$restTime = get_option('iiko_rest_time', '00:30');                 // mixed
$enabled  = get_option('iiko_enable_calendar', 'yes') === 'yes';   // "yes", "1", true, "on"…?
$interval = (int) get_option('iiko_time_slot_interval', 30);       // cast again, everywhere
```

- **String keys everywhere.** A typo like `'iiko_rest_tiem'` silently returns the default; PHPStan can't see it.
- **Everything is `mixed`.** `get_option()` / `config()` return `"0"`, `0`, `false` or `"no"`, so every call site casts by hand.
- **Defaults are duplicated.** `'00:30'` lives in the code *and* in the admin form definition. Change one, forget the other.
- **A global `SettingsKey` constants class** gives autocomplete for key names only, stays `mixed`, and becomes a God Object every module depends on.
- **UI and code drift apart.** The admin form, the validation rules and the code that reads the values are three separate lists.

## The solution: schema first

**1. Describe the module once**, next to its code — `src/Features/DeliveryCalendar/settings.json`:

```json
[
    {"id": "iiko_calendar_title", "type": "title", "title": "Delivery calendar"},
    {"id": "iiko_enable_calendar", "property": "enableCalendar", "php_type": "bool",
     "type": "checkbox", "title": "Enable delivery time picker", "default": "yes"},
    {"id": "iiko_rest_time", "property": "restTime",
     "type": "text", "title": "Order preparation time", "default": "00:30"},
    {"id": "iiko_time_slot_duration", "property": "timeSlotDuration", "php_type": "int",
     "type": "number", "title": "Time slot, minutes", "default": 30},
    {"id": "iiko_calendar_end", "type": "sectionend"}
]
```

**2. Generate:**

```bash
vendor/bin/settings-codegen generate
```

**3. Get plain, committed PHP** (real output of the `wordpress` adapter, shortened):

```php
final readonly class DeliveryCalendarConfig
{
    public function __construct(
        public bool $enableCalendar,
        public string $restTime,
        public int $timeSlotDuration,
    ) {}

    public static function load(): self
    {
        return new self(
            enableCalendar: self::asBool(\get_option('iiko_enable_calendar', 'yes')),
            restTime: (string) \get_option('iiko_rest_time', '00:30'),
            timeSlotDuration: (int) \get_option('iiko_time_slot_duration', 30),
        );
    }
    // toArray(), asBool()
}

final class DeliveryCalendarUiSchema
{
    public static function getFields(): array   // WooCommerce Settings API, ready for the settings tab
    {
        return [
            // ...
            ['id' => 'iiko_rest_time', 'title' => \__('Order preparation time', 'acme'), 'type' => 'text', 'default' => '00:30'],
            // ...
        ];
    }
    // getAllOptionKeys(), optionIdFor()
}
```

**4. Use typed values with full autocomplete:**

```php
$config = DeliveryCalendarConfig::load();

if ($config->enableCalendar) {             // bool, not "yes"
    $slots->build($config->restTime, $config->timeSlotDuration);
}

$config->restTme;                          // PHPStan: Access to an undefined property

add_filter('woocommerce_get_settings_acme', fn () => DeliveryCalendarUiSchema::getFields());
```

Business code depends on a plain object, so unit tests need no database and no WordPress:

```php
public function test_calculates_slots(): void
{
    $config = new DeliveryCalendarConfig(enableCalendar: true, restTime: '01:00', timeSlotDuration: 30);

    $calculator = new CalendarSlotCalculator($config);   // your service
    $slots = $calculator->calculateForDay(new DateTimeImmutable('2026-10-15'));

    $this->assertCount(16, $slots);
}
```

**5. Keep it honest in CI:** `vendor/bin/settings-codegen generate --check` exits with `1` when
someone changed `settings.json` and forgot to regenerate.

```mermaid
flowchart LR
    S["settings.json<br/>(one per module)"] --> G["settings-codegen generate"]
    G --> C["*Config<br/>typed readonly DTO"]
    G --> U["*UiSchema<br/>WooCommerce fields + __()"]
    G --> D["*Definition<br/>fields / defaults / rules"]
    C --> A["Your code<br/>$config->restTime"]
    U --> W["Admin settings page"]
    D --> V["Validation, forms"]
    S -. "generate --check" .-> CI["CI fails on drift"]
```

## Why use it

- **Single source of truth.** Key, type, default, label and validation rules of a setting live in one JSON entry.
- **Type-safe DTOs.** `bool`, `int`, `float`, `string`, `array` properties in a `final readonly` class instead of `mixed`.
- **100% IDE autocomplete** for every setting — no string keys in business code.
- **PHPStan catches typos**: a misspelled property is an error, a misspelled string key never was.
- **Defaults and validation in one place**: `UiSchema::getFields()` / `Definition::defaults()` and `rules()` come from the same schema as the Config class.
- **CI drift check** with `generate --check` (exit codes `0` / `1` / `2`).
- **Zero runtime overhead.** A dev-only dependency: production runs plain generated PHP, cached by OPcache — no JSON parsing, no reflection.
- **Framework-agnostic.** Adapters for WordPress/WooCommerce, Laravel, Symfony and Yii3, or your own `AdapterInterface`.

Read the full rationale (in Russian) →
[“PHP settings architecture: why constants are a dead end, and why JSON”](https://rwsite.ru/php-settings-architecture-why-constants-are-a-dead-end-and-why-json/)

## Requirements

- PHP 8.1+, tested on 8.1, 8.2, 8.3, 8.4 and 8.5 (generated `readonly class` needs 8.2; set
  `"readonly_class": false` for 8.1).
- No runtime dependencies. Framework packages are only suggested.
- Deterministic output: stable order and formatting. You don't need a code style fixer, but the
  output can match one (see `style`).

## Contents

- [The problem](#the-problem)
- [The solution: schema first](#the-solution-schema-first)
- [Why use it](#why-use-it)
- [Requirements](#requirements)
- [Installation](#installation)
- [The schema](#the-schema)
- [Configuration](#configuration)
- [Command line](#command-line)
- [WordPress / WooCommerce](#wordpress--woocommerce)
- [Laravel](#laravel)
- [Symfony](#symfony)
- [Yii3](#yii3)
- [Custom reader](#custom-reader)
- [Extensions and post steps](#extensions-and-post-steps)
- [CI](#ci)
- [For AI agents](#for-ai-agents)
- [Development](#development)

## Installation

```bash
composer require --dev tikhomirov/settings-codegen
```

The package is not on Packagist yet. Until then, add the GitHub repository first:

```bash
composer config repositories.settings-codegen vcs https://github.com/tikhomirov/settings-codegen
composer require --dev tikhomirov/settings-codegen:dev-main
```

`require-dev` is enough: only the generated classes are used at runtime.

## The schema

`settings.json` is a JSON array of entries. An entry with both `id` and `property` becomes a typed
property. Other entries (titles, section ends, info blocks) appear only in UI schemas. Unknown keys
are allowed: the WordPress adapter passes them through to WooCommerce.

```json
[
    {"id": "shop_delivery_title", "type": "title", "title": "Delivery"},
    {
        "id": "shop_enable_calendar",
        "property": "enableCalendar",
        "php_type": "bool",
        "type": "checkbox",
        "title": "Enable delivery calendar",
        "default": "yes"
    },
    {
        "id": "shop_slot_minutes",
        "property": "slotMinutes",
        "php_type": "int",
        "type": "number",
        "title": "Slot length, minutes",
        "default": 30,
        "rules": ["integer", "min:5"]
    },
    {"id": "shop_delivery_end", "type": "sectionend"}
]
```

| Key | Meaning |
| --- | --- |
| `id` | Storage key / option id, unique in the module. `strip_prefixes` removes prefixes from it for reading. |
| `property` | Property name in the Config class. |
| `php_type` | `string` (default), `int`, `float`, `bool`, `array`. |
| `default` | Default value. With `strict_defaults` (on by default), it must match `php_type`. |
| `ui` | `false` keeps a setting out of the WordPress UI schema. |
| `rules` | Passed through to `Definition::rules()` (Laravel, Symfony, Yii3). |
| `desc_args_expr`, `text_args_expr` | WordPress: PHP expressions for `sprintf()` with the translated text. |
| `options_expr`, `depends_expr` | WordPress: PHP expressions emitted as `options` / `depends`. |

The JSON Schema for IDEs is in `vendor/tikhomirov/settings-codegen/resources/settings.schema.json`.
The generator validates the same rules itself.

## Configuration

Put the configuration in `composer.json` under `extra.settings-codegen`, or in a standalone
`settings-codegen.json`:

```json
{
    "extra": {
        "settings-codegen": {
            "adapter": "wordpress",
            "schemas": ["src/Features/*/settings.json"],
            "namespace": "Acme\\Features\\{module}\\Generated",
            "output": "{dir}/Generated"
        }
    },
    "scripts": {
        "settings:generate": "settings-codegen generate",
        "settings:check": "settings-codegen generate --check"
    }
}
```

The module name is the directory that holds `settings.json`. Without `--config`, the CLI reads
`settings-codegen.json` from the working directory and falls back to `composer.json`.

| Option | Default | Description |
| --- | --- | --- |
| `adapter` | `wordpress` | `wordpress`, `laravel`, `symfony`, `yii3`, or the FQCN of an `AdapterInterface`. |
| `schemas` | `["settings.json"]` | Glob patterns relative to the project. `**` is supported. |
| `namespace` | `App\Settings\Generated` | Namespace of the generated classes. `{module}` is replaced. |
| `output` | `{dir}/Generated` | Output directory. `{dir}` is the schema's directory, `{module}` the module name. |
| `module_names` | `{}` | Override module names: `{"app/Settings/settings.json": "General"}`. |
| `strip_prefixes` | `[]` | Prefixes removed (repeatedly) from `id` to get the storage key. |
| `key_aliases` | `{}` | Rename schema keys before validation, e.g. `{"key": "id", "type": "php_type"}`. |
| `strict_defaults` | `true` | Check that `default` matches `php_type`. |
| `readonly_class` | `true` | `final readonly class` (8.2+) or `final class` with readonly properties (8.1). |
| `style` | see below | Formatting of generated code. |
| `docs` | English | Docblock lines per class/method (`{module}`, `{source}` and `{file}` placeholders). |
| `bootstrap` | `[]` | PHP files to require first (e.g. classes for your extensions). |
| `extensions` | `[]` | FQCNs of `ExtensionInterface` implementations. |
| `post_steps` | `[]` | FQCNs or `{"class": ..., "options": {...}}` of `PostStepInterface` implementations. |

`style`:

```json
{"align_arrows": false, "list_keys": false, "empty_array": "inline", "indent": "    "}
```

`align_arrows` aligns `=>` like php-cs-fixer/Pint `align_single_space_minimal`.
`list_keys: true` and `"empty_array": "multiline"` reproduce the `var_export()` + Pint look of
older generators byte for byte.

## Command line

```bash
vendor/bin/settings-codegen generate          # write changed files
vendor/bin/settings-codegen generate --check  # write nothing; exit 1 if anything is missing or outdated
vendor/bin/settings-codegen check             # same as generate --check
vendor/bin/settings-codegen generate --config=settings-codegen.json --working-dir=path/to/project
vendor/bin/settings-codegen generate -q       # print errors only
```

| Exit code | Meaning |
| --- | --- |
| `0` | Everything is generated and up to date. |
| `1` | `--check` only: some generated files are missing or outdated (listed in the output). |
| `2` | Error: invalid schema, invalid configuration, unknown argument, or a file cannot be written. |

Generated files are output, not source: never edit them by hand. Change `settings.json` (or the
configuration) and run `generate` again.

## WordPress / WooCommerce

```json
{
    "adapter": "wordpress",
    "schemas": ["src/Features/*/settings.json"],
    "namespace": "Acme\\Features\\{module}\\Generated",
    "option_reader": "get_option",
    "text_domain": "acme",
    "strip_prefixes": []
}
```

Each module gets `{Module}Config` and `{Module}UiSchema`:

```php
final readonly class DeliveryConfig
{
    public function __construct(
        public bool $enableCalendar,
        public int $slotMinutes,
    ) {}

    public static function load(): self
    {
        return new self(
            enableCalendar: self::asBool(\get_option('shop_enable_calendar', 'yes')),
            slotMinutes: (int) \get_option('shop_slot_minutes', 30),
        );
    }
    // toArray(), asBool()
}

// Business code depends on the DTO only:
$slots = new SlotCalculator(DeliveryConfig::load());

// WooCommerce settings tab:
add_filter('woocommerce_get_settings_acme', fn () => DeliveryUiSchema::getFields());
```

`UiSchema` also has `getAllOptionKeys()` (for export or reset, including `ui: false` options) and
`optionIdFor($property)`. Titles, descriptions, tips, placeholders and option labels are wrapped in
`__('…', 'text_domain')`, so `wp i18n make-pot` finds them. Options: `option_reader` (any function
`(string $key, mixed $default): mixed`), `text_domain`, `config_suffix` (`Config`), `schema_suffix`
(`UiSchema`).

## Laravel

```json
{
    "adapter": "laravel",
    "schemas": ["app/Settings/Modules/*/settings.json"],
    "namespace": "App\\Settings\\Generated",
    "output": "app/Settings/Generated",
    "key_prefix": "settings."
}
```

```php
// config/settings.php: ['enable_calendar' => env('DELIVERY_CALENDAR', true), ...]
$config = DeliveryConfig::load(app('config'));      // reads settings.enable_calendar, ...
$config = DeliveryConfig::fromArray($row->values);  // e.g. JSON column from the database

$request->validate(DeliveryDefinition::rules());   // the "rules" key from settings.json
DeliveryDefinition::defaults();                     // storage key => typed default
DeliveryDefinition::fields();                       // raw schema entries, e.g. to build an admin form
```

Values that are missing or have the wrong type fall back to the schema default.

Options of the typed adapters (`laravel`, `symfony`, `yii3`):

| Option | Default | Description |
| --- | --- | --- |
| `reader` | adapter specific | `{"type", "variable", "expression"}` of `load()`, see [Custom reader](#custom-reader). |
| `key_prefix` | `""` | Prepended to the storage key in `load()`. |
| `config_suffix` / `definition_suffix` | `Config` / `Definition` | Class name suffixes. |
| `definition` | `true` | Generate the Definition class. |
| `string_cast` | `scalar` | `scalar`: any scalar becomes a string; `string`: only strings; `non_empty_string`: only non-empty strings, `""` falls back to the default. |
| `definition_fields` | `normalized` | `Definition::fields()` entries after `key_aliases` (`normalized`) or exactly as written in `settings.json` (`source`). |

`definition_fields: "source"` together with `key_aliases` lets an existing schema with its own key
names (for example `key` / `type`) keep the same `fields()` output that runtime code already reads.

## Symfony

```json
{
    "adapter": "symfony",
    "schemas": ["src/*/Resources/settings.json"],
    "namespace": "App\\Settings\\{module}",
    "output": "src/{module}/Settings",
    "key_prefix": "app.settings."
}
```

```yaml
# config/services.yaml
parameters:
    app.settings.enable_calendar: true
    app.settings.slot_minutes: 15

services:
    App\Settings\Delivery\DeliveryConfig:
        factory: ['App\Settings\Delivery\DeliveryConfig', 'load']
        arguments: ['@parameter_bag']
```

The optional bundle adds a console command:

```php
// config/bundles.php
Tikhomirov\SettingsCodegen\Adapter\Symfony\SettingsCodegenBundle::class => ['dev' => true],
```

```bash
bin/console settings:generate [--check] [--config=settings-codegen.json]
```

## Yii3

```json
{
    "adapter": "yii3",
    "schemas": ["src/*/settings.json"],
    "namespace": "App\\{module}\\Settings",
    "output": "{dir}/Settings",
    "key_prefix": "app.settings."
}
```

```php
// config/common/params.php
return ['app' => ['settings' => ['enable_calendar' => true, 'slot_minutes' => 15]]];

// config/common/di/settings.php
use App\Delivery\Settings\DeliveryConfig;
use Yiisoft\Definitions\DynamicReference;

return [
    DeliveryConfig::class => DynamicReference::to(static fn () => DeliveryConfig::load($params)),
];
```

`load(array $params)` resolves dot-separated keys in nested arrays. The package ships a
`yiisoft/config` plugin (`config/yii3/params-console.php`) that registers the command with
`yiisoft/yii-console`:

```bash
./yii settings:generate [--check]
```

## Custom reader

All framework adapters accept a custom reader. For example, a database-backed store:

```json
{
    "adapter": "laravel",
    "reader": {
        "type": "\\App\\Settings\\SettingsStore",
        "variable": "store",
        "expression": "{var}->get({key})"
    }
}
```

This generates `public static function load(\App\Settings\SettingsStore $store): self` with
`$store->get('…')` calls. `{key}` is the exported key literal (with `key_prefix`) and `{var}` is
the variable.

## Extensions and post steps

Extensions own custom schema keys, validate them and add attributes to generated classes.
Post steps run once after all modules and write extra files, such as a registry or an index of
presets:

```php
use Tikhomirov\SettingsCodegen\Extension\AbstractExtension;
use Tikhomirov\SettingsCodegen\Extension\AttributeSpec;
use Tikhomirov\SettingsCodegen\Schema\{Module, Setting};

final class ModesExtension extends AbstractExtension
{
    public function reservedKeys(): array
    {
        return ['default_for_mode']; // never copied to UI arrays
    }

    public function propertyAttributes(Module $module, Setting $setting): array
    {
        $values = $setting->get('default_for_mode');

        return is_array($values) ? [new AttributeSpec(\App\Attribute\Defaults::class, ['values' => $values])] : [];
    }
}
```

```php
use Tikhomirov\SettingsCodegen\{Context, GeneratedFile};
use Tikhomirov\SettingsCodegen\PostStep\PostStepInterface;

final class RegistryPostStep implements PostStepInterface
{
    public function run(array $modules, Context $context, array $options): array
    {
        $names = array_map(fn ($m) => $m->name, $modules);
        $code = "<?php\n\nreturn " . $context->exporter->value($names) . ";\n";

        return [new GeneratedFile($context->config->path($options['path']), $code)];
    }
}
```

```json
{
    "bootstrap": ["tools/codegen.php"],
    "extensions": ["App\\Codegen\\ModesExtension"],
    "post_steps": [{"class": "App\\Codegen\\RegistryPostStep", "options": {"path": "config/settings-registry.php"}}]
}
```

A completely different output is one class away: implement
`Tikhomirov\SettingsCodegen\Adapter\AdapterInterface` and set `"adapter"` to its FQCN.

## CI

```yaml
- run: composer install
- run: vendor/bin/settings-codegen generate --check
```

## For AI agents

The package ships an [Agent Skill](skills/settings-codegen/SKILL.md) that teaches coding agents
how to add settings, regenerate and fix `--check` failures. It is part of the Composer package, so
after installation it is available at `vendor/tikhomirov/settings-codegen/skills/settings-codegen/`.

```bash
# Claude Code
mkdir -p .claude/skills && cp -r vendor/tikhomirov/settings-codegen/skills/settings-codegen .claude/skills/
# Cursor
mkdir -p .cursor/skills && cp -r vendor/tikhomirov/settings-codegen/skills/settings-codegen .cursor/skills/
```

Or reference it from your project's `AGENTS.md`:

```markdown
Settings are generated by tikhomirov/settings-codegen: follow
vendor/tikhomirov/settings-codegen/skills/settings-codegen/SKILL.md.
```

[`AGENTS.md`](AGENTS.md) in this repository is for agents working on the package itself.

## Development

```bash
composer test      # PHPUnit: golden files for every adapter, runtime behaviour, CLI
composer phpstan   # PHPStan level 9, no baseline
composer coverage  # PHPUnit with pcov or Xdebug; fails below 100% lines, methods and classes
composer check     # test + phpstan
UPDATE_GOLDEN=1 composer test   # after an intended output change; review the diff
```

CI runs the tests on PHP 8.1, 8.2, 8.3, 8.4 and 8.5 (plus lowest dependencies on 8.1) with
`E_ALL` and PHPUnit failing on any warning, notice or deprecation, runs the CLI for every adapter
to make sure it prints no deprecations, runs PHPStan, and a coverage job on PHP 8.3 with pcov that fails if line, method or class coverage drops below 100%. The Symfony
packages are in `require-dev`, so the bundle and the console command are covered too; there are no
coverage exclusions.

## License

[MIT](LICENSE)
