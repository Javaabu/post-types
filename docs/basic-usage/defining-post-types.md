---
title: Defining post types
sidebar_position: 1
---

A post type is a `PostType` definition registered with the `PostTypes` facade. Every post type's posts live in the same `posts` table; a post's `type` column is its post type's key.

```php
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;

PostTypes::register(
    PostType::make('news')
        ->labels('News article', 'News')
        ->description('Announcements and press releases')
        ->icon('newspaper')
        ->supports(['content', 'excerpt', 'media', 'meta', 'author', 'scheduling'])
        ->taxonomies(['categories', 'tags'])
        ->statuses(['draft', 'pending', 'published'])
        ->metaFields([
            TextInput::make('subtitle')->maxLength(255)->translatable(),
            Checkbox::make('featured'),
        ])
        ->media([MediaPickerField::make('gallery')->mediaType('image')->multiple()])
        ->blockProfile('default')
        ->slug('news')
        ->permalink('{year}/{month}/{slug}')
        ->archive(per_page: 12)
        ->perPage(25)
        ->order(1)
);
```

The key (`news`) starts with a letter and has lowercase letters, numbers, dashes and underscores. Don't change it once posts are saved: it's stored on every post, and it names the routes and the permissions.

# Methods

| Method | Default | |
| --- | --- | --- |
| `labels($singular, $plural)` | From the key | The names admins and visitors see. Strings are translated when shown, so add them to your `lang/dv.json`; closures are called each time |
| `description($text)` | none | Shown under the admin index's heading |
| `icon($name)` | `file-text` | A Lucide icon name, for your admin menu |
| `supports([...])` | content, excerpt, media, author, scheduling | The features besides the title and slug. `with(...)` and `without(...)` add and remove some |
| `taxonomies([...])` | none | Taxonomy keys, or `Taxonomy` instances (registered for you). See [Taxonomies and statuses](./taxonomies-and-statuses.md) |
| `statuses([...])` | draft, pending, published | Status keys or `Status` instances. The first is the default for new posts |
| `defaultStatus($key)` | the first status | |
| `metaFields([...])` | none | javaabu/meta fields (stencil fields, or `key => type`), or a closure. Adds the `meta` feature. See [Meta, media and content](./meta-media-and-content.md) |
| `media([...])` | none | More media pickers besides the featured image |
| `blockProfile($name)` | `default` | The javaabu/block-editor profile of the content |
| `slug($base)` | the key in kebab case | The URL base of the archive and posts |
| `permalink($structure)` | `{slug}` | See [Permalinks](./permalinks.md) |
| `public($bool)` | `true` | Without public pages, the post type has admin pages only |
| `archive($bool, per_page: $n)` | `true`, `post-types.archive_per_page` | The public list of posts |
| `perPage($n)` | `post-types.per_page` | Rows on the admin index |
| `ownable()` | off | Authors edit and delete their own posts only, unless they have `edit_others_*` / `delete_others_*`. See [Permissions](./permissions.md) |
| `permission($resource)` | the key, snake case, plural | The permissions' suffix: `view_{resource}` |
| `model($class)` | `post-types.models.post` | A `Post` subclass for this post type's posts |
| `views([...])`, `pages([...])` | none | Blade views and Inertia pages for the public pages. See [Public pages](./public-pages.md) |
| `order($n)` | `0` | The order of `PostTypes::types()`, for menus |

# Features

| Feature | Constant | Adds |
| --- | --- | --- |
| `content` | `PostType::CONTENT` | The block-editor content |
| `excerpt` | `PostType::EXCERPT` | A short summary; lists fall back to the start of the content |
| `media` | `PostType::MEDIA` | A featured image (`featured_image` attachment collection) |
| `meta` | `PostType::META` | The fields from `metaFields()` |
| `author` | `PostType::AUTHOR` | The author column and byline. Posts always record who created them |
| `scheduling` | `PostType::SCHEDULING` | A publish date admins can set; a future date schedules the post |

```php
PostType::make('page')->supports(['content'])->archive(false);
PostType::make('job')->without(PostType::MEDIA)->with(PostType::META);
```

# Classes

For a post type with more to it, extend `PostType`, set the key and configure it in `setUp()`. List the class in the config or register it:

```php
namespace App\PostTypes;

use Javaabu\PostTypes\PostType;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\TextInput;

class EventPostType extends PostType
{
    protected string $key = 'event';

    protected function setUp(): void
    {
        $this->labels(fn () => __('Event'), fn () => __('Events'))
            ->icon('calendar')
            ->taxonomies(['categories'])
            ->metaFields(fn () => [
                DatePicker::make('starts_on')->required(),
                TextInput::make('venue')->thaana(),
            ])
            ->permalink('{year}/{slug}');
    }
}
```

```php
// config/post-types.php
'types' => [
    App\PostTypes\EventPostType::class,
],
```

# The registry

```php
PostTypes::types();             // ['news' => PostType, ...] in their order
PostTypes::get('news');         // throws for an unknown key; find() returns null
PostTypes::has('news');
PostTypes::findBySlug('news');  // by URL base
PostTypes::registered(fn (PostType $type) => ...);   // every post type, now and registered later
PostTypes::forget('news');
```

A post type registered again with the same key replaces the earlier one, meta fields included.

# Reading a definition

```php
$news = PostTypes::get('news');

$news->getSingularLabel();          // "News article", translated
$news->supportsFeature('content');
$news->getTaxonomies();             // ['categories' => Taxonomy, ...]
$news->getStatuses();               // ['draft' => Status, ...]
$news->getPublicStatuses();         // ['published']
$news->permissionFor('edit');       // "edit_news"
$news->query();                     // a query for its posts
$news->newPost();                   // an unsaved post of this type with the default status
$news->getPermalink()->pathFor($post);
$news->toArray();                   // what the pages get
```
