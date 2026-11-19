# AGENTS.md

Notes for AI agents working on **this package**. To *use* the package in a project, see
[`skills/settings-codegen/SKILL.md`](skills/settings-codegen/SKILL.md).

- PHP 8.1+, no runtime dependencies in `src/` except `ext-json` and `ext-mbstring`. Framework
  packages go to `require-dev` / `suggest` only.
- Output must stay deterministic. Expected output lives in `tests/Fixtures/expected/`; after an
  intended change run `UPDATE_GOLDEN=1 composer test` and review the diff.

```bash
composer install
composer test       # PHPUnit
composer phpstan    # PHPStan level 9, no baseline, no ignores
composer coverage   # needs pcov or Xdebug; fails below 100% lines, methods and classes
```

Rules: keep PHPStan at level 9 without a baseline, keep coverage at 100% without
`@codeCoverageIgnore`, add a test for every new option, and update `README.md`,
`skills/settings-codegen/SKILL.md` and `CHANGELOG.md` when behaviour or options change.
