# Common mistakes with javaabu/post-types

- **A model and migration per post type.** `News`, `Event` and `Page` models with their own tables duplicate what a post type gives you. Register `PostType::make('news')`; add fields with `->metaFields()`. Use `->model(App\Models\Event::class)` (a `Post` subclass) only for relations or methods.
- **Querying posts without the type.** `Post::published()->get()` mixes every post type. Start from `PostTypes::get('news')->query()` or `Post::query()->ofType('news')`.
- **Showing unpublished posts.** `where('status', 'published')` includes scheduled posts. Use the `published()` scope (public status and a publish date that has passed).
- **`$post->url()`.** It's javaabu/translatable's method on translatable posts. The permalink is `$post->permalink()`; a term's archive is `$term->archiveUrl('news')`; an archive is `PostTypes::get('news')->archiveUrl()`.
- **Using `Post::class` directly.** With javaabu/translatable enabled the package uses `Models\Translatable\Post`. Use `PostTypes::postModel()` or `$type->getModel()`.
- **Changing a post type's key.** Posts, routes and permissions use it. Change the labels or `->slug()` instead.
- **Translated labels at boot.** `->labels(__('News'))` translates once in the boot locale. Pass plain strings (translated when shown) or closures.
- **Meta conditions without the path.** Meta fields sit under `meta` in the post form: `visibleWhen('meta.featured', true)`, not `visibleWhen('featured', true)`.
- **Writing terms by hand.** `DB::table('post_term')->insert(...)` skips taxonomy checks. Use `$post->syncTerms('categories', [...])` or the `taxonomies` attribute.
- **Hand-made permalinks.** `url("news/{$post->slug}")` breaks when the structure or base changes. Use `$post->permalink()`.
- **Public routes before your own.** A post type with `->slug('')` catches `/{slug}`. Register `Route::postTypes()` after your other routes.
- **Forgetting the sync.** Permissions exist only after `php artisan permissions:sync`; without them every page is 403.
- **Forgetting the media library.** The featured image picker needs `Route::mediapicker()` for the admin guard and a `MediaOwner` user model.
