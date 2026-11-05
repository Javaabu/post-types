{{--
    javaabu/post-types guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

@verbatim
## javaabu/post-types

- News, events, pages and other kinds of posts are post types: register them in a service provider with `PostTypes::register(PostType::make('news')->labels('News article', 'News')->supports([...])->taxonomies(['categories'])->metaFields([...])->permalink('{year}/{slug}'))` and taxonomies with `PostTypes::taxonomy(Taxonomy::make('categories')->hierarchical())`. Never create a model, migration, controller or CRUD views per post type: every post type's posts are `Javaabu\PostTypes\Models\Post` rows with a `type`.
- Extra fields are `->metaFields([...])` (javaabu/meta, stencil fields), media pickers are `->media([MediaPickerField::make(...)])` (javaabu/mediapicker), and the body is block-editor content. Query with `$type->query()->published()->withTerms('categories', 'sports')->whereMeta('featured', true)`.
- Routes come from `Route::postTypesAdmin(['guard' => ..., 'prefix' => 'admin', 'name' => 'admin.'])` and `Route::postTypes()`; permissions are `view_/edit_/delete_/force_delete_/publish_{type}` (run `php artisan permissions:sync`). A post's URL is `$post->permalink()`, not `url()`.
- Override public templates with `resources/views/post-types/{type}/{archive|term|single}.blade.php` or `->views()` / `->pages()`.
- Always activate the `javaabu-post-types` skill when adding or changing post types, taxonomies, meta fields, permalinks, the admin or public pages, or querying posts.
@endverbatim
