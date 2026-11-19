# Contributing

Issues and pull requests are welcome.

- Open an issue first for a new option or adapter, so we can agree on the schema.
- Keep the rules from [`AGENTS.md`](AGENTS.md): PHPStan level 9 without a baseline, 100% coverage
  without ignores, deterministic output, a test for every new option.
- Run `composer check` and `composer coverage` before opening a PR. After an intended change in the
  generated output run `UPDATE_GOLDEN=1 composer test` and review the diff.
- Update `README.md`, `skills/settings-codegen/SKILL.md` and `CHANGELOG.md` (section `Unreleased`)
  when behaviour or options change.
