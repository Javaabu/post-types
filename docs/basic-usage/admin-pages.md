---
title: Admin pages
sidebar_position: 4
---

`Route::postTypesAdmin()` adds, for each post type, pages to list, filter, create, edit, publish, trash and delete posts, and for each taxonomy those post types use, pages to manage its terms.

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::postTypesAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
});
```

| Option | Default | |
| --- | --- | --- |
| `guard` | `null` (the default guard) | The guard users sign in with; also used for permission checks |
| `prefix` | `admin` | The URL prefix |
| `name` | `admin.` | The route name prefix (keep the dot) |
| `middleware` | `auth`, or `auth:{guard}` | Replaces the default middleware |
| `only`, `except` | all post types | Post type keys |
| `taxonomies` | `true` | `false` leaves out the terms pages |
| `controllers` | `post-types.controllers` | `['posts' => …, 'terms' => …]` |

Call it once per guard to give several user types their own pages: `Route::postTypesAdmin(['guard' => 'web', 'prefix' => 'account', 'name' => 'account.', 'only' => ['news']])`.

# Routes

For a post type with the key `news`:

| Route | Name | |
| --- | --- | --- |
| `GET admin/news` | `admin.news.index` | The list |
| `GET admin/news/create` | `admin.news.create` | |
| `POST admin/news` | `admin.news.store` | |
| `GET admin/news/{post}/edit` | `admin.news.edit` | |
| `PATCH admin/news/{post}` | `admin.news.update` | |
| `DELETE admin/news/{post}` | `admin.news.destroy` | Moves it to the trash |
| `POST admin/news/{post}/restore` | `admin.news.restore` | |
| `DELETE admin/news/{post}/force` | `admin.news.force-delete` | Deletes it for good |
| `POST admin/news/bulk` | `admin.news.bulk` | Bulk actions |

Keys with underscores use dashes in URLs (`press_release` → `admin/press-release`). For a taxonomy with the key `categories`: `admin/taxonomies/categories` (`admin.taxonomies.categories.index`, `create`, `store`, `edit`, `update`, `destroy`).

Every route answers JSON when the request expects it: the index gives `data` (rows) and `meta` (pagination), edit gives `data` (the post) and `form` (stencil's JSON), store and update give `message` and `data`.

# The index

- **Status tabs**: All, each status with posts (Draft always), Scheduled, and Trash for users who can see it. Each shows its count.
- **Filters** ([javaabu/filters](https://github.com/Javaabu/filters)): search (title and excerpt; Thaana keyboard), status, each taxonomy, the publish date and the trash. Sort by title or publish date from the headings, or by creation and update date. The query string is filters' flat format: `?search=…&status=published&categories[]=3&orderby=title&order=ASC`.
- **Bulk actions** ([javaabu/bulk-actions](https://github.com/Javaabu/bulk-actions)): Publish, Move to drafts, Move to trash, Restore and Delete for good. They're offered to users who may run them, and every post is checked against the policy; posts the user can't change are skipped and reported. "Select all N" works across pages. Bulk actions on posts run during the request, a chunk at a time.
- **Rows**: thumbnail, title (a link to edit) and slug, status badge, author, terms, publish date, and View, Edit, Move to trash, Restore and Delete for good as the user may.

# The form

The post form is `Javaabu\PostTypes\Forms\PostForm`, a [stencil](https://github.com/Javaabu/stencil) form built from the post type:

- Title (required, Thaana), slug (made from the title when empty), content, excerpt.
- Details: the meta fields.
- Publish: status and publish date. Without the publish permission, the status only offers non-public statuses, the publish date is disabled, and a published post's status can't be changed.
- Media: the featured image and the post type's other pickers.
- Taxonomies: a searchable select per taxonomy, children indented under their parents.

New posts get the signed-in user as their author. After saving, the page goes back to the edit form with "News article saved.".

The term form (`TermForm`) has the name, slug, parent (hierarchical taxonomies; never the term itself or its descendants), order and description.

# Blade and Inertia

`post-types.views` picks the stack: `blade` (javaabu/forms v2 components, stencil's Blade driver, filters' and bulk-actions' components) or `inertia` (React pages through manifest). Both get the same data:

| Page | Blade view | Inertia page |
| --- | --- | --- |
| Posts | `post-types::admin.posts.index` | `post-types::Posts/Index` |
| Post form | `post-types::admin.posts.edit` | `post-types::Posts/Edit` |
| Terms | `post-types::admin.terms.index` | `post-types::Terms/Index` |
| Term form | `post-types::admin.terms.edit` | `post-types::Terms/Edit` |

The props are typed in `@javaabu/post-types` (`PostsIndexProps`, `PostsEditProps`, `TermsIndexProps`, `TermsEditProps`). Strings arrive translated in `strings`. See [JavaScript API](../advanced-usage/javascript-api.md).

The pages don't include a navigation menu: link to `route('admin.news.index')` from your admin layout, for example by looping over `PostTypes::types()`:

```blade
@foreach(\Javaabu\PostTypes\Facades\PostTypes::types() as $type)
    @can('viewAny', $type)
        <a href="{{ route('admin.' . $type->getKey() . '.index') }}">{{ $type->getPluralLabel() }}</a>
    @endcan
@endforeach
```
