# Authorization (Permissions)

## Current state

Permission rules are defined in:

- `config/common/access.php`

Each entry maps a permission name (example `brand.view`) to a callable returning `bool`.

## Missing integration

This project still needs a single enforcement point, typically one of:

- A dedicated middleware (recommended) that checks current route name and validates access.
- Per-action checks (simpler but repetitive).

## Recommended approach

- Define a `PermissionMiddleware` that:
  - Retrieves current `Actor` (from `CurrentUser`).
  - Retrieves current route name.
  - Resolves a permission rule callable from `config/common/access.php`.
  - Throws `ForbiddenException('forbidden')` when access is denied.

This makes authorization consistent and keeps actions clean.
