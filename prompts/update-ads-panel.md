# Update Ads Panel — NewsMan CMS

## Objective

Upgrade the existing config-driven advertisement foundation into a production-ready, database-driven Advertisement Management System for NewsMan CMS.

The implementation must preserve and reuse the existing advertisement slot/rendering architecture wherever practical, while adding:

* database-managed advertisements;
* Filament administration;
* frontend quick editing;
* image and video advertisements;
* editable advertisement hyperlinks;
* scheduling;
* device and page targeting;
* priority and rotation support;
* advertisement caching;
* click and impression tracking;
* advertisement analytics;
* article paragraph placement rules;
* short-article bottom stacking;
* secure provider-code handling;
* role- and permission-based access.

Do not remove or break existing homepage, article, archive, sidebar, footer, Media Library, SEO, Filament, cache, queue, imported WordPress media, or frontend rendering functionality.

---

# Existing System Context

The project already contains an advertisement frontend foundation.

Current known status:

* Approximately 19 advertisement slots are configured in:

```text
config/advertisements.php
```

* Existing advertisement types include:

```text
html
image
placeholder
```

* Advertisement data handling exists in:

```text
app/Data/AdvertisementData.php
```

* Existing advertisement rendering includes components similar to:

```text
x-news.advertisement-slot
x-frontend.ad-slot
```

* Homepage advertisement positions are already wired.
* Article top, inline article, article bottom and sidebar advertisements are partially wired.
* Category, tag, search, date and author archive advertisements are partially wired.
* Disabled or invalid advertisements generally avoid rendering empty wrappers.
* Advertisement images already support responsive and lazy-loaded rendering.
* External advertisement links support attributes similar to:

```html
rel="sponsored noopener noreferrer"
```

* Article inline advertisements are inserted after sanitized article paragraphs.
* A permission similar to the following already exists:

```text
manage advertisements
```

The existing implementation must be audited before modifications.

Reuse working foundations instead of unnecessarily rebuilding them.

---

# Primary Requirement

Create a complete Advertisement Panel that allows authorized users to manage advertisements through Filament and, for selected actions, directly from the public frontend.

The following roles must be able to update advertisements from the frontend:

```text
super-admin
admin
editor
```

Do not hardcode frontend access using role names alone.

Implement permission-based authorization so role assignments can be changed later without rewriting advertisement code.

---

# Required Advertisement Positions

Preserve compatible existing homepage, archive, search, sidebar, author, category, tag and footer slots.

The article page must support these exact positions:

```text
ARTICLE_TOP

ARTICLE_AFTER_FEATURED_IMAGE

ARTICLE_AFTER_PARAGRAPH_1

ARTICLE_AFTER_PARAGRAPH_2

ARTICLE_AFTER_PARAGRAPH_3

ARTICLE_AFTER_PARAGRAPH_4

ARTICLE_AFTER_PARAGRAPH_5

ARTICLE_BOTTOM

ARTICLE_SIDEBAR
```

Use a PHP enum or another single canonical source of truth.

Recommended enum:

```php
App\Enums\AdvertisementPosition
```

Do not duplicate slot names independently across config files, Blade templates, Filament forms, tests and services.

---

# Required Article Rendering Order

The intended article rendering order is:

```text
Article title

Article metadata

ARTICLE_TOP

Featured image

ARTICLE_AFTER_FEATURED_IMAGE

Paragraph 1

ARTICLE_AFTER_PARAGRAPH_1

Paragraph 2

ARTICLE_AFTER_PARAGRAPH_2

Paragraph 3

ARTICLE_AFTER_PARAGRAPH_3

Paragraph 4

ARTICLE_AFTER_PARAGRAPH_4

Paragraph 5

ARTICLE_AFTER_PARAGRAPH_5

Remaining article content

ARTICLE_BOTTOM
```

`ARTICLE_SIDEBAR` must remain in the article sidebar and must not participate in inline fallback logic.

---

# Short Article Fallback Rule

Paragraph advertisement positions must never be silently skipped only because the article contains fewer paragraphs.

When an article has fewer than five valid renderable paragraphs, advertisements assigned to unavailable paragraph positions must be moved to a bottom advertisement stack.

The bottom stack must appear after the article content.

`ARTICLE_BOTTOM` must be appended after the fallback paragraph advertisements.

The stack order must always preserve the original logical position order.

Example: article contains only two paragraphs.

Required output:

