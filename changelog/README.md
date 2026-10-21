# Changelog

Satu file per versi: `v{MAJOR}.{MINOR}.{PATCH}.md` (versi terbaru cukup
dilihat dari nama file; tidak ada file gabungan).

Format file:

```markdown
# v1.0.0 — 2026-10-15

## Ringkasan

<2-3 kalimat: apa yang berubah dan kenapa>

## Endpoint terpengaruh   (bila relevan)

| Method | Path | Perubahan |
| --- | --- | --- |
| POST | `/v1/...` | ... |

## Detail perubahan

<bullet per perubahan — section Added/Changed/Fixed/Renamed juga boleh>

## File berubah

- `path/ke/file`

## Deployment steps

<langkah setelah deploy: migrate, composer update, env baru, dll —
atau "Tidak ada">
```

Catatan:

- Versi di folder ini adalah **versi changelog app** (semver sendiri
  mulai `v1.0.0`) — tidak mengikuti nomor versi skeleton.
  `v1.0.0` adalah baseline gabungan seluruh riwayat skeleton hingga
  `v1.2.9`.
- Release notes skeleton untuk `composer skeleton:update` tetap di
  `scripts/skeleton.readme` (mekanisme terpisah, format `## vX.Y.Z`).
