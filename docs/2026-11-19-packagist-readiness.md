# 2026-11-19 — ревью оформления перед Packagist

## Резюме

- README: шапка по центру (обложка → заголовок → слоган → бейджи в одну строку), бейджи Packagist
  (version, downloads), обложка по абсолютному URL (видна и на Packagist), установка через
  `composer require`, VCS — как запасной вариант.
- composer.json: description/keywords с MoonShine, `homepage`, `support` (issues, source, security).
  `composer validate --strict` — ok.
- Добавлены SECURITY.md и CONTRIBUTING.md (export-ignore), `.gitattributes` с `eol=lf`.
- GitHub: topics, homepage, выключены Wiki и Projects, включены Dependabot alerts, private
  vulnerability reporting, удаление веток после merge.
- Обложка 1280×720, 264 КБ, текст читается — оставлена как есть (в dist не попадает).
- Релиз v0.1.1 (только метаданные и документация).

## План

1. Правки README/composer.json/файлов сообщества — `24d7c93`.
2. Эта заметка — следующий коммит.
3. Push, CI, тег v0.1.1 + GitHub release.
4. Руками: social preview (Settings → Social preview → загрузить `docs/assets/logo.jpg`),
   публикация на Packagist и GitHub webhook для автообновления.