```text
Paragraph 1

ARTICLE_AFTER_PARAGRAPH_1

Paragraph 2

ARTICLE_AFTER_PARAGRAPH_2

ARTICLE_AFTER_PARAGRAPH_3

ARTICLE_AFTER_PARAGRAPH_4

ARTICLE_AFTER_PARAGRAPH_5

ARTICLE_BOTTOM
```

The last four advertisements must appear one below another.

There must be exactly:

```text
5px
```

vertical space between successfully rendered advertisements in this bottom stack.

Do not add a margin before the first advertisement or after the final advertisement unless existing layout styling independently requires it.

Recommended structure:

```html
<div class="article-ad-bottom-stack">
    <!-- active rendered advertisements -->
</div>
```

Recommended CSS:

```css
.article-ad-bottom-stack {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.article-ad-bottom-stack:empty {
    display: none;
}
```

Only active and valid advertisements may participate in the stack.

An inactive, expired, invalid or unassigned advertisement must:

* render no markup;
* create no empty wrapper;
* create no 5px gap;
* create no placeholder in production.

---

# Article Fallback Matrix

Implement and test the following exact behavior:

| Valid article paragraph count | Inline positions   | Bottom fallback stack      |
| ----------------------------: | ------------------ | -------------------------- |
|                             0 | None               | P1, P2, P3, P4, P5, Bottom |
|                             1 | P1                 | P2, P3, P4, P5, Bottom     |
|                             2 | P1, P2             | P3, P4, P5, Bottom         |
|                             3 | P1, P2, P3         | P4, P5, Bottom             |
|                             4 | P1, P2, P3, P4     | P5, Bottom                 |
|                     5 or more | P1, P2, P3, P4, P5 | Bottom                     |

Where:

```text
P1 = ARTICLE_AFTER_PARAGRAPH_1
P2 = ARTICLE_AFTER_PARAGRAPH_2
P3 = ARTICLE_AFTER_PARAGRAPH_3
P4 = ARTICLE_AFTER_PARAGRAPH_4
P5 = ARTICLE_AFTER_PARAGRAPH_5
```

`ARTICLE_TOP` and `ARTICLE_AFTER_FEATURED_IMAGE` must never be moved into this fallback stack.

---

# Paragraph Detection Requirements

Use the existing sanitized article content pipeline.

Do not split article HTML using unsafe regular expressions when a DOM-aware or existing parser is available.

A valid paragraph for advertisement placement should generally be a visible, non-empty `<p>` element after sanitization.

Do not count:

* empty paragraphs;
* whitespace-only paragraphs;
* hidden elements;
* script or style elements;
* advertisement markup;
* unrelated wrappers;
* captions unless the existing article renderer treats them as body paragraphs.

Preserve:

* headings;
* blockquotes;
* lists;
* images;
* embeds;
* tables;
* WordPress-imported HTML;
* existing sanitization;
* existing responsive media handling.

Advertisement insertion must not corrupt HTML structure.

---

# Canonical Advertisement Renderer

Currently there appear to be two parallel rendering systems similar to:

```text
x-news.advertisement-slot
x-frontend.ad-slot
```

Audit both.

Refactor toward one canonical advertisement renderer.

Recommended public API:

```blade
<x-advertisement.slot
    :position="\App\Enums\AdvertisementPosition::ARTICLE_TOP"
    :context="$article"
/>
```

A string may be accepted internally for backward compatibility, but canonical application code should prefer the enum.

The canonical component or service must:

* resolve the active advertisement;
* validate scheduling;
* validate targeting;
* validate device requirements where applicable;
* render the selected creative;
* avoid empty wrappers;
* expose authorized frontend editing controls;
* support impression tracking;
* generate click-tracking URLs;
* cache safe resolver results;
* preserve sponsored link attributes.

Maintain temporary backward-compatible wrapper components where required, but document them as deprecated and migrate existing usages.

---

# Footer Cleanup

Audit the existing footer advertisement implementation.

There is a known risk that the active footer contains a legacy static placeholder such as:

```text
Advertisement · Footer
```

This legacy footer placeholder may render independently of the config-driven slot system.

Required action:

* remove the duplicate unconditional footer placeholder;
* connect the footer to the canonical advertisement renderer;
* use the appropriate canonical footer position;
* ensure production does not show fake advertisement placeholders;
* ensure no duplicate footer advertisements appear.

If the existing position is:

```text
FOOTER_TOP
```

retain it unless there is a strong architectural reason to rename it.

Any renaming must include backward compatibility and tests.

---

# Header Position Cleanup

Audit the meaning of:

