# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project follows
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Structured `settings.json` format: an object with `fields` and nested `sections` (no explicit
  `sectionend`), `property` derived from the id (`"property": false` for UI-only fields),
  `php_type` inferred from `default`, and for WordPress a default field `type` and `"yes"`/`"no"`
  checkbox defaults. The flat list format works unchanged; errors point to `sections[i].fields[j]`.
- `settings.json` loader with validation (`resources/settings.schema.json` for IDEs), key aliases,
  prefix stripping and explicit module names.
- Deterministic PHP exporter with optional Pint-compatible arrow alignment and `var_export` flavour.
- Adapters: `wordpress` (Config + WooCommerce UiSchema with `__()`), `laravel`, `symfony`, `yii3`
  (Config with `load()`, `fromArray()` and `toArray()`, plus a Definition class with fields,
  defaults, rules and keys). Custom readers and custom adapters are supported.
- Extension API (reserved keys, validation, class and property attributes) and post steps.
- `bin/settings-codegen generate [--check]`, a Symfony console command with an optional bundle,
  and a Yii3 `yiisoft/config` plugin.
- Typed adapters: `string_cast` (`scalar`, `string`, `non_empty_string`) and `definition_fields`
  (`normalized`, `source`) options for migrating existing schemas without changing runtime behaviour.
- Agent Skill `skills/settings-codegen/SKILL.md` (shipped in the Composer package) and `AGENTS.md`
  for contributors; README section "For AI agents".
- Quality gates: PHPStan level 9 without a baseline, 100% line, method and class coverage enforced
  in CI (`composer coverage`, PHP 8.3 + pcov, `tools/coverage-check.php`).
- PHP 8.5 support: CI matrix PHP 8.1–8.5; PHPUnit fails on warnings, notices and deprecations,
  and CI runs the CLI for every adapter with `E_ALL` to catch deprecations.
- README starts with the why: the problem (WordPress `get_option()` and Laravel `config()`/`env()`),
  a before/after example in the structured format with real generator output,
  a Mermaid diagram, the benefits and a link to the full rationale; package banner
  (`docs/assets/logo.jpg`, not in the dist archive).
