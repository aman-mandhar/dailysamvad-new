# Daily Samvad launch checklist

This checklist is intentionally unchecked. Mark an item complete only after recording evidence from the reviewed release and the actual staging or production environment. Never place credentials, cookies, tokens, or private user data in this document.

## Before launch

- [ ] Git ownership is resolved and `git status`, `git diff --check`, branch, and commit have been reviewed.
- [ ] The deployment source is a known commit from the approved repository and branch.
- [ ] The Laravel database backup has a timestamp, size, checksum, readable SQL check, and non-production restore result.
- [ ] Laravel storage/media has a timestamped archive, size, checksum, listing check, and restore result.
- [ ] Current WordPress database, uploads, code, and configuration backups are verified.
- [ ] DNS and SSL configuration records are exported and stored outside public web roots.
- [ ] Staging `.env` has `APP_ENV=staging`, `APP_DEBUG=false`, a unique `APP_KEY`, HTTPS `APP_URL`, and no production secrets in reports.
- [ ] Production `.env` values have been peer-reviewed without copying the local `.env` blindly.
- [ ] HTTPS certificate, chain, hostname, HTTP redirect, and secure cookies are verified.
- [ ] Web-server document root points only to Laravel `public/`.
- [ ] `storage` and `bootstrap/cache` ownership and least-privilege permissions are verified; no `777` permissions exist.
- [ ] Public storage link targets the intended persistent media directory.
- [ ] PHP execution is denied beneath uploaded media paths at the web-server level.
- [ ] Staging has access protection plus `X-Robots-Tag: noindex, nofollow`.
- [ ] Redirect matrix has been reviewed for posts, taxonomies, authors, dates, feeds, attachments, trailing slashes, HTTP, and `www` variants.
- [ ] Robots, sitemap eligibility, canonical hosts, metadata, and structured data are verified with real staging data.
- [ ] Advertisement behavior and provider staging policy are verified without clicking live ads.
- [ ] Analytics uses an approved staging-safe configuration and does not contaminate production reporting.
- [ ] Mail configuration has been tested with staging-safe recipients.
- [ ] Configuration, route, event, and view caches rebuild successfully.
- [ ] Homepage, article, archives, search, errors, admin, feed, robots, sitemap, and health smoke tests pass.
- [ ] Browser and responsive testing passes at 1440, 1280, 1024, 768, 430, 390, 375, and below 375 pixels.
- [ ] Admin login, authorization, draft, schedule, publish, validation, CSRF, media, and logout behavior pass using staging records.
- [ ] Performance baseline and server/application log review contain no critical findings.
- [ ] Rollback rehearsal is complete and the final staging state is healthy.
- [ ] Cutover owner, rollback owner, communication channel, and launch window are confirmed.

## During launch

- [ ] Confirm the latest WordPress and Laravel backups and checksums before changing traffic.
- [ ] Enter Laravel maintenance mode using the database-independent 503 page.
- [ ] Execute the approved final database synchronization strategy without modifying the live WordPress source database.
- [ ] Execute the approved final media synchronization after reviewing a non-destructive dry run.
- [ ] Deploy the reviewed commit into a new release directory.
- [ ] Run safe pending migrations with `--force`.
- [ ] Recreate and verify the persistent public storage link.
- [ ] Rebuild optimized Laravel caches from the approved production `.env`.
- [ ] Switch the release/current symlink or web root using the reviewed atomic procedure.
- [ ] Verify HTTPS, canonical host, assets, media, and secure cookies.
- [ ] Exit maintenance mode only after local server smoke checks pass.
- [ ] Run public homepage, article, category, search, admin, health, sitemap, robots, and redirect smoke checks.
- [ ] Monitor application, PHP-FPM, web-server, and database logs with secrets redacted.
- [ ] Invoke rollback immediately if a defined abort threshold is reached.

## After launch

- [ ] Homepage and representative recent/old articles render correctly.
- [ ] Category, tag, author, date, pagination, normal search, and empty search work.
- [ ] Admin login and core editorial workflow work.
- [ ] Sitemap and robots use the production host and intended indexing policy.
- [ ] Advertisements render or remain intentionally disabled without empty wrappers.
- [ ] Analytics receives only intended production traffic.
- [ ] Canonicals, Open Graph, Twitter metadata, JSON-LD, and redirects are correct.
- [ ] Application, PHP-FPM, web-server, and database logs show no launch-critical errors.
- [ ] Performance is compared with the approved staging baseline.
- [ ] Google Search Console changes are performed only after the production site is stable.
- [ ] News sitemap/Search Console submission policy is confirmed.
- [ ] Cache, sessions, storage, and any supervised queue worker are healthy.
- [ ] A post-launch database and media backup is created and integrity-checked.
- [ ] WordPress retirement status is explicitly recorded; WordPress is not deleted during cutover validation.
- [ ] Launch evidence, timings, incidents, and final decision are archived outside the public document root.