```text
HEADER_TOP
```

If it is currently used only as a homepage advertisement, either:

1. preserve its current behavior and document it clearly; or
2. migrate it to a correctly named homepage position with backward compatibility.

Do not silently change current frontend placement.

---

# Database Architecture

Implement database-driven advertisements.

Use normalized tables instead of storing every concern in one unstructured record.

Recommended tables follow.

---

## advertisements

Campaign-level data:

```text
id
uuid
title
slug
advertiser_name nullable
description nullable
status
priority
rotation_weight
target_url nullable
open_in_new_tab
nofollow
sponsored
start_at nullable
end_at nullable
created_by nullable
updated_by nullable
published_by nullable
published_at nullable
created_at
updated_at
deleted_at nullable
```

Recommended status values:

```text
draft
scheduled
active
paused
expired
archived
```

Use an enum where practical.

Rules:

* an advertisement must not render while in draft, paused or archived state;
* scheduled ads render only inside the active date range;
* expired ads must stop rendering automatically;
* date handling must respect the application timezone;
* status resolution should not require manually changing every expired record.

---

## advertisement_creatives

Creative-level data:

```text
id
advertisement_id
type
media_id nullable
image_path nullable
video_path nullable
poster_media_id nullable
poster_path nullable
html_code nullable
alt_text nullable
caption nullable
width nullable
height nullable
mime_type nullable
file_size nullable
autoplay
muted
loop
controls
created_at
updated_at
```

Supported creative types:

```text
image
video
html
provider_code
```

Optional future types may be added without changing existing database meaning.

Image support should include existing safe formats supported by the project.

Video support should initially prioritize browser-safe formats such as:

```text
MP4
WebM
```

For autoplay video:

* muted must be required;
* `playsinline` must be used;
* controls must be configurable;
* loop must be configurable;
* poster image must be supported;
* loading behavior must avoid harming article performance.

---

## advertisement_placements

Placement and targeting data:

```text
id
advertisement_id
position
page_type nullable
category_id nullable
tag_id nullable
post_id nullable
device
created_at
updated_at
```

Recommended device values:

```text
all
desktop
tablet
mobile
```

An advertisement may have multiple placements.

Do not duplicate an entire campaign record merely to assign it to multiple positions.

Use proper relationships and indexes.

---

## advertisement_daily_stats

Aggregated reporting data:

```text
id
advertisement_id
date
impressions
clicks
unique_impressions nullable
unique_clicks nullable
created_at
updated_at
```

Use a unique database constraint for:

```text
advertisement_id + date
```

Do not write every impression synchronously to a large raw events table on each public request.

Prefer:

* Redis counters where Redis is available;
* queued aggregation;
* atomic increments;
* scheduled flushing to daily statistics.

Provide a database-safe fallback when Redis is unavailable.

---

## advertisement_audits

Track important advertisement changes:

```text
id
advertisement_id
user_id nullable
action
old_values nullable
new_values nullable
ip_hash nullable
created_at
```

Track at least:

```text
created
updated
published
paused
creative_replaced
link_changed
schedule_changed
deleted
restored
```

Avoid storing sensitive raw request information unnecessarily.

---

# Model Requirements

Create appropriate Eloquent models and relationships.

Recommended models:

```text
Advertisement
AdvertisementCreative
AdvertisementPlacement
AdvertisementDailyStat
AdvertisementAudit
```

Use:

* casts;
* enums;
* scopes;
* factories;
* policies;
* soft deletes where appropriate;
* database indexes;
* foreign keys;
* unique constraints.

Recommended scopes/services:

```php
Advertisement::active()
Advertisement::currentlyScheduled()
Advertisement::forPosition(...)
Advertisement::forDevice(...)
```

Do not put the full ad-selection algorithm directly inside Blade views.

---

# Advertisement Resolver

Create a dedicated resolver service.

Recommended class:

```php
App\Services\Advertisements\AdvertisementResolver
```

Responsibilities:

* accept a position;
* accept page context;
* identify matching active campaigns;
* enforce dates;
* enforce status;
* enforce page targeting;
* enforce category/tag/post targeting;
* enforce device targeting where server-side detection is used;
* apply priority;
* apply rotation;
* return a renderable DTO;
* provide a config fallback during migration;
* cache results safely.

Recommended usage:

```php
$advertisement = $resolver->resolve(
    position: AdvertisementPosition::ARTICLE_TOP,
    context: $context
);
```

The resolver must remain testable independently of Blade rendering.

---

