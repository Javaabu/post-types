---
title: Configuration
sidebar_position: 1
---

Publish the config with `php artisan vendor:publish --tag=post-types-config`. Every option has a default; the common case needs none.

| Option | Default | |
| --- | --- | --- |
| `types` | `[]` | Post types: `PostType` instances or class names |
| `taxonomies` | `[]` | Taxonomies: `Taxonomy` instances or class names |
| `permalinks` | `[]` | `'news' => ['base' => 'blog', 'structure' => '{year}/{slug}']`. See [Permalinks](../basic-usage/permalinks.md) |
| `models.post` | `null` | A `Post` subclass for every post type; `null` is the package's (translatable when javaabu/translatable is enabled) |
| `models.term` | `null` | A `Term` subclass |
| `tables.posts`, `tables.terms`, `tables.post_term` | `posts`, `post_terms`, `post_term` | Table names |
| `run_migrations` | `true` | Load the package's migration |
| `views` | `blade` (`POST_TYPES_VIEWS`) | `blade` or `inertia` |
| `layout` | `layouts.app` | The Blade layout of the admin pages |
| `public_layout` | `layouts.app` | The Blade layout of the public pages |
| `layout_section` | `content` | The section both fill |
| `per_page` | `20` | Admin rows per page (`->perPage()` per post type) |
| `archive_per_page` | `12` | Posts per archive page (`->archive(per_page:)`) |
| `controllers.posts`, `controllers.terms`, `controllers.public` | the package's | See [Controllers, policies and views](./controllers-policies-and-views.md) |
| `policies.post`, `policies.term` | `PostPolicy`, `TermPolicy` | See [Permissions](../basic-usage/permissions.md) |
| `register_permissions` | `true` | Register each post type's and taxonomy's permissions |
| `menus` | `true` | Register post types as menu sources when javaabu/dynamic-menubuilder is installed |

# The facade

`Javaabu\PostTypes\Facades\PostTypes` (alias `PostTypes`), backed by the `PostTypesManager` singleton:

| Method | |
| --- | --- |
| `register($types)` | A `PostType`, a class name, or a list |
| `registered(callable)` | Called for every post type, now and registered later |
| `forget($key)` | |
| `types()`, `has($key)`, `find($key)`, `get($key)`, `findBySlug($base)` | |
| `taxonomy($taxonomies)`, `taxonomies()`, `hasTaxonomy()`, `findTaxonomy()`, `getTaxonomy()`, `typesWithTaxonomy($key)` | |
| `status($statuses)`, `statuses()`, `getStatus($key)` | |
| `postModel()`, `termModel()` | The model classes in use |

`PostTypes::$runs_migrations = false` (in a provider's `register()`) also stops the migration loading.
