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
   unit-тесты. PHPStan level 9 (без baseline). Покрытие 100% (строки, методы, классы), проверка в CI.
   Workflow GitHub Actions на PHP 8.1–8.4.
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

## 6. Публикация и качество

- Репозиторий: https://github.com/tikhomirov/settings-codegen (публичный). Тега и Packagist нет.
- Ветка `feat/edakit-compat` влита в `main` обычным merge (`25bac55`), коммит `9e9e1a8` есть в
  `origin/main` (на него ссылается composer.lock edakit). Локальная ветка удалена.
- PHPStan level 9 без baseline и без ignore (`47dc77d`): типы сужены в коде, а не подавлены.
- Покрытие 100% строк (820/820), методов (124/124) и классов, без `@codeCoverageIgnore`.
  Symfony-пакеты в `require-dev`, бандл и консольная команда тоже покрыты. В CI отдельный job
  на PHP 8.3 с pcov: `tools/coverage-check.php` валит сборку при покрытии ниже 100%.
  Локально: `composer coverage`.
- Удалён мёртвый код: `CodegenConfig::has/get/lines`, `Codegen::config()`, `Module::hasSettings()`.
- README: бейджи (CI, coverage 100%, PHPStan 9), установка из VCS, справка по CLI и кодам выхода,
  все четыре адаптера, `string_cast` / `definition_fields`, раздел «For AI agents».
- Agent Skill `skills/settings-codegen/SKILL.md` входит в dist-архив; `AGENTS.md` для работы над
  самим пакетом (в dist не входит).
- `actions/checkout` обновлён до v5.

Коммиты этапа:

- 47dc77d refactor: raise PHPStan to level 9
- 25bac55 Merge branch 'feat/edakit-compat'
- 2e9c63c test: reach 100% line, method and class coverage and enforce it in CI
- 1417fe8 docs: complete README with badges, CLI reference and development checks
- 4bb84e7 docs: add settings-codegen Agent Skill, AGENTS.md and README section for AI agents

CI для `4bb84e7`: все jobs зелёные (PHP 8.1–8.4, lowest 8.1, PHPStan, Coverage) —
https://github.com/tikhomirov/settings-codegen/actions/runs/44481904977

## 7. PHP 8.5 (2026-11-19)

- Ограничение `"php": ">=8.1"` в composer.json уже пускает 8.5, менять не нужно.
- Матрица CI: PHP 8.1, 8.2, 8.3, 8.4, 8.5 (highest) и 8.1 (lowest). PHPUnit теперь валит сборку
  на warnings, notices и deprecations (`failOnNotice`, `failOnDeprecation`); в CI `error_reporting=-1`
  и отдельный шаг запускает CLI для всех четырёх адаптеров и падает при любом выводе в stderr.
- Падений и deprecations на 8.5 не нашлось, правок кода не потребовалось.
- Коммиты: acf2a6f `ci: test on PHP 8.5 and fail on notices and deprecations`,
  `docs: mention PHP 8.5 support in README, CHANGELOG, skill and AGENTS.md` (716cce8).
- CI для 716cce8 зелёный на всех версиях:
  https://github.com/tikhomirov/settings-codegen/actions/runs/44482441868
  На 8.1 пропущено 7 тестов: сгенерированные readonly-классы требуют 8.2.

## 8. README: зачем нужен пакет (2026-11-19)

- Верх README переписан: баннер `docs/assets/logo.jpg` (карандашный рисунок, 1280×720, JPEG, 264 КБ, в dist не входит; первый вариант `logo.png` заменён отдельным коммитом, затем рисунок обновлён: слева корректные UI-контролы — числовые поля, селект, переключатели, справа Config с типизированными свойствами),
  одна строка-суть, бейджи, «The problem», «The solution: schema first» (до/после на примере
  DeliveryCalendar: settings.json → `generate` → реальный вывод адаптера wordpress, сокращённый →
  `$config->restTime`, unit-тест без БД), Mermaid-схема, «Why use it», ссылка на статью.
  Установка, адаптеры, CLI и раздел для AI-агентов идут после.
- Утверждения сверены с кодом: вывод получен реальным запуском генератора, ошибка PHPStan
  `Access to an undefined property` проверена на level 9.

## 9. Упрощённый формат схемы и абстрактные примеры (2026-11-19)

Задача: убрать «iiko»-примеры из README, показать «было» для WordPress и Laravel, упростить
формат settings.json (id не везде, секции без явных start/end) с полной обратной совместимостью.