# Priority and Rotation

Implement practical initial rotation.

At minimum:

* higher priority advertisements should be considered first;
* equal-priority advertisements may rotate using weight;
* `rotation_weight` must be a positive integer;
* selection must not return inactive or invalid advertisements;
* fallback to another matching advertisement when the preferred advertisement has no valid creative.

Do not implement a fragile pseudo-random algorithm directly in Blade.

A deterministic option may be used for cache-friendly rotation, for example based on a bounded time bucket, visitor token or request context.

Document the chosen algorithm.

---

# Config Migration and Backward Compatibility

Do not delete:

```text
config/advertisements.php
```

immediately.

Use it temporarily for:

* default slot definitions;
* slot metadata;
* development placeholders;
* database seeding;
* backward-compatible fallback.

Create a migration or seeder strategy that imports or maps existing configured slots without duplicating production ads.

After database ads exist, the resolver should prefer database records.

Suggested resolution order:

```text
1. Matching active database advertisement
2. Valid configured fallback advertisement
3. Development-only placeholder
4. Nothing
```

Production must never display development placeholders unless explicitly enabled by a secure configuration flag.

---

# Filament Advertisement Panel

Create a Filament Advertisement Resource.

Suggested path:

```text
app/Filament/Resources/AdvertisementResource.php
```

Follow the project’s currently installed Filament version and architecture.

Do not assume APIs from another Filament major version.

The resource must support:

* listing advertisements;
* searching;
* filtering;
* creating;
* editing;
* viewing;
* duplicating where safe;
* pausing;
* publishing;
* scheduling;
* restoring;
* deleting based on authorization;
* viewing basic statistics.

---

## Advertisement Form

The form should include logical sections.

### Basic information

```text
Title
Slug
Advertiser name
Description
Status
Priority
Rotation weight
```

### Creative

```text
Creative type
Select existing Media Library item
Upload image
Upload video
Poster image
Alt text
Caption
HTML/provider code
Video controls
Autoplay
Muted
Loop
```

Reuse the existing NewsMan Media Library where practical.

Avoid creating unnecessary duplicate files.

When uploading new files:

* use the project’s existing storage disk conventions;
* validate MIME type;
* validate extension;
* validate file size;
* generate safe filenames;
* avoid executable uploads;
* preserve compatibility with image optimization queues;
* return reliable public URLs.

### Destination

```text
Target URL
Open in new tab
Sponsored
Nofollow
```

### Placement

```text
One or more positions
Page type
Categories
Tags
Posts
Device
```

### Schedule

```text
Start date and time
End date and time
```

### Status and publishing

```text
Draft
Scheduled
Active
Paused
Archived
```

### Read-only information

```text
Created by
Updated by
Published by
Created at
Updated at
Current impressions
Current clicks
CTR
```

---

# Filament Table

Show useful columns:

```text
Creative preview
Title
Advertiser
Positions
Type
Status
Priority
Start
End
Impressions
Clicks
CTR
Updated by
Updated at
```

Recommended filters:

```text
Status
Creative type
Position
Device
Scheduled
Expired
Advertiser
Date range
```

Recommended actions:

```text
Edit
View
Duplicate
Publish
Pause
Archive
Restore
Delete
View statistics
```

Use bulk actions only where safe.

Do not allow an Editor to bulk-delete campaigns unless specifically authorized.

---

# Frontend Quick Editing

Authorized users must be able to update advertisements directly from the public frontend.

Eligible users are expected to include:

```text
super-admin
admin
editor
```

However, access must be controlled by permissions and policies.

Recommended permission:

```text
update advertisements from frontend
```

Do not render frontend editing HTML for unauthorized or unauthenticated visitors.

Do not merely hide controls using CSS.

Authorization must be enforced server-side for every update action.

---

## Frontend Editing Overlay

For authorized users, display a small non-intrusive overlay on each resolved advertisement.

Suggested actions:

```text
Edit
Replace creative
Change link
Activate or pause
View stats
Open in Advertisement Panel
```

The overlay must:

* not affect public advertisement dimensions;
* not be visible to guests;
* be keyboard accessible;
* work on image and video advertisements;
* not interfere with normal advertisement click tracking;
* avoid being indexed by search engines;
* not be included in cached public HTML shown to guests.

Be especially careful with full-page caching.

Authenticated frontend toolbar markup must never leak into anonymous cached responses.

---

## Frontend Edit Modal

Use Livewire or the project’s established interactive stack.

The quick-edit modal should support:

