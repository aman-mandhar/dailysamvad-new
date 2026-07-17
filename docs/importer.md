# WordPress Importer

## Post Statuses

The post importer defaults to published WordPress posts:

```bash
php artisan import:wordpress --only=posts --status=publish --limit=100
```

Supported filters are:

- `publish`
- `draft`
- `pending`
- `future`
- `private`
- `all`

Examples:

```bash
php artisan import:wordpress --only=posts --status=publish
php artisan import:wordpress --only=posts --status=draft
php artisan import:wordpress --only=posts --status=all
php artisan import:wordpress --only=posts --status=publish --limit=100
```

The importer never imports `trash`, `inherit`, `revision`, `attachment`, `nav_menu_item`, `auto-draft`, or an unknown status. Unsupported records are skipped and logged. `skipped_by_filter` counts supported posts excluded by the requested filter, while `unsupported_status` counts unsafe or unknown statuses.

## SEO Priority

SEO metadata is resolved in this order:

1. Yoast SEO
2. Rank Math
3. Generated metadata

Existing non-empty Laravel SEO values are preserved. Generated metadata uses the post title for the meta title. The meta description uses the WordPress excerpt, then approximately 160 characters of normalized plain-text content. Focus keywords and canonical URLs are imported when supplied by Yoast or Rank Math.

## Verification Fields

The post verification table reports:

- `seo_imported`: posts with Yoast or Rank Math metadata
- `seo_generated`: posts that used generated metadata
- `seo_missing`: posts for which complete SEO fallback could not be generated
- `skipped_by_filter`: supported posts excluded by `--status`
- `unsupported_status`: posts skipped because their status is unsafe or unknown
- `draft_without_category`: imported drafts with no WordPress category
- `category_mapping_failure`: WordPress category relationships whose categories are not imported
- `missing_category`: non-draft posts that have no WordPress category

Always run a dry run and review these counters before a live post import.
