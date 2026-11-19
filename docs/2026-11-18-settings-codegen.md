# 2026-11-18 — пакет tikhomirov/settings-codegen

## 1. Резюме задания

Сделать публичный универсальный composer-пакет по идее статьи «Архитектура настроек в PHP»:
схема `settings.json` превращается в типизированные readonly-классы. Ядро без зависимостей.
Адаптеры через `AdapterInterface`: WordPress (WooCommerce Settings API), Laravel, Symfony, Yii3.
Добавить хуки для расширений и пост-шагов, CLI `generate` / `--check`, тесты на эталонных файлах,
CI и README. Проверить совместимость с woo2iiko, ничего не коммитя в плагин.

## 2. План и что сделано

1. Каркас пакета: PHP 8.1+, MIT, PSR-4 `Tikhomirov\SettingsCodegen`; фреймворки только в `suggest` и `require-dev`.
2. Схема: `SchemaLoader` (glob, `**`, сортировка, `key_aliases`, `module_names`, `strip_prefixes`),
   `SchemaValidator` и `resources/settings.schema.json`.
3. `PhpExporter`: детерминированный вывод; опционально выравнивание `=>` как в Pint и стиль `var_export`.
4. Ядро: `Codegen` (сборка, запись, check), `CodegenConfig` (`extra.settings-codegen` или отдельный JSON),
   `ExtensionInterface` (свои ключи, валидация, атрибуты), `PostStepInterface`, CLI `bin/settings-codegen`.
5. Адаптеры:
   - `wordpress`: Config + UiSchema с `__()`, совместим с генератором woo2iiko;
   - `laravel`, `symfony`, `yii3`: Config с `load()`/`fromArray()`/`toArray()` и Definition
     (`fields`, `defaults`, `rules`, `keys`); читатель (reader) настраивается.
6. Консоль: `settings:generate` (symfony/console), `SettingsCodegenBundle`, config-plugin для Yii3.
7. Тесты: эталонные файлы для всех адаптеров, исполнение сгенерированного кода, CLI и `--check`,
   unit-тесты. PHPStan level 8. Workflow GitHub Actions на PHP 8.1–8.4.
8. Проверка на woo2iiko во временной копии (`/tmp`, удалена). Результат ниже.

## 3. Проверка на woo2iiko

Конфиг пакета повторяет `bin/generate-settings.php`, без запуска Pint. Расширение «режимы»
и пост-шаг SettingsModeIndex написаны во временной копии, в пакет они не входят.

- 46 из 54 файлов `src/Features/*/Generated` совпали байт в байт.
- 3 файла (AddressManager, CheckoutForm, ReserveTables UiSchema) отличаются только тем, что Pint
  переписывает PHP-выражения из `*_expr` в settings.json: кавычки в `__("Geocoding", "iiko")`
  и сокращает FQCN `\iiko\Features\X\Generated\XConfig` до `XConfig`. Это лечится правкой
  выражений в settings.json.
- 5 файлов (ModifiersFilter и ProductNutritional Config/UiSchema, PluginOperatingMode UiSchema)
  отличаются у модулей без настроек: пакет пишет `new self()` и `return [];` вместо пустых
  строк внутри скобок. Это сделано намеренно.
- SettingsModeIndex: данные идентичны. Отличается только порядок модулей: пакет сортирует пути,
  старый генератор зависит от порядка файлов в ФС.
- Попутно найдено: в `SmsAuth/settings.json` у `waitcall_proxy_sites` (`php_type: array`)
  `default: ""`, поэтому `(array) ''` даёт `['']`. Для woo2iiko нужен `"strict_defaults": false`
  или исправить default на `[]`.

## 4. Коммиты

- d5beb6a chore: scaffold tikhomirov/settings-codegen package
- b15b0d8 feat(schema): load and validate settings.json with bundled JSON Schema
- cf46270 feat(php): add deterministic PHP exporter with optional Pint-style alignment
- ef34d04 feat(core): add codegen pipeline, extensions, post steps and CLI with --check
- 127ae15 feat(wordpress): generate Config DTO and WooCommerce UiSchema
- f719f24 feat(adapters): add Laravel, Symfony and Yii3 typed config adapters
- c8e4dd5 feat(console): add settings:generate command, Symfony bundle and Yii3 config plugin
- ccfaa8e test: add golden files for all adapters, runtime, CLI and unit tests
- c5efce0 ci: add GitHub Actions workflow for tests and PHPStan
- bfb8e90 docs: add README with WordPress, Laravel, Symfony and Yii3 examples and CHANGELOG

## 5. Доработка под edakit (2026-11-19)

Ветка `feat/edakit-compat`. Две опции типизированных адаптеров (laravel, symfony, yii3), чтобы
перевести edakit на пакет без изменения поведения в рантайме:

- `string_cast`: `scalar` (по умолчанию), `string`, `non_empty_string`. У edakit пустая строка
  и не-строки в хранилище дают default — это `non_empty_string`.
- `definition_fields`: `normalized` (по умолчанию) или `source`. При `source` `Definition::fields()`
  отдаёт поля как в settings.json, до `key_aliases`. edakit хранит `key`/`type`, рантайм читает
  именно их, а генератору они подаются алиасами `id`/`php_type`.

Тесты: `tests/Integration/TypedAdapterOptionsTest.php`. Golden-файлы не менялись.
