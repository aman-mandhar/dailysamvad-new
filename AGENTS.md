# AGENTS.md

## Project

Daily Samvad is being rebuilt from WordPress into a Laravel-based multilingual news CMS.

The existing WordPress website is available locally at:

http://localhost/dailysamvad-old

The new Laravel application is the replacement platform.

## Main Stack

- PHP 8.3+
- Laravel 13
- MySQL 8
- Livewire 3
- Blade
- Tailwind CSS
- Vite
- Filament
- Redis later
- React only for isolated interactive widgets when specifically required

## Development Rules

- Follow Laravel 13 conventions.
- Keep controllers thin.
- Use Form Requests for validation.
- Use Policies for authorization.
- Use services for external integrations.
- Use DTOs for structured News-Man data.
- Avoid unnecessary repository classes.
- Avoid unnecessary React usage.
- Prefer Blade and Livewire for the public site and admin workflow.
- Prevent N+1 queries with eager loading.
- Add indexes for frequently filtered columns.
- Add tests for every major feature.
- Do not modify unrelated files.
- Do not expose secrets.
- Do not place passwords or API keys in documentation.
- Do not run destructive production commands.
- Do not delete migrations or production data without explicit approval.

## WordPress Migration Rules

- Preserve WordPress post slugs.
- Preserve original publication dates.
- Preserve authors.
- Preserve categories and tags.
- Preserve featured image references.
- Preserve Yoast SEO metadata where available.
- Store the original WordPress ID in nullable unique old_wp_id fields.
- Importers must be idempotent.
- Importers must support chunking.
- Importers must log failures.
- Original WordPress HTML content should be preserved initially.
- Do not rename imported image files during the first migration.

## News-Man Rules

- AI-generated news must always be saved as draft.
- Only editors or admins may publish.
- AI must not publish directly.
- Every generated draft must retain source attribution.
- AI processing must use queues.
- Provider integrations must use interfaces.
- Do not hard-code prompts in business logic.
- Track AI usage and failures.

## Standard Commands

Tests:

php artisan test

Code formatting:

./vendor/bin/pint

Frontend build:

npm run build

Migration status:

php artisan migrate:status

## Working Method

For every task:

1. Inspect existing code.
2. Explain the implementation plan.
3. Modify only relevant files.
4. Add or update tests.
5. Run tests.
6. Run Pint.
7. Report changed files.
8. Report unresolved issues.

## Git Rules

- Use small focused commits.
- Do not combine unrelated features.
- Do not commit secrets.
- Do not modify production environment files.