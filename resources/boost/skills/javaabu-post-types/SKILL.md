---
name: javaabu-post-types
description: Add WordPress-style post types to a Laravel app with javaabu/post-types. Use when defining post types (PostTypes::register, PostType::make with labels, supports, taxonomies, statuses, metaFields, media, permalink), taxonomies and terms, querying posts (Post::published, withTerms, whereMeta), setting up the admin pages (Route::postTypesAdmin, per-type permissions, Blade or Inertia), public archives and single posts (Route::postTypes, permalinks, templates), translatable posts, menus, or overriding the controllers, policies, forms or views.
license: MIT
metadata:
  author: Javaabu
---

# Post types with javaabu/post-types

The docs ship with the package: `vendor/javaabu/post-types/llms.txt` lists every page in `vendor/javaabu/post-types/docs/`.

| Task | Read |
| --- | --- |
| A post type, its routes, querying and templates | This file |
| Every method, option, route, prop and config key | [references/api.md](references/api.md) |
| Mistakes to avoid | [references/common-mistakes.md](references/common-mistakes.md) |

## 1. Define taxonomies and post types

```php
// app/Providers/AppServiceProvider.php
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

public function boot(): void
{
    PostTypes::taxonomy(Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical());
    PostTypes::taxonomy(Taxonomy::make('tags'));

    PostTypes::register(
        PostType::make('news')
            ->labels('News article', 'News')
            ->supports(['content', 'excerpt', 'media', 'meta', 'author', 'scheduling'])
            ->taxonomies(['categories', 'tags'])
            ->metaFields([
                TextInput::make('subtitle')->maxLength(255)->translatable(),
                Checkbox::make('featured'),
            ])
            ->media([MediaPickerField::make('gallery')->mediaType('image')->multiple()])
            ->permalink('{year}/{month}/{slug}')
    );

    PostTypes::register(PostType::make('page')->labels('Page', 'Pages')->supports(['content'])->archive(false));
}
```

Labels are translated when shown: add them to `lang/dv.json`. Keys never change once posts exist.

## 2. Add the routes and permissions

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::postTypesAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
    Route::postTypes();
});
```

```bash
php artisan permissions:sync
```

Admins get `admin/news` (`admin.news.index`, create, edit, trash, bulk) and `admin/taxonomies/categories`. Visitors get `/news`, `/news/categories/{term}` and `/news/2026/11/{slug}`. Give roles `view_news`, `edit_news`, `delete_news`, `force_delete_news`, `publish_news`, and `view_/edit_/delete_categories`.

## 3. Query posts

```php
use Javaabu\PostTypes\Facades\PostTypes;

$latest = PostTypes::get('news')->query()
    ->published()
    ->withTerms('categories', 'sports')
    ->whereMeta('featured', true)
    ->latestPublished()
    ->with(['terms', 'author'])
    ->take(5)
    ->get();

foreach ($latest as $post) {
    $post->permalink();
    $post->featuredImageUrl();
    $post->summary();
    $post->getMeta('subtitle');
    $post->termsOf('categories');
}
```

Create posts in code with the type and fillable attributes; slugs, publish dates, meta and terms are handled:

```php
Post::create([
    'type' => 'news',
    'title' => 'ރާއްޖޭގެ ޚަބަރު',
    'status' => 'published',
    'meta' => ['subtitle' => 'ސަބްޓައިޓަލް'],
    'taxonomies' => ['categories' => [$sports->id]],
]);
```

## 4. Templates

Put a Blade view at `resources/views/post-types/news/single.blade.php` (or `archive`, `term`) to replace the package's for news; it gets `$post` (array), `$record` (the model), `$strings` and `$layout`. For Inertia, `->pages(['single' => 'News/Show'])` with `PublicSingleProps` from `@javaabu/post-types`. `PostCard` and the public pages import the package's stylesheet, so Vite bundles it: don't `@import` `post-types.css` in the app's CSS. The npm packages the pages need (React, `@javaabu/forms`, `@javaabu/onu`) are declared for pages only, so `php artisan manifest:check` asks for them only when the app's `package.json` has `@inertiajs/react`.

```blade
@extends('layouts.public')

@section('content')
    <h1 dir="auto">{{ $post['title'] }}</h1>
    <x-block-editor::content :content="$record->contentDocument()" />
@endsection
```

## 5. Change behaviour

Extend `PostsController`, `PostForm`, `PostFilters`, `PostBulkActions` or `PostPolicy` and point `config('post-types.controllers'/'policies')` at your classes. Don't add your own routes for posts.
