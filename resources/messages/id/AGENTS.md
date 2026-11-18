# `resources/messages/` rules

<!-- skeleton-source-only:start -->
> **Source repo** — rules below apply to consumer projects synced via
> `composer skeleton:update`. In this skeleton source repo, `Core/` is
> editable; changes made here are what ship downstream.
<!-- skeleton-source-only:end -->

- `en/error.php`, `en/success.php`, `en/validation.php` and the same files
  under `id/` are **frozen** — don't add your code here, don't edit, rename,
  move, or delete them.
- New translation keys go in `{en,id}/app.php`.
- You may read the frozen files to reference existing message keys.
- See the root `AGENTS.md` for the full protected-paths policy.