```text
Title
Creative preview
Select existing Media Library item
Upload replacement image
Upload replacement video
Poster image
Destination URL
Open in new tab
Sponsored
Nofollow
Status
Start
End
Save
Cancel
Open full settings
```

For Editors:

* allow creative replacement;
* allow target URL updates;
* allow status changes where permitted;
* do not allow arbitrary provider JavaScript unless separately authorized;
* do not allow permission or ownership changes.

For Super Admin/Admin:

* allow broader campaign management based on policy.

After saving:

* validate authorization again;
* save atomically;
* create an audit record;
* clear affected advertisement cache;
* refresh the advertisement component;
* queue expensive image/video processing;
* preserve the previous creative until the replacement is successfully stored;
* avoid broken image or video URLs.

---

# Permissions

Audit existing permissions before adding duplicates.

Create or normalize permissions similar to:

```text
view advertisements
create advertisements
update advertisements
delete advertisements
restore advertisements
publish advertisements
pause advertisements
update advertisements from frontend
view advertisement analytics
manage advertisement provider code
manage advertisement settings
```

Recommended access model:

| Role              | Suggested access                                                                      |
| ----------------- | ------------------------------------------------------------------------------------- |
| super-admin       | Full advertisement access                                                             |
| admin             | Full operational advertisement access                                                 |
| editor            | View, update assigned/allowed ads, replace creative, change link, frontend quick edit |
| seo-manager       | Optional link metadata access                                                         |
| analytics-manager | Read-only analytics                                                                   |
| reporter          | No advertisement management                                                           |
| reviewer          | No advertisement management                                                           |
| subscriber        | No advertisement management                                                           |

Preserve existing role architecture.

Do not remove existing permissions.

Use policies and gates consistently in:

* Filament navigation;
* resource pages;
* table actions;
* Livewire actions;
* controllers;
* click endpoints;
* provider-code editing.

---

# Provider HTML and Script Security

Existing developer-controlled HTML may currently render using raw Blade output.

When advertisement HTML becomes admin-managed, this becomes a high-risk surface.

Implement a trusted-provider policy.

Recommended distinction:

```text
html
provider_code
```

For ordinary HTML:

* sanitize against an explicit allowlist;
* disallow arbitrary scripts;
* disallow event-handler attributes;
* disallow unsafe URLs;
* disallow embedded forms unless intentionally supported.

For provider code:

* allow only users with:

```text
manage advertisement provider code
```

* consider provider allowlisting;
* preserve Content Security Policy requirements;
* clearly label the field as trusted code;
* audit every change;
* never expose it to Editors by default.

Do not pretend arbitrary JavaScript can be safely sanitized using basic string replacement.

---

# URL Validation

Validate creative and destination URLs.

Destination URL rules:

* allow valid `http` and `https` URLs;
* optionally support approved internal relative URLs;
* reject `javascript:`;
* reject unsafe `data:` destinations;
* reject malformed control characters;
* normalize whitespace;
* preserve valid tracking query parameters.

For uploaded media:

* generate URLs through Laravel storage APIs;
* avoid manual path concatenation;
* verify file existence before rendering;
* use a safe fallback or render nothing if the asset is missing.

---

# Advertisement Rendering Requirements

## Image advertisement

Render:

* responsive image dimensions;
* width and height attributes where available;
* useful alt text;
* lazy loading except where above-the-fold performance requires another strategy;
* destination link when supplied;
* sponsored and nofollow attributes;
* secure target behavior.

Recommended link attributes for external sponsored links:

```html
rel="sponsored nofollow noopener noreferrer"
```

Apply only the attributes enabled by the advertisement settings while retaining required security attributes.

---

## Video advertisement

Render:

* `<video>`;
* safe MIME-aware `<source>` tags;
* poster image;
* `playsinline`;
* muted autoplay only;
* optional controls;
* optional loop;
* preload strategy;
* fallback content;
* destination click behavior that does not make controls unusable.

Do not place an anchor over the entire video in a way that blocks video controls.

Use a dedicated CTA or safe clickable wrapper strategy.

---

## HTML/provider advertisement

Render only after authorization and validation requirements are satisfied.

Provider code should not be escaped if it is intentionally trusted, but access to edit it must be strongly restricted.

---

# Tracking

Implement advertisement impression and click tracking.

## Impression tracking

An impression should be counted when an advertisement is actually rendered or viewed according to the chosen initial tracking strategy.

Document whether the first version uses:

```text
server-side render impression
```

or:

```text
client-side viewability impression
```

