# Post Types

[![Tests](https://github.com/Javaabu/post-types/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/Javaabu/post-types/actions/workflows/tests.yml?query=branch%3Amain)
[![codecov](https://codecov.io/gh/Javaabu/post-types/branch/main/graph/badge.svg)](https://codecov.io/gh/Javaabu/post-types)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/post-types.svg)](https://packagist.org/packages/javaabu/post-types)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

WordPress-style post types for Laravel. Define news, events or pages fluently, and get admin pages, public archives and permalinks for each.

- Post types with labels, features (content, excerpt, media, meta, author, scheduling), taxonomies, statuses and scheduling.
- Meta fields with [javaabu/meta](https://github.com/Javaabu/meta), media with [javaabu/mediapicker](https://github.com/Javaabu/mediapicker), content with [javaabu/block-editor](https://github.com/Javaabu/block-editor).
- Admin pages from `Route::postTypesAdmin()` with [javaabu/filters](https://github.com/Javaabu/filters) and [javaabu/bulk-actions](https://github.com/Javaabu/bulk-actions), in Blade or Inertia React.
- Public archives, term archives and single pages from `Route::postTypes()`, with configurable permalinks and overridable templates.
- Permissions per post type through [javaabu/permissions](https://github.com/Javaabu/permissions), for any guard and user model.
- Dhivehi and RTL; optional translations with [javaabu/translatable](https://github.com/Javaabu/translatable).

```php
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;
use Javaabu\Stencil\Fields\TextInput;

// AppServiceProvider::boot()
PostTypes::taxonomy(Taxonomy::make('categories')->hierarchical());

PostTypes::register(
    PostType::make('news')
        ->labels('News article', 'News')
        ->supports(['content', 'excerpt', 'media', 'meta'])
        ->taxonomies(['categories'])
        ->metaFields([TextInput::make('subtitle')])
        ->permalink('{year}/{month}/{slug}')
);

// routes/web.php
Route::postTypesAdmin(['guard' => 'web_admin', 'prefix' => 'admin', 'name' => 'admin.']);
Route::postTypes();
```

## Documentation

See the [docs](docs/intro.md), or [llms.txt](llms.txt) for AI assistants.

## License

The MIT License (MIT). See [License File](LICENSE.md).
