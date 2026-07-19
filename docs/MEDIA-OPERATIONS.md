# Media Operations

## Access and uploads

The Filament Media resource is protected by the existing `manage media` permission. Editors, administrators, reporters, and other roles receive only the access already assigned by `RolePermissionSeeder`; panel access remains a separate existing boundary. Force deletion and bulk deletion are disabled. Referenced Media records cannot be deleted.

Library uploads use `MEDIA_DISK` and `MEDIA_LIBRARY_PATH`. The default maximum is 10,240 KB. JPEG, PNG, GIF, and WebP are allowed after real MIME and image-content validation. SVG, empty files, corrupt images, executable content, traversal, and oversized uploads are rejected. Stored names are UUID-based and original filenames are metadata only. A storage failure creates no record; a database failure after writing removes the new binary. Matching SHA-256 and byte size returns the existing record without merging or deleting anything.

Global metadata includes alt, caption, credit, and copyright. Post alt and caption remain usage overrides. Selecting Media in the post form sets both `featured_media_id` and the compatible path. Detaching clears the relationship and never deletes the shared binary. Imported WordPress originals are never silently replaced.

## Integrity audit

Run the read-only audit with:

```powershell
php artisan media:audit
php artisan media:audit --chunk=250 --no-storage-scan
php artisan media:audit --fail-on-errors
```

The command processes database records in bounded chunks and writes a JSON report under `storage/app/media-audits`, outside the public web root. It checks missing and zero-byte originals, derivative state, invalid paths, MIME metadata, dimensions, featured and soft-deleted references, deleted assignments, possible inline-HTML references, checksum groups, WordPress identity conflicts, and storage files without Media records. Storage scanning is streaming and may be disabled for a faster database-only run.

Default exit status is zero after a completed audit, including when findings exist. `--fail-on-errors` returns failure when missing originals, invalid paths, zero-byte files, or WordPress identity conflicts are found. Operational errors are reported without exposing absolute server paths. The command never fixes metadata, deletes files, merges duplicates, or changes editorial records.

`media:report-orphans --dry-run` provides the narrower existing candidate report. A candidate means zero known featured, legacy-path, or inline-HTML reference; it is not proof that deletion is safe. Filename matches and matching dimensions are not binary duplicates. Exact duplicates require checksum and size evidence. Soft-deleted-only references and possible inline HTML must remain protected.

## Missing files and troubleshooting

Normal public rendering does not call `Storage::exists()`. Unsafe values become fallbacks; a physical loss after import is found by the audit. The media table exposes persisted `missing_at` state without scanning each table row. Do not clear metadata simply because a binary is missing.

If images do not render, verify the configured disk, `public/storage` link, relative database path, file permissions, and web-server execution rules. Back up the database and `storage/app/public` together. Never move or rename `wordpress/uploads` during troubleshooting.

Apache public-storage rules reject PHP-like execution. Equivalent Nginx/VPS rules must be verified during deployment. Do not expose audit reports through the web server.

## Responsive delivery and future formats

Public components use stored dimensions and verified derivative metadata only. Hero/article priority and native lazy loading are documented in `MEDIA-ARCHITECTURE.md`. No public request generates derivatives or probes files. JPEG/PNG fallbacks remain required. WebP/AVIF `<picture>` sources, a CDN, and responsive derivative generation remain deferred until real files and runtime support are verified.

## Known limitations

- Historical path-only posts need a controlled source-backed relationship/dimension backfill.
- WordPress generated sizes are not inferred by filename.
- Inline HTML references remain uncertain without a parser-backed inventory.
- No gallery model or verified gallery requirement exists.
- Orphan and duplicate output is advisory and never authorizes deletion.
