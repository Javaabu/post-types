# javaabu/post-types API

## PostType

`PostType::make(string $key)` or a subclass with `protected string $key` and `setUp()`.

| Method | Default |
| --- | --- |
| `labels($singular, $plural)` | headline of the key |
| `description($text)`, `icon($lucide)` | none, `file-text` |
| `supports([...])`, `with(...)`, `without(...)` | `content`, `excerpt`, `media`, `author`, `scheduling` (also `meta`) |
| `taxonomies([...])` | none (keys or `Taxonomy`) |
| `statuses([...])`, `defaultStatus($key)` | `draft`, `pending`, `published`; first is default |
| `metaFields(array\|Closure)` | none; adds `meta`; meta group = key |
| `media(array\|Closure)` | none; `MediaPickerField`s, saved to their collection |
| `blockProfile($name)` | `default` |
| `slug($base)`, `permalink($structure)` | kebab key, `{slug}` |
| `public($bool)`, `archive($bool, per_page: $n)`, `perPage($n)` | public, archive, config |
| `ownable()` | off |
| `permission($resource)` | snake plural key |
| `model($class)` | `post-types.models.post` |
| `views([...])`, `pages([...])` | `archive`, `term`, `single` |
| `order($n)` | 0 |

Readers: `getKey()`, `getSingularLabel()`, `getPluralLabel()`, `supportsFeature()`, `getTaxonomies()`, `hasTaxonomy()`, `getStatuses()`, `getStatus()`, `getPublicStatuses()`, `getDefaultStatus()`, `getMediaFields()`, `getBlockProfile()`, `getSlug()`, `getPermalinkStructure()`, `getPermalink()`, `isPublic()`, `hasArchive()`, `archiveUrl()`, `isOwnable()`, `permissionFor($action)`, `permissions()`, `getModel()`, `query()`, `newPost()`, `toArray()`.

Constants: `PostType::CONTENT`, `EXCERPT`, `MEDIA`, `META`, `AUTHOR`, `SCHEDULING`.

## Taxonomy

`Taxonomy::make($key)`: `labels()`, `description()`, `slug()`, `hierarchical()`, `multiple(false)`, `required()`, `public(false)`, `permission()`, `icon()`. `permissionFor('view'|'edit'|'delete')`.

## Status

`Status::make($key)->label()->color('primary'|'secondary'|'success'|'danger'|'warning'|'info')->public()`. Built-in: `Status::DRAFT`, `PENDING`, `PUBLISHED` (public).

## PostTypes facade

`register()`, `registered(callable)`, `forget()`, `types()`, `has()`, `find()`, `get()`, `findBySlug()`, `taxonomy()`, `taxonomies()`, `hasTaxonomy()`, `findTaxonomy()`, `getTaxonomy()`, `typesWithTaxonomy()`, `status()`, `statuses()`, `getStatus()`, `postModel()`, `termModel()`.

## Post model

Columns `type`, `title`, `slug`, `excerpt`, `content` (Document), `status`, `published_at`, `author_type`/`author_id`, `translations`, `lang`, timestamps, `deleted_at`. Fillable: those plus `meta` and `taxonomies`.

Methods: `postType()`, `author()`, `terms()`, `termsOf($taxonomy)`, `syncTerms($taxonomy, $terms)`, `isPublished()`, `isScheduled()`, `hasPublicStatus()`, `displayStatus()`, `displayStatusColor()`, `isOwnedBy($user)`, `permalink()`, `permalinkPath()`, `contentDocument(?$locale)`, `summary($limit)`, `featuredImageUrl($conversion)`, plus javaabu/meta (`getMeta`, `setMeta`, …) and mediapicker attachments (`getFirstAttachment`, …). `Post::FEATURED_IMAGE` = `featured_image`.

Scopes: `ofType()`, `published()`, `scheduled()`, `unpublished()`, `withStatus()`, `withTerms($taxonomy, $terms)`, `byAuthor($user)`, `latestPublished()`, `search($text)`.

## Term model

`taxonomy`, `name`, `slug`, `description`, `parent_id`, `order`. `parent()`, `children()`, `posts()`, `ofTaxonomy()`, `ordered()`, `search()`, `path()`, `archiveUrl($type)`, `Term::tree($taxonomy)`.

## Routes

`Route::postTypesAdmin(['guard', 'prefix' => 'admin', 'name' => 'admin.', 'middleware', 'only', 'except', 'taxonomies' => true, 'controllers' => ['posts', 'terms']])`:
`{prefix}/{type}` → `{name}{type}.index|create|store|edit|update|destroy|restore|force-delete|bulk`; `{prefix}/taxonomies/{taxonomy}` → `{name}taxonomies.{taxonomy}.index|create|store|edit|update|destroy`.

`Route::postTypes(['prefix' => '', 'name' => '', 'middleware', 'guard', 'only', 'except', 'controller', 'default'])`:
`{base}` → `{name}{type}.index`; `{base}/{taxonomy}/{term}` → `{name}{type}.term`; `{base}/{permalink}` → `{name}{type}.show`.

Permalink tokens: `{slug}`, `{id}`, `{year}`, `{month}`, `{day}`, `{<taxonomy key>}`.

## Permissions and policies

Per type: `view_x`, `edit_x`, `delete_x`, `force_delete_x`, `publish_x` (+ `edit_others_x`, `delete_others_x` with `ownable()`). Per taxonomy: `view_x`, `edit_x`, `delete_x`.
`PostPolicy` abilities: `viewAny(PostType)`, `view`, `create(PostType)`, `update`, `delete`, `restore`, `forceDelete`, `viewTrash(PostType)`, `publish(Post|PostType)`. `TermPolicy`: `viewAny(Taxonomy)`, `view`, `create(Taxonomy)`, `update`, `delete`.

## Config (`config/post-types.php`)

`types`, `taxonomies`, `permalinks`, `models.post`, `models.term`, `tables.*`, `run_migrations`, `views` (`blade`|`inertia`), `layout`, `public_layout`, `layout_section`, `per_page`, `archive_per_page`, `controllers.posts|terms|public`, `policies.post|term`, `register_permissions`, `menus`.

## Views and pages

Blade: `post-types::admin.posts.index|edit`, `admin.terms.index|edit`; public lookup `->views()`, `post-types.{type}.{page}`, `post-types.{page}`, `post-types::public.{page}`.
Inertia: `post-types::Posts/Index|Edit`, `Terms/Index|Edit`, `Public/Archive|Term|Single`.

## React (`@javaabu/post-types`)

`PostsIndex`, `PostsEdit` (`components` prop), `TermsIndex`, `TermsEdit`, `PublicArchive`, `PublicTerm`, `PublicSingle`, `PostCard`, `PostMeta`, `PostTerms`, `PostTitle`, `PostActions`, `StatusTabs`, `postFormComponents`, `format()`; types for every page's props.

## Classes to extend

`Http\Controllers\PostsController` (`saved()`, `deleted()`, `form()`, `filters()`, `bulkActions()`), `TermsController`, `PublicPostsController` (`publishedQuery()`, `viewNames()`, `canSee()`), `Forms\PostForm` (section methods), `Forms\TermForm`, `Filters\PostFilters`, `BulkActions\PostBulkActions`, `Policies\PostPolicy`, `Policies\TermPolicy`.
