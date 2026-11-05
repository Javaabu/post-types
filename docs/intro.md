---
title: Introduction
sidebar_position: 1.0
---

# Post Types

[Post Types](https://github.com/Javaabu/post-types) brings WordPress-style post types to Laravel. You define a kind of post (news, events, pages, jobs) in a few lines, and the package gives it:

- **storage**: one `posts` table for every post type, with a title, slug, excerpt, block-editor content, a status, a publish date and an author;
- **taxonomies**: categories, tags or your own, shared between post types, flat or hierarchical;
- **statuses and scheduling**: draft, pending review and published (and your own); a published post with a future publish date is scheduled and appears on its own;
- **meta fields** with [javaabu/meta](https://github.com/Javaabu/meta), **media** with [javaabu/mediapicker](https://github.com/Javaabu/mediapicker) and **content** with [javaabu/block-editor](https://github.com/Javaabu/block-editor);
- **admin pages** with [javaabu/filters](https://github.com/Javaabu/filters) and [javaabu/bulk-actions](https://github.com/Javaabu/bulk-actions), in Blade or Inertia React;
- **public pages**: an archive, term archives and single posts, with permalinks you configure and templates you override;
- **permissions** per post type through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model.

```php
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

// AppServiceProvider::boot()
PostTypes::taxonomy(Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical());

PostTypes::register(
    PostType::make('news')
        ->labels('News article', 'News')
        ->supports(['content', 'excerpt', 'media', 'meta', 'author', 'scheduling'])
        ->taxonomies(['categories'])
        ->metaFields([
            TextInput::make('subtitle')->maxLength(255),
            Checkbox::make('featured'),
        ])
        ->permalink('{year}/{month}/{slug}')
);
```

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::postTypesAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
    Route::postTypes();
});
```

That's `/admin/news` for admins (list, filter, create, edit, publish, trash), `/admin/taxonomies/categories` for the categories, and `/news`, `/news/categories/{term}` and `/news/2026/11/{slug}` for visitors.

# What's included

- **Definitions**: `PostType`, `Taxonomy` and `Status`, fluently or as classes. See [Defining post types](basic-usage/defining-post-types.md) and [Taxonomies and statuses](basic-usage/taxonomies-and-statuses.md).
- **Meta, media and content**: see [Meta, media and content](basic-usage/meta-media-and-content.md).
- **Admin pages**: see [Admin pages](basic-usage/admin-pages.md).
- **Public pages and permalinks**: see [Public pages](basic-usage/public-pages.md) and [Permalinks](basic-usage/permalinks.md).
- **Permissions**: see [Permissions](basic-usage/permissions.md).
- **Translations**: optional, with javaabu/translatable. See [Translations](advanced-usage/translations.md).
- **Menus**: posts and archives as menu items, with javaabu/dynamic-menubuilder. See [Menus](advanced-usage/menus.md).
- **Dhivehi and RTL**: see [Dhivehi and RTL](advanced-usage/dhivehi-and-rtl.md).
