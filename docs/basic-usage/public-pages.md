---
title: Public pages
sidebar_position: 5
---

`Route::postTypes()` adds the pages visitors see, for each post type with public pages:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::postTypes();
});
```

| Option | Default | |
| --- | --- | --- |
| `prefix` | none | A URL prefix for every post type, such as a locale |
| `name` | none | A route name prefix (`public.`) |
| `middleware` | the group's | Extra middleware |
| `guard` | `null` (the default guard) | Who's signed in, for previews |
| `only`, `except` | all public post types | Post type keys |
| `controller` | `post-types.controllers.public` | |
| `default` | `false` | Use this registration for `$post->permalink()` when a post type is registered more than once |

For the post type `news` with base `news`:

| Route | Name | |
| --- | --- | --- |
| `GET news` | `news.index` | The archive: published posts, newest first, paginated (unless `->archive(false)`) |
| `GET news/{taxonomy}/{term}` | `news.term` | A term's archive, for each public taxonomy of the post type (`news/categories/sports`). Hierarchical taxonomies include the posts of child terms |
| `GET news/{permalink}` | `news.show` | A post. See [Permalinks](./permalinks.md) |

Only published posts are shown. A draft, pending or scheduled post opens for users who can edit it, with a "This is a preview" notice; everyone else gets a 404. Every page answers JSON when the request expects it.

```php
$post->permalink();                         // the post's URL, or null (no public routes, or a taxonomy token without a term)
PostTypes::get('news')->archiveUrl();       // https://example.com/news
$term->archiveUrl('news');                  // https://example.com/news/categories/sports
```

# Templates

The Blade views are found WordPress-style: the first that exists wins.

| Page | Views |
| --- | --- |
| Archive | `->views(['archive' => …])`, `post-types.{type}.archive`, `post-types.archive`, `post-types::public.archive` |
| Term archive | `->views(['term' => …])`, `post-types.{type}.term`, `post-types.term`, then the archive's |
| Post | `->views(['single' => …])`, `post-types.{type}.single`, `post-types.single`, `post-types::public.single` |

So `resources/views/post-types/news/single.blade.php` is used for news posts, and `resources/views/post-types/single.blade.php` for every other post type. Views get:

| Variable | Archive and term | Post |
| --- | --- | --- |
| `$post_type` | ✓ (`PostType::toArray()`) | ✓ |
| `$posts` | cards: `id`, `title`, `slug`, `url`, `summary`, `published_at`, `published_at_label`, `author`, `image`, `terms` | |
| `$paginator` | the paginator (`$paginator->links()`) | |
| `$pagination` | `current_page`, `last_page`, `total`, `prev_url`, `next_url` | |
| `$taxonomy`, `$term` | on term archives | |
| `$title` | the post type's plural label, or the term's name | |
| `$post` | | the card plus `excerpt`, `content`, `content_html`, `meta`, `status`, `published` |
| `$record` | | the `Post` model |
| `$preview` | | whether it isn't published |
| `$archive_url`, `$strings`, `$layout`, `$layout_section` | ✓ | ✓ |

```blade
{{-- resources/views/post-types/news/single.blade.php --}}
@extends('layouts.public')

@section('content')
    <article>
        <h1 dir="auto">{{ $post['title'] }}</h1>
        <p>{{ $record->getMeta('subtitle') }}</p>
        <x-block-editor::content :content="$record->contentDocument()" />
    </article>
@endsection
```

The package's views use the partials `post-types::partials.card`, `partials.meta` and `partials.terms`, and the styles `post-types::styles` (logical CSS; `.post-types-archive`, `.post-types-card`, `.post-types-meta`, `.post-types-terms`, `.post-types-pagination`). Publish them with `--tag=post-types-views` to change them for every post type.

# Inertia

With `post-types.views` set to `inertia`, the pages are `post-types::Public/Archive`, `Public/Term` and `Public/Single`, with the same props (without `$paginator` and `$record`). Use your own per post type with `->pages(['single' => 'News/Show'])`, or override the package's through manifest (`resources/vendor/post-types/js/Pages/Public/Single.tsx`).