A client-side method using `IntersectionObserver` is preferred for better accuracy, but it must:

* avoid duplicate counting;
* work with lazy-loaded ads;
* respect privacy;
* fail gracefully when JavaScript is disabled;
* not block rendering.

Use an opaque tracking token rather than exposing internal campaign logic.

---

## Click tracking

Advertisement destination links should route through a signed or validated click endpoint.

Example:

```text
/advertisements/{advertisement}/click
```

Requirements:

* validate that the advertisement exists;
* reject unsafe destination URLs;
* increment the click counter safely;
* dispatch or queue analytics work;
* redirect using an allowlisted safe redirect;
* avoid open redirect vulnerabilities;
* avoid requiring authentication for public advertisement clicks;
* apply throttling where appropriate.

Do not accept an arbitrary destination URL directly from request query parameters.

---

# Analytics

Add a basic Advertisement Analytics view.

At minimum show:

```text
Impressions
Clicks
CTR
Date range
Advertisement
Position
Creative type
Device where available
Top-performing advertisements
Recently expired advertisements
Currently active advertisements
```

CTR formula:

```text
clicks / impressions * 100
```

Handle zero impressions safely.

The initial analytics implementation does not need to become a complete enterprise ad server, but the database structure must not block future reporting.

---

# Cache Integration

Advertisement rendering must integrate with the existing cache architecture.

Cache keys should account for relevant dimensions such as:

```text
position
page type
category
tag
post
device
active schedule window
```

Do not create unbounded per-user cache keys.

On advertisement create/update/publish/pause/delete/restore:

* clear affected advertisement resolver cache;
* clear affected full-page cache where necessary;
* avoid flushing the entire application cache unless no targeted mechanism exists;
* document any broad fallback cache clearing.

Ensure frontend editing controls never become part of anonymous full-page cache.

When a campaign begins or expires due to schedule, cached pages must not continue serving the wrong advertisement indefinitely.

Use bounded cache TTLs or scheduled invalidation.

---

# Queue Integration

Use queues for expensive work:

* image optimization;
* video metadata extraction;
* poster generation if implemented;
* analytics aggregation;
* cache invalidation batches;
* provider validation where appropriate.

Do not require the queue to complete before preserving a valid current advertisement.

Use after-commit dispatching where database consistency matters.

Provide synchronous-safe fallbacks for local testing when the queue connection is `sync`.

---

# Media Library Integration

Reuse the existing NewsMan Media Library.

Allow:

```text
Select Existing
Upload New
```

Do not break imported WordPress media.

When an existing media item is selected:

* store its relationship/reference;
* avoid copying the file unnecessarily;
* use reliable public URLs;
* respect soft-deleted or missing media behavior.

When replacing a creative:

* do not delete the old media if it is referenced elsewhere;
* do not delete imported WordPress media automatically;
* avoid orphaning newly uploaded files after failed transactions;
* preserve current creative until replacement validation succeeds.

---

# Development Placeholders

Development placeholders may continue to exist behind a configuration flag.

Example:

```env
ADVERTISEMENT_PLACEHOLDERS_ENABLED=true
```

Rules:

* default production value must be false;
* production placeholders must not appear as fake ads;
* placeholders must not record impressions or clicks;
* placeholders must not render frontend quick-edit actions unless deliberately useful for creating an ad in that slot;
* placeholder rendering must not cause cumulative layout shift.

An authorized user may optionally see an “Add advertisement” control for an empty slot without showing that empty UI to public visitors.

---

# Empty Slot Frontend Management

For authorized users only, empty configured slots may display a small management control such as:

```text
Add advertisement
```

This control must:

* not render for guests;
* not occupy public layout space;
* not leak into cached guest HTML;
* open a create form prefilled with the current position;
* respect create permissions;
* not create an advertisement until the user explicitly saves.

---

# API and Service Boundaries

Keep business logic out of:

* Blade templates;
* Filament schema closures where possible;
* route closures;
* Livewire render methods.

Recommended classes may include:

```text
AdvertisementResolver
AdvertisementRenderer
AdvertisementTrackingService
AdvertisementCacheService
AdvertisementAuthorizationService
ArticleAdvertisementInjector
AdvertisementStatisticsAggregator
```

Use DTOs where they improve clarity.

Preserve existing `AdvertisementData` if useful, extending or replacing it only after auditing all usages.

---

# Suggested Implementation Sequence

Implement in the following order.

## Part 1 — Audit and tests

