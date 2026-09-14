# Agent & Contributor Rules

## Protected paths — DO NOT MODIFY

Any `Core/` directory under `src/`, the skeleton-owned DI wiring, and the
translation message files listed below are **frozen code**. AI agents must
not create, edit, rename, move, or delete files under these paths — not
even to fix a bug, apply a refactor, run a codemod, or "improve" style.

Protected paths:

- `src/Shared/Core/`
- `src/Domain/Shared/Core/`
- `src/Application/Shared/Core/`
- `src/Infrastructure/Core/`
- `config/common/di/` — skeleton-owned DI wiring
- `resources/messages/en/error.php`
- `resources/messages/en/success.php`
- `resources/messages/en/validation.php`
- `resources/messages/id/error.php`
- `resources/messages/id/success.php`
- `resources/messages/id/validation.php`
- any future `src/**/Core/` directory

If a change to protected code seems necessary (bug, security fix, new
shared capability, message wording), **stop and ask the maintainer
first**. Do not work around the rule by editing protected files
"temporarily" or reverting afterwards.

Core code is maintained centrally and synced into this project via
`composer skeleton-update` — never patch it locally. Message files
(`error`, `success`, `validation`) are owned by the maintainer — AI agents
may read them to reference existing message keys, but must not modify
them.

### Where to put code instead

| Need | Location |
| --- | --- |
| Shared helpers / utilities | `src/*/Common/` (e.g. `src/Shared/Common/`) |
| New feature | a module, e.g. `src/Api/V1/<Module>/`, `src/Application/<Module>/` |
| Configuration / DI wiring | `config/web/di/`, `config/console/`, or a new project file — never `config/common/di/` |
| Database changes | `src/Migration/` |
| New translation keys | `resources/messages/{en,id}/app.php` — never the protected `error`/`success`/`validation` files |
