# Changelog

All notable changes to `javaabu/post-types` are documented in this file.

## Unreleased

Initial development of v1.0:

- Post types (`PostType`), taxonomies (`Taxonomy`) and statuses (`Status`), fluently, as classes or in config, through the `PostTypes` registry. Features: content, excerpt, media, meta, author and scheduling.
- One `posts` table for every post type; `post_terms` and `post_term` for taxonomy terms. `Post` and `Term` models with scopes (`published()`, `scheduled()`, `withTerms()`, `search()`, …), Thaana-safe unique slugs, publish dates and scheduling without a cron.
- Meta fields with javaabu/meta (a meta group per post type, nested in the post form), a featured image and more media with javaabu/mediapicker, block-editor content.
- `Route::postTypesAdmin()`: admin pages per post type with status tabs, javaabu/filters, javaabu/bulk-actions (publish, move to drafts, trash, restore, delete for good), stencil forms, trash and restore; term pages per taxonomy. JSON for every route.
- `Route::postTypes()`: archives, term archives and single posts with permalink structures (`{slug}`, `{id}`, `{year}`, `{month}`, `{day}`, taxonomy keys), canonical redirects, previews for editors, and WordPress-style template lookup.
- Blade (javaabu/forms v2) and Inertia React (`@javaabu/post-types` through javaabu/manifest) for every page.
- Permissions per post type and taxonomy through javaabu/permissions, `PostPolicy` and `TermPolicy`, ownable post types, any guard; overridable controllers, forms, filters, bulk actions and policies.
- Optional javaabu/translatable (translatable posts, terms and meta), javaabu/dynamic-menubuilder (post types as menu sources) and Inertia.
- English and Dhivehi strings, RTL-safe views and styles; docs, `llms.txt` and the Laravel Boost guideline and skill.
- Large post bulk actions are queued like other packages' (`bulk-actions.queue_after`): `PostBulkActions` stores the post type's key as the run's context and is rebuilt with `fromContext()` (Javaabu/bulk-actions#13). Needs bulk-actions' `context` column: run `php artisan migrate`.
- The npm packages only the Inertia pages and React components need (React, `@javaabu/forms`, `@javaabu/onu`) are declared with `npm(..., for: 'pages')`, so Blade-only apps' `php artisan manifest:check` doesn't ask for them.
- `PostCard` and the public React pages import the package's stylesheet, so Vite bundles it on Inertia pages without an import in the app's CSS.
