---
name: settings-codegen
description: Use when a PHP project uses tikhomirov/settings-codegen (a settings.json next to Generated/*Config.php classes, or "settings-codegen" in composer.json) and you need to add, change or remove a setting, regenerate the classes, fix a failing `settings-codegen generate --check`, or set the package up for WordPress/WooCommerce, Laravel, Symfony, Yii3 or a custom adapter.
---

# settings-codegen

Schema-first settings for PHP. Each module describes its settings once in `settings.json`; the
generator writes a typed readonly `{Module}Config` class plus framework glue:
`{Module}UiSchema` (WooCommerce Settings API) for WordPress, or `{Module}Definition`
(`fields()`, `defaults()`, `rules()`, `keys()`) for Laravel, Symfony and Yii3.

**Golden rule: never edit generated files by hand.** Change `settings.json` (or the codegen
configuration) and run `generate`. Manual edits are overwritten and `--check` fails in CI.

## Install

Requires PHP 8.1+ (tested on 8.1–8.5). Dev dependency only; production uses the committed
generated classes. Until the package is on
Packagist, install from GitHub:

```bash
composer config repositories.settings-codegen vcs https://github.com/tikhomirov/settings-codegen
composer require --dev tikhomirov/settings-codegen:dev-main
```

## Pick an adapter

| Project | `adapter` | `load()` reads from |
| --- | --- | --- |
| WordPress / WooCommerce | `wordpress` (default) | `\get_option($id, $default)` (`option_reader`) |
| Laravel | `laravel` | `\Illuminate\Contracts\Config\Repository $config` |
| Symfony | `symfony` | `ParameterBagInterface $parameters` |
| Yii3 | `yii3` | `array $params` (dot-separated keys) |
| Anything else | FQCN of `Tikhomirov\SettingsCodegen\Adapter\AdapterInterface` | your code |

The typed adapters (`laravel`, `symfony`, `yii3`) also accept a custom reader, e.g. a DB store:

```json
{"reader": {"type": "\\App\\Settings\\SettingsStore", "variable": "store", "expression": "{var}->get({key})"}}
```

## Configure

In `composer.json` under `extra.settings-codegen`, or in a standalone `settings-codegen.json`
(the CLI reads `settings-codegen.json` first, then `composer.json`):

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

The module name is the directory holding `settings.json` (override with `module_names`). Other
common options: `key_prefix` (typed adapters), `strip_prefixes`, `key_aliases`,
`strict_defaults` (default `true`), `readonly_class` (default `true`, needs PHP 8.2; use `false`
for 8.1), `style`, `string_cast` (`scalar` | `string` | `non_empty_string`), `definition_fields`
(`normalized` | `source`). The full table is in the package README.

## Add or change a setting

Edit the module's `settings.json` (JSON Schema for IDEs:
`vendor/tikhomirov/settings-codegen/resources/settings.schema.json`). First check which format
the file uses and keep it:

**Structured format** — a JSON object with `fields` and/or `sections` (prefer it for new files):

```json
{
    "sections": [
        {
            "title": "Shop API",
            "fields": [
                { "id": "enabled", "title": "Enable integration", "default": true },
                { "id": "timeout", "title": "Timeout, seconds", "default": 30, "rules": ["integer", "min:1"] }
            ]
        }
    ]
}
```

- A section is a title with nested `fields`; never add `sectionend` entries here. Its `id` is optional.
- `property` is derived (camelCase of the storage key: `api_key` → `apiKey`); `"property": false`
  makes a UI-only field. Titles and other value-less entries need no `id`.
- `php_type` is inferred from `default` (`true` → `bool`, `30` → `int`, `1.5` → `float`,
  `[]` → `array`, else `string`). Write `php_type` explicitly when the default does not show the
  type (e.g. no default, or `"10"` meant as int).
- WordPress: `type` defaults to `checkbox` / `number` / `text`; arrays need an explicit `type`.

**Flat list format** — a JSON array (older files). Nothing is inferred: every setting needs
`id` and `property`, `php_type` defaults to `string`, sections are explicit `title` /
`sectionend` entries:

```json
{
    "id": "timeout",
    "property": "timeout",
    "php_type": "int",
    "type": "number",
    "title": "Timeout, seconds",
    "default": 30,
    "rules": ["integer", "min:1"]
}
```

Keys (both formats):

- `id`: storage key / option id, unique in the module. Renaming it changes where the value is
  read from — existing stored values are not migrated.
- `property`: camelCase PHP identifier, unique in the module. Renaming it (or, in the structured
  format, renaming the `id` it is derived from) is a breaking change for code that reads
  `$config->property`.
- `php_type`: `string`, `int`, `float`, `bool`, `array`.
- `default`: with `strict_defaults` it must fit `php_type`: `bool` accepts `true/false`,
  `0/1`, `"yes"/"no"`, `"on"/"off"`, `"true"/"false"`, `""`; `int`/`float` accept numbers or
  numeric strings; `array` needs an array; `string` any scalar. `null` is always allowed.
- `rules`: copied to `Definition::rules()` (typed adapters).
- `ui: false`: keep a WordPress option out of the settings page (still in `getAllOptionKeys()`).
- WordPress only: `desc_args_expr` / `text_args_expr` (lists of PHP expressions for `sprintf`),
  `options_expr`, `depends_expr` (PHP expressions emitted verbatim).

Then regenerate and commit the schema together with the generated files.

## Generate and check

```bash
vendor/bin/settings-codegen generate          # write changed files
vendor/bin/settings-codegen generate --check  # write nothing; fail if outdated
vendor/bin/settings-codegen generate --config=settings-codegen.json --working-dir=path/to/project
```

Exit codes: `0` up to date / written; `1` (`--check` only) files missing or outdated — run
`generate`; `2` error — invalid schema or configuration (the message names the file, entry index
and key), unknown argument, or an unwritable path. Symfony with the bundle:
`bin/console settings:generate [--check]`; Yii3: `./yii settings:generate [--check]`.

## Extensions and post steps

- `extensions`: FQCNs of `ExtensionInterface` (or `AbstractExtension`): `reservedKeys()` claims
  custom schema keys (never copied to UI arrays), `validate(Module)` returns error strings,
  `classAttributes()` / `propertyAttributes()` return `AttributeSpec`s added to the Config class.
- `post_steps`: FQCNs or `{"class": ..., "options": {...}}` of `PostStepInterface`;
  `run(array $modules, Context $context, array $options)` returns extra `GeneratedFile`s (a
  registry, an index) once after all modules.
- `bootstrap`: PHP files required first so those classes autoload.

```json
{
    "bootstrap": ["tools/codegen.php"],
    "extensions": ["App\\Codegen\\ModesExtension"],
    "post_steps": [{"class": "App\\Codegen\\RegistryPostStep", "options": {"path": "config/settings-registry.php"}}]
}
```

## Common mistakes

- Editing `Generated/*.php` instead of `settings.json`.
- Changing `settings.json` without running `generate` — CI `--check` exits `1`.
- A `default` that does not fit `php_type` (e.g. `"abc"` for `int`) — exit `2`. Fix the default;
  turn `strict_defaults` off only for legacy schemas.
- Duplicate `id` or `property` in a module, or a `property` that is not a PHP identifier
  (structured format: two ids such as `a_b` and `a-b` derive the same property).
- Mixing formats: `sectionend` entries or required `property` belong to the flat list format only.
- Relying on inference where it guesses wrong: `"default": "10"` is a `string`; write `php_type`.
- Generating `readonly class` for a PHP 8.1 project — set `"readonly_class": false`.
- Custom keys used by your code but not claimed by an extension: they leak into the WordPress UI
  arrays.
- Two modules or a post step writing the same file — exit `2`.

## Checklist before commit

1. `settings.json` is valid; every new setting has an `id` and a `default` (flat format: also
   `property` and `php_type`).
2. `vendor/bin/settings-codegen generate` was run; generated files are staged with the schema.
3. `vendor/bin/settings-codegen generate --check` exits `0`.
4. Code reads the new value through `{Module}Config`, not raw option/config keys.
5. Renamed `id`s have a migration for stored values; renamed `property`s have all usages updated.
