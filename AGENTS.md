# post-types: notes for AI coding agents

`javaabu/post-types` is a Laravel package for WordPress-style post types. Post types, taxonomies and statuses are definitions registered in code (`PostTypes::register(PostType::make('news')->…)`); every post type's posts are rows of one `posts` table. It builds on javaabu/meta (meta fields), javaabu/mediapicker (media), javaabu/block-editor (content), javaabu/filters and javaabu/bulk-actions (admin index), javaabu/stencil (forms), javaabu/permissions (RBAC) and javaabu/manifest (React code and styles). Admin and public pages ship in Blade and Inertia React. javaabu/translatable, javaabu/dynamic-menubuilder and inertiajs/inertia-laravel are optional.

Treat `PostType`, `Taxonomy`, `Status`, `PostTypesManager`/`PostTypes`, `Post`, `Term`, `PostForm`, `TermForm`, `PostFilters`, `PostBulkActions`, the controllers, the policies, `Route::postTypesAdmin()`, `Route::postTypes()`, the permission names, the page props and the `@javaabu/post-types` exports as public contracts: javaabu/cms builds on them.

## Layout

| Path | What |
| --- | --- |
| `src/PostType.php`, `src/Taxonomy.php`, `src/Status.php`, `src/PostTypesManager.php`, `src/Facades` | Definitions and the registry |
| `src/Models` | `Post`, `Term`, and their translatable versions in `Models/Translatable` |
| `database/migrations` | `posts`, `post_terms`, `post_term` |
| `src/Routing` | `PostTypeRoutes` (both macros, URL helpers, the current route's post type and guard) and `Permalink` |
| `src/Http` | Controllers, `PostData` and `PublicData` (what pages and JSON get) |
| `src/Forms`, `src/Filters`, `src/BulkActions`, `src/Policies` | Stencil forms, filters, bulk actions and policies |
| `src/Views/ViewRenderer.php` | Blade or Inertia, by `config('post-types.views')` |
| `src/Support` | `Translations` (optional javaabu/translatable, feature-detected), `Slug` (Thaana-safe slugs) |
| `src/Contracts/MenuLinkable.php`, `src/Integrations/Menus.php` | Optional dynamic-menubuilder: the contract alias and the sources |
| `resources/views` | Blade: admin pages, public pages, partials |
| `resources/js` | TypeScript: Inertia pages (`Pages/Posts`, `Pages/Terms`, `Pages/Public`), parts, types |
| `resources/css/post-types.css`, `dist/` | Styles (logical properties only) and their prebuilt copy, **committed** |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `tests/js/fixtures` | Inertia props written by `InertiaPagesTest` |
| `tests/js/shims` | Stand-ins for block-editor's and mediapicker's React modules in Vitest and `tsc` |
| `docs`, `llms.txt`, `resources/boost` | Docs, the index for assistants, the Laravel Boost guideline and skill |

## Rules

- Follow the Javaabu PHP guidelines (`vendor/javaabu/boost-javaabu-guidelines`): snake_case variables and properties, camelCase methods, `#[Test]` methods named `it_…`, early returns, no `else`. Pint enforces formatting.
- Posts never use `url()`: javaabu/translatable's trait defines it. The permalink is `Post::permalink()`, a term's archive `Term::archiveUrl()`.
- Use `PostTypes::postModel()`/`termModel()` (or a post type's `getModel()`), never `Post::class`, wherever records are queried or created, so the translatable or configured model is used. Meta fields are registered for the base `Post` (subclasses inherit them).
- When the Inertia props change, run `POST_TYPES_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter InertiaPagesTest` and commit `tests/js/fixtures`; keep `resources/js/types.ts` in step.
- After changing `resources/css`, run `pnpm build` and commit `dist/`. CI fails when it's stale.
- Nothing may assume the `web` guard or `App\Models\User`; HTTP tests use two guards (`web` → `User`, `web_admin` → `Admin`).
- Inertia, javaabu/translatable and javaabu/dynamic-menubuilder are optional: feature-detect them (`Support\Translations`, `Integrations\Menus::installed()`), keep them in `suggest`, and keep test classes loadable without them. CI has a leg without all three.
- Every user-facing string goes through `__()` with an English key and gets a line in `lang/en.json` and `lang/dv.json` (`LangTest` checks). Use Thaana in tests of user-facing behaviour. List uncertain translations in `docs/advanced-usage/dhivehi-and-rtl.md`. Views and components use logical CSS only (`RtlTest` checks).
- Every change needs tests, docs (linked from `llms.txt`, checked by `DocsTest`), the Boost skill if usage changes, and a line in `CHANGELOG.md`.
- Work on `main`. Never tag or release.

## Commands

```bash
composer install && pnpm install      # pnpm 10.28 (packageManager); composer first (the javaabu React code is in vendor/)
composer lint && composer test        # Pint and PHPUnit
pnpm lint && pnpm format:check && pnpm typecheck && pnpm test
pnpm build                            # dist/
```
