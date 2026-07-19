# Media Architecture

## Canonical contract

Daily Samvad uses `App\Models\Media` as the canonical identity and metadata record for reusable binaries. Existing string fields remain supported during the additive rollout: `posts.featured_image`, `users.avatar_path`, and `categories.image_path`. Posts may additionally reference `media.id` through nullable `featured_media_id`. No existing path is rewritten or backfilled by a migration.

- Canonical disk: `MEDIA_DISK`, default `public`.
- Canonical database path: normalized, disk-relative, forward-slash path without a leading slash, for example `wordpress/uploads/2026/07/photo.jpg`.
- Public local URL: generated through Laravel Storage by `MediaUrlResolver`, normally `/storage/{path}`.
- External URLs: only absolute HTTP or HTTPS URLs; valid non-WordPress URLs remain unchanged.
- WordPress upload URLs: `/wp-content/uploads/{path}` resolves to the preserved local `wordpress/uploads/{path}` layout. The importer never renames the WordPress relative path.
- Absolute server paths, traversal, null bytes, and executable extensions are rejected.
- SVG is not accepted by current upload controls or the WordPress importer MIME allowlist.

## Models and metadata

`Media` stores the stable WordPress attachment ID (`old_wp_id`), disk, path, original WordPress URL evidence, MIME type, byte size, optional dimensions, global alt text, caption, and provider metadata. The unique WordPress ID makes imports idempotent. `(disk, path)` is indexed but not unique because WordPress may contain distinct attachment identities that reference one binary.

Post-level `featured_image_alt` and `featured_image_caption` remain per-usage overrides. Media alt and caption are global attachment metadata. Existing rich HTML remains untouched and may contain independent WordPress URLs.

## WordPress mapping

`WordPressMediaImporter` reads attachment posts, `_wp_attached_file`, `_thumbnail_id`, `_wp_attachment_image_alt`, MIME type, caption, and size. It copies supported files in chunks, preserves year/month folders, upserts Media by WordPress attachment ID, and assigns both `featured_media_id` and the legacy `featured_image` path. Repeated imports reuse the same record. Missing, unreadable, unsupported, and duplicate files are reported without aborting the whole import.

WordPress generated-size metadata, attachment uploader, and serialized dimensions are not yet imported. Original attachment IDs cannot be inferred for legacy rows that were populated before this contract without a controlled source-backed backfill.

## Resolution and missing files

`MediaPathNormalizer` and `MediaUrlResolver` perform no database query. Normal URL resolution deliberately avoids `Storage::exists()` so homepage/archive lists never probe each image. Import mapping only assigns files confirmed at import time. Null, malformed, traversal, executable, and rejected legacy values return `null`, allowing existing Blade fallbacks to render.

`resolveExisting()` is available for bounded admin/detail diagnostics when a physical existence check is explicitly required. Missing metadata must not be silently cleared. A filesystem loss after import is detected by the existing verification command or future Phase 13B admin status, not by per-card web requests.

## Deletion and replacement

Only generated files under configured `media.managed_paths` (currently `posts/featured`) are candidates for automatic deletion. WordPress imports, remote URLs, avatars, categories, and arbitrary rich-content files are never automatically removed.

Before deletion, the observer refuses removal when a Media record owns the path, another live or soft-deleted post uses it as a featured image, or any post HTML references it. Replacing a unique managed upload removes the old binary after the database update. Soft deletion preserves it. Restoration requires no filesystem operation. Force deletion removes only an unreferenced managed upload. Deleting a Media row nulls `featured_media_id` but leaves legacy path metadata and the binary intact; future explicit cleanup must re-check every known and HTML reference.

## Security and performance

New operations use Laravel Storage. Featured-image uploads accept JPEG, PNG, and WebP up to 5 MB with UUID filenames. Avatar uploads accept the same image formats up to 2 MB. Executable paths and traversal are rejected, remote fetching is absent, and public storage contains an Apache `.htaccess` denying PHP-like execution. Nginx/VPS execution prevention must be configured and tested during deployment; this document does not claim it is currently enforced there.

No recursive scan, checksum, image probe, binary read, media-table preload, or existence check occurs during normal rendering. Import work is chunked. The local media volume measured on 2026-07-19 was 42,171 files and 2,648,679,599 bytes (2,525.98 MiB); these are audit observations, not cached application counters.

## Backup, deployment, and extension points

Back up the database and `storage/app/public` together so identities and binaries remain consistent. Preserve the `public/storage` link or equivalent web-server mapping and execution restrictions on deployment. Do not move or re-encode `wordpress/uploads`.

Phase 13B may build Filament browsing, search, upload, missing-status, and selection workflows on `Media`. Phase 13C may add derivatives and responsive metadata without changing original paths. Neither feature is implemented in Phase 13A.

## Responsive delivery contract

Public rendering uses the shared `news.responsive-image` Blade component. It accepts a resolved source, alt text, intrinsic dimensions, verified `srcset`, layout-specific `sizes`, loading, decoding, fetch priority, classes, and fallback behavior. It performs no query, filesystem check, image probe, or derivative generation.

Intrinsic dimensions are emitted only from Media metadata. Legacy path-only and remote images retain a plain `src` and omit unknown dimensions. Card containers continue using their design-specific aspect ratios; article images preserve their natural aspect ratio and are not cropped.

Derivative candidates may be read from `media.metadata.derivatives` only when each candidate has a positive width, canonical path, and `verified_at` evidence. The original may participate using its stored width. No derivatives currently get generated by public requests, migrations, or Blade. WordPress generated sizes cannot be reused until their paths and true widths are imported and verified; filename inference alone is prohibited.

The first homepage hero and article featured image are eager and high priority. Card, archive, sidebar, related, breaking-news, avatar, and advertisement images remain native-lazy where applicable. Remote sources receive no local `srcset`. Open Graph, feeds, Twitter, and JSON-LD continue using the original featured-image URL, preserving SEO image identity.

Future WebP, AVIF, or CDN work must retain JPEG/PNG fallbacks, record real generated alternates before emitting `picture` sources, and continue resolving hosts through the storage URL resolver. No format or CDN availability is claimed by this phase.

## Administration and integrity

The Filament Media resource uses server-side pagination, database-backed search and filters, eager reference counts, and persisted missing state. It never scans storage per table row. Uploads are handled by `MediaUploadService`, which validates real image content, uses collision-resistant paths, detects exact duplicates by checksum plus size, and removes a newly written file if database persistence fails.

Existing Media selection in the post workflow is server-searchable and does not preload the library. Assignment updates the canonical relationship and compatible legacy path; detachment does not delete the binary. Referenced records cannot be deleted, and force/bulk deletion is disabled. No gallery relationship was introduced because no verified product or repository requirement exists.

Operational audit, troubleshooting, authorization, formats, limits, backup, and failure semantics are documented in `MEDIA-OPERATIONS.md`. The audit is read-only, chunked, writes reports outside the public tree, and treats duplicates and orphans only as candidates.
