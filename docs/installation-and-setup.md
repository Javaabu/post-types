---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

```bash
composer require javaabu/post-types
```

The service provider is discovered automatically, along with those of its dependencies.

# Migrating

The package loads its own migration: the `posts`, `post_terms` and `post_term` tables. javaabu/meta, javaabu/mediapicker and javaabu/bulk-actions load theirs (`meta`, `attachments`, `bulk_action_runs`). Publish spatie/laravel-medialibrary's `media` table, then migrate:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

If you installed a pre-release before queued bulk actions on posts, run `php artisan migrate` again after updating. It adds bulk-actions' `context` column to `bulk_action_runs`, where queued runs keep their post type.

To publish the package's migration instead of loading it (to add columns, say), publish it and turn the loading off:

```bash
php artisan vendor:publish --tag=post-types-migrations
```

```php
// config/post-types.php
'run_migrations' => false,
```

The table names are in `post-types.tables`.

# The media library

The featured image and other media are picked from javaabu/mediapicker's library. Follow its setup once: point spatie at its media model (`'media_model' => Javaabu\Mediapicker\Models\Media::class` in `config/media-library.php`), make your admin user model a `MediaOwner`, and register the library's routes for the guard your admins use:

```php
Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
```

See mediapicker's installation docs for the details.

# Defining post types

Register taxonomies and post types in a service provider's `boot()`:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        PostTypes::taxonomy(Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical());

        PostTypes::register(
            PostType::make('news')
                ->labels('News article', 'News')
                ->taxonomies(['categories'])
        );
    }
}
```

Or list them in `config/post-types.php` (instances or class names). See [Defining post types](basic-usage/defining-post-types.md).

# Routes

Routes aren't loaded automatically: add the admin and public routes where your app wants them, with its own guard, prefix, name and middleware:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::postTypesAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
    Route::postTypes();
});
```

Register the public routes after your own catch-all-free routes; a post type whose base is empty (`->slug('')`) puts its posts at the root. See [Admin pages](basic-usage/admin-pages.md) and [Public pages](basic-usage/public-pages.md).

# Permissions

Each post type and taxonomy registers its permissions with javaabu/permissions. Create them, then give them to your roles:

```bash
php artisan permissions:sync
```

See [Permissions](basic-usage/permissions.md).

# Layouts

The Blade pages extend `layouts.app` and fill its `content` section. Change it in the config:

```php
// config/post-types.php
'layout' => 'admin.layouts.app',          // admin pages
'public_layout' => 'layouts.public',      // archives and posts
'layout_section' => 'content',
```

# Inertia React

Set `post-types.views` to `inertia` (or `POST_TYPES_VIEWS=inertia` in `.env`). The pages are `post-types::Posts/Index`, `Posts/Edit`, `Terms/Index`, `Terms/Edit`, `Public/Archive`, `Public/Term` and `Public/Single`, resolved by manifest's `resolvePage()`:

```tsx
import { createInertiaApp } from "@inertiajs/react"
import { resolvePage } from "@javaabu/manifest/inertia"

createInertiaApp({
  resolve: (name) => resolvePage(name, import.meta.glob("./pages/**/*.tsx")),
  // ...
})
```

Install what the pages import (`php artisan manifest:check` lists it once your `package.json` has `@inertiajs/react`), and render them inside your layout and `FormsProvider` with the Inertia router, as for other javaabu/forms pages. Wrap the app in the media picker's `MediapickerProvider` so the post form's pickers know your library's URL.

The styles come with the components: `PostCard` and the public pages import `resources/css/post-types.css`, so Vite bundles it with any page that renders them. There's nothing to import in your app's CSS. (Blade pages get the same stylesheet from manifest's injection, as before.)

# Publishing

| Tag | What |
| --- | --- |
| `post-types-config` | `config/post-types.php` |
| `post-types-migrations` | The migration |
| `post-types-views` | The Blade views, to `resources/views/vendor/post-types` |
| `post-types-translations` | `lang/en.json` and `lang/dv.json`, to `lang/vendor/post-types` |

React files are overridden through manifest: put your copy at `resources/vendor/post-types/js/...`. See [Controllers, policies and views](advanced-usage/controllers-policies-and-views.md).
