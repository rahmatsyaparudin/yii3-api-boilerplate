# `src/Console/` rules

- `Core/` is **frozen code** — don't add your code here, don't edit, rename,
  move, or delete anything inside it.
- Put project-specific console commands in `Common/` instead.
- `Core/` is synced via `composer skeleton:update` — local changes will be
  lost. See the root `AGENTS.md` for the full protected-paths policy.
