---
title: Menus
sidebar_position: 5
---

With [javaabu/dynamic-menubuilder](https://github.com/Javaabu/dynamic-menubuilder) installed, every public post type is a source of menu items: admins can add a post, or the post type's archive as its index page, to any menu. It's optional and needs no setup.

```bash
composer require javaabu/dynamic-menubuilder
```

Each source is keyed by the post type's key (`news`), labelled with its plural label, and limited to that post type's posts. In menus:

| | |
| --- | --- |
| Title | The post's title in the menu's language |
| URL | `$post->permalink()` |
| Shown | Published posts only: drafts and scheduled posts drop out of menus until they're published |
| Index page | The archive, for post types with one |
| Search | The builder searches titles and excerpts |

When a post is deleted for good, its menu items are removed and their children move up. Trashed posts stay in menus (hidden, since they aren't published) so restoring one brings its items back.

To keep post types out of menus, set `post-types.menus` to `false`, or register sources yourself:

```php
use Javaabu\DynamicMenubuilder\Facades\Menus;

Menus::linkable(Post::class, 'featured-news')
    ->label(fn () => __('Featured news'))
    ->query(fn (Builder $query) => $query->ofType('news')->whereMeta('featured', true));
```

`Post` implements `Javaabu\PostTypes\Contracts\MenuLinkable`, which is dynamic-menubuilder's `MenuLinkable` when it's installed and an empty interface when it isn't.