* inspect current advertisement config;
* inspect DTOs;
* inspect both rendering components;
* locate every slot usage;
* inspect article paragraph injection;
* inspect footer placeholder;
* inspect permissions;
* inspect cache and queue integration;
* add regression tests before major refactoring.

## Part 2 — Canonical positions and renderer

* create advertisement position enum;
* normalize existing slot metadata;
* create canonical renderer;
* keep backward-compatible wrappers;
* migrate footer;
* remove duplicate unconditional placeholder;
* ensure no empty wrappers.

## Part 3 — Article placement upgrade

* add featured-image position;
* add paragraph positions 1–5;
* implement DOM-safe injection;
* implement short-article fallback;
* implement 5px bottom stack;
* add exact matrix tests.

## Part 4 — Database layer

* create migrations;
* create models;
* create factories;
* create seeders;
* build resolver;
* add config fallback;
* add scheduling;
* add priority and rotation;
* add targeting.

## Part 5 — Filament Resource

* create Advertisement Resource;
* add form;
* add creative preview;
* integrate Media Library;
* add table filters/actions;
* add policy checks;
* add analytics summary.

## Part 6 — Frontend quick editing

* add authorized overlay;
* add Livewire modal;
* add replace creative;
* add target URL update;
* add status update;
* add audit logs;
* clear caches safely.

## Part 7 — Tracking and analytics

* add impression tracking;
* add click redirect;
* protect against open redirects;
* aggregate counters;
* show analytics;
* test duplicate prevention.

## Part 8 — Security and production hardening

* provider-code authorization;
* HTML sanitization;
* URL validation;
* CSP review;
* cache isolation;
* queue failure handling;
* missing media handling;
* performance checks.

---

# Protected Boundaries

Do not break or unnecessarily rewrite:

* existing WordPress import system;
* imported media paths;
* existing posts and article content;
* existing SEO metadata;
* Open Graph metadata;
* social images;
* current post workflow;
* role dashboards;
* Spatie permission setup;
* Filament panel registration;
* homepage layout;
* archive pages;
* search;
* category pages;
* tag pages;
* author pages;
* article sidebar;
* existing cache architecture;
* existing queue architecture;
* Redis integration;
* image optimization;
* frontend responsive design;
* public URLs;
* sitemap;
* robots;
* analytics integrations.

Do not remove current config slots merely because database records are introduced.

Do not create breaking route conflicts with article slugs or legacy WordPress routes.

---

# Testing Requirements

Add focused automated tests.

## Position tests

Test every required article position:

```text
ARTICLE_TOP
ARTICLE_AFTER_FEATURED_IMAGE
ARTICLE_AFTER_PARAGRAPH_1
ARTICLE_AFTER_PARAGRAPH_2
ARTICLE_AFTER_PARAGRAPH_3
ARTICLE_AFTER_PARAGRAPH_4
ARTICLE_AFTER_PARAGRAPH_5
ARTICLE_BOTTOM
ARTICLE_SIDEBAR
```

## Paragraph fallback tests

Test articles containing:

```text
0 paragraphs
1 paragraph
2 paragraphs
3 paragraphs
4 paragraphs
5 paragraphs
more than 5 paragraphs
```

For each case verify:

* correct inline advertisements;
* correct bottom fallback order;
* ARTICLE_BOTTOM appears last;
* no duplicate advertisement;
* no skipped active fallback advertisement;
* no blank wrapper;
* inactive advertisements create no gap;
* bottom stack uses the expected class;
* CSS or rendered markup supports 5px spacing.

## Scheduling tests

Test:

* future advertisement;
* currently active advertisement;
* expired advertisement;
* paused advertisement;
* draft advertisement;
* missing end date;
* missing start date;
* timezone boundaries.

## Targeting tests

Test:

* all devices;
* desktop;
* tablet;
* mobile;
* category targeting;
* tag targeting;
* post targeting;
* page-type targeting;
* fallback advertisement.

## Permission tests

Test access for:

```text
super-admin
admin
editor
reporter
reviewer
seo-manager
analytics-manager
subscriber
guest
```

Verify frontend update endpoints reject unauthorized users even if called directly.

## Creative tests

Test:

* image creative;
* video creative;
* safe HTML;
* provider code permission;
* missing media;
* invalid URL;
* unsafe JavaScript URL;
* unsupported file type;
* replacement failure preserving old creative.

## Tracking tests

Test:

* impression increment;
* duplicate impression protection;
* click increment;
* safe redirect;
* invalid destination rejection;
* no arbitrary open redirect;
* inactive advertisement click behavior;
* placeholder does not track.