Как было: корень — массив; `id` уже был необязателен для записей без `property`, но секции
описывались парой `title` + `sectionend`, а у каждой настройки нужны `property` и `php_type`.

Что сделано:

- `SchemaNormalizer`: корень-объект `{"fields": [...], "sections": [{"title", "fields": [...]}]}`
  разворачивается в тот же плоский список (title + поля + sectionend с id секции, если он задан).
  `property` = camelCase ключа хранения (после `strip_prefixes`), `"property": false` — поле без
  значения; `php_type` выводится из `default`; для WordPress — `type` (checkbox/number/text) и
  `"yes"`/`"no"` для булевых чекбоксов. Ошибки указывают путь `sections[0].fields[1]`.
- Почему так: объект в корне однозначно отличает новый формат от старого (массив), поэтому
  старые схемы проходят без изменений и без флагов; вывод типов только в новом формате, чтобы
  не поменять смысл старых файлов (там `php_type` по умолчанию `string`).
- JSON Schema: `oneOf` из двух форматов, определения `field` и `section`.
- Тесты: `SchemaNormalizerTest` (новый формат даёт байт-в-байт то же, что эквивалентный плоский,
  для всех 4 адаптеров; ошибки; вывод типов), модуль-фикстура `Shop` в новом формате и его эталоны.
  Старые эталоны не изменились. Покрытие 100%, PHPStan 9.
- edakit: во временной копии с пакетом из рабочего дерева `composer generate:settings:check` —
  «up to date (47 files, 23 schemas)», `generate` меняет 0 файлов, каталоги Generated идентичны.
  Сам edakit не менялся.
- README: «The problem» с WordPress и Laravel, «The solution» на модуле Shop (enabled, api_key,
  timeout, секция Newsletter) с реальным выводом адаптеров laravel и wordpress; схема Mermaid без
  изменений; раздел «The schema» описывает оба формата. Скилл обновлён.

## 10. Адаптер MoonShine (2026-11-19)

Задача: адаптер под админ-панель MoonShine — поля формы из схемы, дефолты, секции → Box/Fieldset,
с опорой на то, как edakit строит поля (`AbstractSettingsModulePage::moduleField()`).

- `MoonShineAdapter` (`adapter: "moonshine"`) наследует типизированный адаптер: те же Config и
  Definition, читатель по умолчанию — Laravel config repository (MoonShine работает на Laravel),
  `reader` переопределяется. Дополнительно `{Module}Fields`: `components()`, `names()`,
  `values(Config)`, `input(array)`.
- Логика из edakit перенесена в генерацию: `ui.field` (switcher/textarea/number/select/password/
  color/text…), `ui.label`, `ui.help` → `hint`, `ui.options`, `ui.multiple`, `ui.attributes`
  (только скалярные значения, как `htmlAttributes()`), `ui.show_when` → `showWhen()` по имени поля,
  `ui.section` → секция. Плюс WooCommerce `type`, `title`/`desc_tip`/`desc`, `custom_attributes`,
  `placeholder`, `rules: required`. Неизвестный `ui.field` — ошибка генерации (в edakit — молча Text).
- Опции: `fields_suffix`, `field_name` (`{key}`, `{key_snake}`, `{property}`; для edakit —
  `module_setting_{key_snake}`), `section` (`box`/`fieldset`), `translate` (`__`).
- Почему MoonShine не в require-dev: `moonshine/moonshine` требует PHP 8.2+ и Laravel-компоненты,
  а матрица начинается с 8.1 (и lowest на 8.1) — composer не поставил бы зависимости. Генератор
  MoonShine не нужен (он пишет текст), поэтому покрытие 100% достигается без него. Реальный API
  проверяется отдельным job «MoonShine API» на PHP 8.2–8.5: `composer require --dev
  moonshine/moonshine` и PHPStan level 9 по эталонам `tests/Fixtures/expected/moonshine`
  (`tests/MoonShine/phpstan.neon`). На 8.2 ставится MoonShine 4.21, на 8.5 — 5.0, оба без ошибок.
  Создать поля в рантайме без загруженного ядра MoonShine нельзя (`Core::$instance`), поэтому
  проверка статическая; проверено, что она ловит неверные методы и типы аргументов.
- Тесты: эталоны для 4 модулей (включая edakit-стиль `moonshine/Panel`), `MoonShineAdapterTest`.
