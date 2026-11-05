---
title: Controllers, policies and views
sidebar_position: 3
---

# Controllers

| Config | Default | Routes |
| --- | --- | --- |
| `post-types.controllers.posts` | `Javaabu\PostTypes\Http\Controllers\PostsController` | The admin post pages and bulk actions |
| `post-types.controllers.terms` | `Javaabu\PostTypes\Http\Controllers\TermsController` | The admin term pages |
| `post-types.controllers.public` | `Javaabu\PostTypes\Http\Controllers\PublicPostsController` | Archives and posts |

Extend one and set it in the config, or pass it to a macro for those routes only (`Route::postTypesAdmin(['controllers' => ['posts' => …]])`, `Route::postTypes(['controller' => …])`):

```php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Javaabu\PostTypes\Forms\PostForm;
use Javaabu\PostTypes\Http\Controllers\PostsController as BasePostsController;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\PostType;

class PostsController extends BasePostsController
{
    protected function saved(Request $request, Post $post, bool $created): void
    {
        if ($post->isPublished()) {
            cache()->forget("home.latest.{$post->type}");
        }
    }

    protected function form(Request $request, PostType $type, Post $post): PostForm
    {
        return \App\Forms\PostForm::for($post, can_publish: $this->gate($request)->allows('publish', $post->exists ? $post : $type));
    }
}
```

Hooks and building blocks you can override: `saved()`, `deleted()`, `form()`, `filters()`, `bulkActions()`, `tabs()`, `editView()`; in the public controller `publishedQuery()`, `paginate()`, `viewNames()` and `canSee()`. The current post type comes from the route (`PostTypeRoutes::postType($request)`), and the guard from `PostTypeRoutes::guard($request)`.

# Forms, filters and bulk actions

- `Javaabu\PostTypes\Forms\PostForm`: override `mainSection()`, `metaSection()`, `publishSection()`, `mediaSection()`, `taxonomiesSection()`, `taxonomyField()` or `definition()`.
- `Javaabu\PostTypes\Forms\TermForm`.
- `Javaabu\PostTypes\Filters\PostFilters` (`PostFilters::for($type)`): override `filters()` and `sorts()`.
- `Javaabu\PostTypes\BulkActions\PostBulkActions` (`PostBulkActions::for($type)`): override `actions()`.

```php
class PostFilters extends \Javaabu\PostTypes\Filters\PostFilters
{
    protected function filters(): array
    {
        return [
            ...parent::filters(),
            BooleanFilter::make('featured')->query(fn (Builder $query, bool $value) => $query->whereMeta('featured', $value)),
        ];
    }
}
```

Use your classes from your controller's `filters()` and `bulkActions()`.

# Policies

See [Permissions](../basic-usage/permissions.md#overriding).

# Blade views

Publish them with `php artisan vendor:publish --tag=post-types-views` and edit `resources/views/vendor/post-types/…`:

| View | |
| --- | --- |
| `admin/posts/index.blade.php`, `admin/posts/row.blade.php`, `admin/posts/edit.blade.php` | Admin posts |
| `admin/terms/index.blade.php`, `admin/terms/edit.blade.php` | Admin terms |
| `public/archive.blade.php`, `public/single.blade.php` | Public pages |
| `partials/card.blade.php`, `partials/meta.blade.php`, `partials/terms.blade.php` | Shared by the public pages |

For one post type's public pages, add `resources/views/post-types/{type}/{archive|term|single}.blade.php` instead. See [Public pages](../basic-usage/public-pages.md#templates).

# React files

Override any file of `resources/js` through manifest: put your copy at `resources/vendor/post-types/js/<same path>` (`php artisan manifest:override post-types js/Pages/Posts/Index.tsx` copies it for you). Your copy's relative imports still resolve to the package.
