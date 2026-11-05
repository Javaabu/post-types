---
title: Permalinks
sidebar_position: 6
---

A post's URL is its post type's **base** followed by its **permalink structure**:

```php
PostType::make('news')->slug('news')->permalink('{year}/{month}/{slug}');
// https://example.com/news/2026/11/ރާއްޖޭގެ-ޚަބަރު
```

| Token | Value | Matches |
| --- | --- | --- |
| `{slug}` | the post's slug | one URL segment |
| `{id}` | the post's id | digits |
| `{year}` | the publish year (or creation, for posts without one) | 4 digits |
| `{month}` | the month, two digits | 2 digits |
| `{day}` | the day, two digits | 2 digits |
| `{categories}` (any taxonomy key of the post type) | the slug of the post's first term in that taxonomy | one URL segment |

A structure needs `{slug}` or `{id}`, and uses each token once; anything else is plain text (`article-{id}`). Posts are found by slug (or id), so the other tokens only make the URL readable: a request with an old date or category is redirected (301) to the post's current permalink. A post with a taxonomy token and no term in that taxonomy has no permalink (`permalink()` is `null`).

The default structure is `{slug}` and the default base is the key in kebab case. An empty base (`->slug('')`) puts posts at the root (`/about`) and turns the archive off; register those public routes last.

# Slugs

Slugs are made from the title when left empty, and are unique within the post type (`ޚަބަރު`, `ޚަބަރު-2`). They keep Thaana letters and their fili, lowercase Latin letters and numbers; everything else becomes a dash. Admins can type their own in the form. `Javaabu\PostTypes\Support\Slug::make($text)` is the same function.

Thaana slugs are percent-encoded in URLs (`%DE%9A%DE%A6…`); browsers show them in Thaana.

# Configuring without code changes

`config('post-types.permalinks')` overrides a post type's base and structure by its key, so an app can change URLs without touching the definition (a package's post types, say):

```php
// config/post-types.php
'permalinks' => [
    'news' => ['base' => 'blog', 'structure' => '{year}/{slug}'],
],
```

Routes are registered when `Route::postTypes()` runs, so change permalinks before the routes load (in config, or in a service provider's `boot()`), and clear the route cache after a change.

# In code

```php
$post->permalink();                     // the full URL, or null
$post->permalinkPath();                 // "2026/11/ރާއްޖޭގެ-ޚަބަރު" (under the base)
$permalink = PostTypes::get('news')->getPermalink();
$permalink->getTokens();                // ['year', 'month', 'slug']
$permalink->routeUri();                 // "{post_year}/{post_month}/{post_slug}"
```
