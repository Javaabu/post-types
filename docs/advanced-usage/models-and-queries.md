---
title: Models and queries
sidebar_position: 2
---

# Post

`Javaabu\PostTypes\Models\Post` stores every post type's posts.

| Column | |
| --- | --- |
| `type` | The post type's key |
| `title`, `slug`, `excerpt` | |
| `content` | Block-editor JSON, cast with `AsBlockDocument` |
| `status`, `published_at` | |
| `author_type`, `author_id` | The `author()` morph |
| `translations`, `lang` | javaabu/translatable's JSON storage (unused without it) |
| `created_at`, `updated_at`, `deleted_at` | Soft deletes: the trash |

It uses javaabu/meta's `HasMeta`, javaabu/mediapicker's `InteractsWithAttachments` and `SoftDeletes`. Fillable: `type`, `title`, `slug`, `excerpt`, `content`, `status`, `published_at`, `meta`, `taxonomies`.

```php
use Javaabu\PostTypes\Models\Post;

$post = Post::create([
    'type' => 'news',
    'title' => 'ރާއްޖޭގެ ޚަބަރު',
    'status' => 'published',
    'meta' => ['subtitle' => 'ސަބްޓައިޓަލް'],
    'taxonomies' => ['categories' => [3]],
]);

$post->author()->associate($admin)->save();
```

Use `PostTypes::postModel()` (or `$type->getModel()`, `$type->query()`) rather than the class, so the translatable or configured model is used.

| Method | |
| --- | --- |
| `postType()` | The `PostType`, or null when it's no longer registered |
| `statusDefinition()`, `displayStatus()`, `displayStatusColor()` | |
| `isPublished()`, `isScheduled()`, `hasPublicStatus()` | |
| `isOwnedBy($user)` | |
| `author()`, `terms()`, `termsOf($taxonomy)`, `syncTerms($taxonomy, $terms)` | |
| `permalink()`, `permalinkPath()` | |
| `contentDocument(?$locale)`, `summary($limit)`, `featuredImageUrl($conversion)` | |
| `metaGroup()` | The type, for javaabu/meta |

| Scope | |
| --- | --- |
| `ofType($type)` | A key, keys or a `PostType` |
| `published()`, `scheduled()`, `unpublished()`, `withStatus($status)` | |
| `withTerms($taxonomy, $terms)` | Any of these terms: slugs, ids or terms |
| `byAuthor($user)` | |
| `latestPublished()` | Newest publish date first |
| `search($text)` | Title and excerpt (and translations), wildcards escaped |

Plus meta's scopes (`whereMeta`, `orderByMeta`, …) and mediapicker's `withAttachments()`.

```php
Post::query()
    ->ofType('news')
    ->published()
    ->withTerms('categories', 'sports')
    ->whereMeta('featured', true)
    ->latestPublished()
    ->with(['terms', 'author'])
    ->take(5)
    ->get();
```

# Your own model

Extend `Post` to add relations, conversions or methods, then point the config (every post type) or one post type at it:

```php
namespace App\Models;

use Javaabu\PostTypes\Models\Post as BasePost;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends BasePost
{
    public function registerAttachmentConversions(?Media $media = null): void
    {
        $this->addAttachmentConversion('card')
            ->fit(Fit::Crop, 600, 400)
            ->performOnCollections(self::FEATURED_IMAGE);
    }
}
```

```php
// config/post-types.php
'models' => ['post' => App\Models\Post::class],

// or for one post type
PostType::make('event')->model(App\Models\Event::class);
```

With javaabu/translatable, extend `Javaabu\PostTypes\Models\Translatable\Post` instead. Meta fields are registered for the base `Post`, so subclasses have them.

# Term

`Javaabu\PostTypes\Models\Term`: `taxonomy`, `name`, `slug`, `description`, `parent_id`, `order`. Relations `parent()`, `children()`, `posts()`; scopes `ofTaxonomy()`, `ordered()`, `search()`; `path()`, `archiveUrl($post_type)`, `isSelfOrDescendant($id)`, `Term::tree($taxonomy)`.