## Cache tests

Test:

* cache hit;
* update invalidation;
* status invalidation;
* schedule boundary;
* guest cache excludes editor controls;
* authenticated editor can see controls;
* public response never contains frontend edit markup.

## Regression tests

Retain and run existing advertisement-related tests.

Ensure homepage, article, category, tag, search, date, author and footer rendering continue working.

---

# Validation Commands

Run commands appropriate to the project environment.

At minimum:

```bash
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan optimize:clear
php artisan test
```

Run focused advertisement tests before the complete suite.

Example:

```bash
php artisan test --filter=Advertisement
```

Run formatting:

```bash
vendor/bin/pint
```

Run frontend build if frontend assets are modified:

```bash
npm run build
```

Inspect routes:

```bash
php artisan route:list
```

Inspect queue failures where relevant:

```bash
php artisan queue:failed
```

Do not claim the full test suite passed if it timed out or was not completed.

---

# Required Completion Criteria

The phase is complete only when:

1. Existing advertisement rendering remains functional.
2. Duplicate footer advertisement rendering is removed.
3. A canonical advertisement renderer is used.
4. Article positions 1–5 are supported.
5. `ARTICLE_AFTER_FEATURED_IMAGE` is supported.
6. Short article fallback works exactly as specified.
7. Fallback advertisements stack with exactly 5px gap.
8. Inactive advertisements create no gap or wrapper.
9. Advertisement records are database-managed.
10. Image and video creatives are supported.
11. Hyperlinks are editable.
12. Filament Advertisement Resource works.
13. Authorized frontend quick editing works.
14. Unauthorized frontend editing is blocked server-side.
15. Scheduling works.
16. Priority and rotation work.
17. Device/page/category/post targeting works.
18. Impression and click tracking work.
19. Basic analytics are available.
20. Provider-code access is restricted.
21. URL validation prevents unsafe redirects.
22. Cache invalidation works.
23. Guest cache never exposes editor controls.
24. Existing Media Library is reused safely.
25. Existing WordPress media remains intact.
26. Focused tests pass.
27. Full-suite status is reported honestly.
28. No production placeholder appears unintentionally.
29. No broken image/video URL is introduced.
30. No existing SEO or frontend workflow is broken.

---

# Required Completion Report

After implementation, return a structured report with these exact sections.

## 1. Summary

Explain what was implemented.

## 2. Audit Findings

Describe the original advertisement architecture and any inconsistencies found.

## 3. Files Created

List every created file.

## 4. Files Modified

List every modified file and briefly explain why.

## 5. Database Changes

List migrations, tables, columns, indexes and relationships.

## 6. Advertisement Positions

List all supported positions and identify any backward-compatible aliases.

## 7. Article Fallback Behaviour

Show the implemented paragraph fallback matrix and confirm the 5px stack behavior.

## 8. Filament Advertisement Panel

Describe forms, tables, filters, actions and permissions.

## 9. Frontend Quick Editing

Describe the overlay, modal, authorization and cache isolation.

## 10. Creative Support

Describe image, video, HTML and provider-code behavior.

## 11. Scheduling, Targeting and Rotation

Describe the implemented selection logic.

## 12. Tracking and Analytics

Describe impressions, clicks, CTR and aggregation.

## 13. Security

Describe HTML/provider-code rules, URL validation, redirect protection and authorization.

## 14. Cache and Queue Integration

Describe cache keys, invalidation and queued work.

## 15. Tests

Report:

```text
focused test command
focused tests passed
focused assertions
full-suite command
full-suite result
frontend build result
Pint result
```

Do not hide timeouts or skipped validations.

## 16. Manual Verification Steps

Provide exact browser steps for:

* creating an image advertisement;
* creating a video advertisement;
* assigning an article position;
* checking a two-paragraph article fallback;
* editing an advertisement from the frontend;
* testing an unauthorized user;
* verifying a click;
* checking analytics;
* testing expiry;
* verifying footer behavior.

## 17. Risks or Remaining Work

List anything intentionally deferred.

---

# Final Instruction

Begin by auditing the current implementation.

Do not blindly replace existing advertisement code.

Preserve all working advertisement slots and tests.

Implement the feature completely in the current repository.

Do not stop after producing a plan.

Do not create placeholder-only files.

Do not leave migrations, models, Filament resources, frontend controls or tests as TODOs.

Make production-safe code changes, run the available validation commands and return the required completion report.
