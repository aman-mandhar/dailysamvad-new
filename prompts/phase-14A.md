# Phase 14A — Native SEO Foundation

## Objective

Implement a completely native Laravel SEO system.

Do NOT use any third-party SEO package.

This project must no longer depend on WordPress SEO plugins (Yoast/RankMath).

Everything should be generated dynamically by Laravel.

---

# Requirements

Create a dedicated SEO layer.

Example architecture (modify if a better architecture already exists):

app/
    SEO/
        SEOManager.php
        MetaData.php
        SEOData.php
        OpenGraphData.php
        TwitterCardData.php

SEO logic must NOT live inside controllers.

Controllers should only provide the page model.

The SEO layer must determine all metadata.

---

# Generate

Every public page must automatically generate:

<title>

<meta name="description">

<meta name="keywords">

<meta name="author">

<meta name="robots">

<link rel="canonical">

without requiring duplicate Blade code.

---

# Canonical URLs

Automatically generate canonical URLs.

Examples

/news/article-slug

/category/politics

/tag/punjab

/search?q=...

Every page must point to its own canonical URL.

---

# Meta Title Priority

Priority order:

1. Custom SEO title
2. Article title
3. Site name

---

# Meta Description Priority

Priority order:

1. Custom description
2. Article excerpt
3. Auto-generated summary

Descriptions should be approximately 160 characters.

---

# Keywords

Support:

• custom keywords

• categories

• tags

Merge them intelligently.

Remove duplicates.

---

# Author

Output article author.

Fallback:

Site Name.

---

# Robots

Support:

index,follow

noindex,nofollow

noarchive

nosnippet

Per-page configurable.

---

# Canonical

Avoid duplicate URLs.

Support:

Pagination

Category archives

Tag archives

Author archives

Search pages

---

# Blade

Create ONE reusable Blade component or partial responsible for rendering all SEO tags.

No duplicated HTML.

---

# Performance

Avoid unnecessary database queries.

SEO generation should reuse already-loaded models whenever possible.

---

# Protected Boundaries

Do NOT modify:

Existing frontend HTML structure

CSS

JavaScript

Livewire components

News queries

Homepage

Importer

Authentication

Admin panel

Database schema unless absolutely required

---

# Validation

Verify:

Article pages

Category pages

Tag pages

Author pages

Search pages

Homepage

Every page should contain valid metadata.

No duplicate title tags.

No duplicate description tags.

No duplicate canonical URLs.

---

# Commands

Run:

php artisan test

php artisan optimize

php artisan route:list

---

# Completion Report

Return:

1. Files created

2. Files modified

3. Architecture decisions

4. Validation results

5. Performance impact

6. Any follow-up recommendations for Phase 14B

Do not begin Phase 14B.
Only implement Phase 14A.