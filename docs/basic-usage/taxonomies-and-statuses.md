---
title: Taxonomies and statuses
sidebar_position: 2
---

# Taxonomies

A taxonomy groups posts: categories, tags, topics, locations. Taxonomies are shared: define one once, then list it on the post types that use it. Terms live in the `post_terms` table, linked to posts through `post_term`.

```php
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Taxonomy;

PostTypes::taxonomy(Taxonomy::make('categories')->labels('Category', 'Categories')->hierarchical());
PostTypes::taxonomy(Taxonomy::make('tags'));
PostTypes::taxonomy(Taxonomy::make('atolls')->labels('Atoll', 'Atolls')->multiple(false)->required());

PostTypes::register(PostType::make('news')->taxonomies(['categories', 'tags']));
```

| Method | Default | |
| --- | --- | --- |
| `labels($singular, $plural)` | From the key | Translated when shown |
| `description($text)` | none | Shown on the terms page |
| `slug($segment)` | the key in kebab case | The URL segment of term archives: `/news/{slug}/{term}` |
| `hierarchical()` | off | Terms can have a parent. Term archives include the posts of child terms |
| `multiple(false)` | multiple | A post has one term at most |
| `required()` | off | A post needs a term |
| `public(false)` | public | No term archives |
| `permission($resource)` | the key, snake case | `view_{resource}`, `edit_{resource}`, `delete_{resource}` |
| `icon($name)` | `tags` | A Lucide icon name |

Like post types, taxonomies can be classes (extend `Taxonomy`, set `$key`, override `setUp()`) and listed in `config('post-types.taxonomies')`.

## Terms

Admins manage terms at `{prefix}/taxonomies/{taxonomy}` (see [Admin pages](./admin-pages.md)). In code:

```php
use Javaabu\PostTypes\Models\Term;

$sports = Term::create(['taxonomy' => 'categories', 'name' => 'ކުޅިވަރު']);       // slug ކުޅިވަރު
$football = Term::create(['taxonomy' => 'categories', 'name' => 'Football', 'parent_id' => $sports->id]);

$football->path();                   // "ކުޅިވަރު › Football"
$sports->children;                   // ordered by `order`, then id
$sports->posts;
Term::query()->ofTaxonomy('categories')->ordered()->get();
Term::tree('categories');            // [['term' => Term, 'depth' => 0], ...] in display order
$sports->archiveUrl('news');         // https://example.com/news/categories/ކުޅިވަރު
```

Slugs are made from the name and keep Thaana (with its fili); they're unique in the taxonomy. Deleting a term moves its children up to its parent and removes it from posts.

## A post's terms

```php
$post->termsOf('categories');                       // Collection<Term>
$post->syncTerms('categories', [$sports->id]);      // exactly these; other taxonomies are kept
$post->taxonomies;                                  // ['categories' => [1], 'tags' => [4, 7]]
$post->update(['taxonomies' => ['tags' => [4]]]);   // synced when the post is saved

Post::query()->withTerms('categories', ['ކުޅިވަރު', 3])->get();   // slugs, ids or terms
```

Ids that belong to another taxonomy are ignored.

# Statuses

A post has one status. Three are built in:

| Key | Label | Public | Badge |
| --- | --- | --- | --- |
| `draft` | Draft | no | secondary |
| `pending` | Pending review | no | warning |
| `published` | Published | yes | success |

Add your own, then list them on post types:

```php
use Javaabu\PostTypes\Status;

PostTypes::status(Status::make('archived')->label('Archived')->color('info'));
PostTypes::status(Status::make('members')->label('Members only')->public());

PostType::make('news')->statuses(['draft', 'published', 'archived']);
```

A public status shows the post on the site once its publish date has passed.

# Scheduling

Saving a post with a public status and no publish date sets it to now. A publish date in the future schedules the post: it appears in archives and at its permalink when the date comes, with no queue or cron. Admins see it as "Scheduled".

```php
$post->isPublished();   // public status, publish date passed
$post->isScheduled();   // public status, publish date in the future
$post->displayStatus(); // "Scheduled", "Trashed", or the status's label

Post::query()->published();     // what visitors see
Post::query()->scheduled();
Post::query()->unpublished();   // drafts, pending, scheduled, …
Post::query()->withStatus('pending');
```

Only users with the post type's `publish_*` permission can give a post a public status or set its publish date. Others save drafts and posts pending review.
