<?php

namespace Javaabu\PostTypes;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Javaabu\PostTypes\Exceptions\PostTypesException;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Routing\Permalink;
use Javaabu\Stencil\Components\Component;

/**
 * A kind of post: news, events, pages, jobs. Defined fluently, or as a subclass
 * that sets its properties and overrides setUp().
 *
 * ```php
 * PostTypes::register(
 *     PostType::make('news')
 *         ->labels('News article', 'News')
 *         ->supports(['content', 'excerpt', 'media', 'meta'])
 *         ->taxonomies(['categories', 'tags'])
 *         ->metaFields([TextInput::make('subtitle')->translatable(), Checkbox::make('featured')])
 *         ->permalink('{year}/{month}/{slug}')
 * );
 * ```
 *
 * @implements Arrayable<string, mixed>
 */
class PostType implements Arrayable
{
    /** The post body, edited with javaabu/block-editor */
    public const string CONTENT = 'content';

    /** A short summary */
    public const string EXCERPT = 'excerpt';

    /** A featured image, picked with javaabu/mediapicker */
    public const string MEDIA = 'media';

    /** The fields from metaFields(), stored with javaabu/meta */
    public const string META = 'meta';

    /** The author shown on posts */
    public const string AUTHOR = 'author';

    /** A publish date admins can set, in the past or the future */
    public const string SCHEDULING = 'scheduling';

    /** @var array<int, string> */
    public const array FEATURES = [self::CONTENT, self::EXCERPT, self::MEDIA, self::META, self::AUTHOR, self::SCHEDULING];

    protected string $key = '';

    protected Closure|string|null $singular = null;

    protected Closure|string|null $plural = null;

    protected Closure|string|null $description = null;

    protected string $icon = 'file-text';

    /** @var array<int, string> */
    protected array $supports = [self::CONTENT, self::EXCERPT, self::MEDIA, self::AUTHOR, self::SCHEDULING];

    /** @var array<int, string> */
    protected array $taxonomies = [];

    /** @var array<int, string|Status> */
    protected array $statuses = [Status::DRAFT, Status::PENDING, Status::PUBLISHED];

    protected string $default_status = Status::DRAFT;

    /** @var array<int|string, mixed>|Closure|null */
    protected array|Closure|null $meta_fields = null;

    /** @var array<int, Component>|Closure|null */
    protected array|Closure|null $media_fields = null;

    protected string $block_profile = 'default';

    protected ?string $slug = null;

    protected string $permalink = '{slug}';

    protected bool $public = true;

    protected bool $has_archive = true;

    protected ?int $archive_per_page = null;

    protected ?int $per_page = null;

    protected bool $ownable = false;

    protected ?string $permission = null;

    /** @var class-string<Post>|null */
    protected ?string $model = null;

    /** @var array<string, string> */
    protected array $views = [];

    /** @var array<string, string> */
    protected array $pages = [];

    protected int $order = 0;

    public function __construct(?string $key = null)
    {
        $this->key = $key ?? $this->key;

        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $this->key)) {
            throw PostTypesException::invalidKey('post type', $this->key);
        }

        $this->setUp();
    }

    public static function make(?string $key = null): static
    {
        return new static($key);
    }

    /**
     * Configure a subclass
     */
    protected function setUp(): void {}

    /*
    |--------------------------------------------------------------------------
    | Definition
    |--------------------------------------------------------------------------
    */

    /**
     * The names shown to admins and visitors. Strings are translated when shown.
     */
    public function labels(Closure|string|null $singular = null, Closure|string|null $plural = null): static
    {
        $this->singular = $singular ?? $this->singular;
        $this->plural = $plural ?? $this->plural;

        return $this;
    }

    public function description(Closure|string|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * A Lucide icon name for admin menus
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * The features posts have besides a title and slug: content, excerpt, media,
     * meta, author and scheduling
     *
     * @param  array<int, string>  $features
     */
    public function supports(array $features): static
    {
        foreach ($features as $feature) {
            if (! in_array($feature, self::FEATURES, true)) {
                throw new PostTypesException("[{$feature}] isn't a post type feature. Use one of: " . implode(', ', self::FEATURES) . '.');
            }
        }

        $this->supports = array_values(array_unique($features));

        return $this;
    }

    /**
     * Add features to the ones the post type has
     */
    public function with(string ...$features): static
    {
        return $this->supports([...$this->supports, ...$features]);
    }

    /**
     * Remove features from the ones the post type has
     */
    public function without(string ...$features): static
    {
        return $this->supports(array_values(array_diff($this->supports, $features)));
    }

    /**
     * The taxonomies posts are grouped by, by key
     *
     * @param  array<int, string|Taxonomy>  $taxonomies
     */
    public function taxonomies(array $taxonomies): static
    {
        $keys = [];

        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy instanceof Taxonomy) {
                PostTypes::taxonomy($taxonomy);

                $taxonomy = $taxonomy->getKey();
            }

            $keys[] = $taxonomy;
        }

        $this->taxonomies = array_values(array_unique($keys));

        return $this;
    }

    /**
     * The statuses posts can have, by key or as Status definitions. The first
     * is the default for new posts unless defaultStatus() says otherwise.
     *
     * @param  array<int, string|Status>  $statuses
     */
    public function statuses(array $statuses): static
    {
        $this->statuses = array_values($statuses);

        $first = $statuses[array_key_first($statuses)] ?? Status::DRAFT;

        $this->default_status = $first instanceof Status ? $first->getKey() : $first;

        return $this;
    }

    public function defaultStatus(string $status): static
    {
        $this->default_status = $status;

        return $this;
    }

    /**
     * Fields stored with javaabu/meta (stencil fields, or key => type), shown in
     * the post form. Adds the meta feature.
     *
     * @param  array<int|string, mixed>|Closure  $fields
     */
    public function metaFields(array|Closure $fields): static
    {
        $this->meta_fields = $fields;

        if (! in_array(self::META, $this->supports, true)) {
            $this->supports[] = self::META;
        }

        return $this;
    }

    /**
     * More media pickers besides the featured image: javaabu/mediapicker's
     * MediaPickerField, each saved to the attachment collection it names
     *
     * @param  array<int, Component>|Closure  $fields
     */
    public function media(array|Closure $fields): static
    {
        $this->media_fields = $fields;

        return $this;
    }

    /**
     * The javaabu/block-editor profile of the content
     */
    public function blockProfile(string $profile): static
    {
        $this->block_profile = $profile;

        return $this;
    }

    /**
     * The URL base of the archive and the permalinks: /{slug}. Defaults to the
     * key in kebab case.
     */
    public function slug(string $slug): static
    {
        $this->slug = trim($slug, '/');

        return $this;
    }

    /**
     * The permalink structure under the base: {slug}, {id}, {year}, {month},
     * {day} and taxonomy keys ({categories}, the post's first term)
     */
    public function permalink(string $structure): static
    {
        $this->permalink = trim($structure, '/');

        return $this;
    }

    /**
     * Whether posts have public pages (default). Without them, the post type
     * only has admin pages and data for your own views.
     */
    public function public(bool $public = true): static
    {
        $this->public = $public;

        return $this;
    }

    /**
     * Whether the post type has a public archive (a list of its posts), and
     * how many posts each page shows
     */
    public function archive(bool $enabled = true, ?int $per_page = null): static
    {
        $this->has_archive = $enabled;
        $this->archive_per_page = $per_page ?? $this->archive_per_page;

        return $this;
    }

    /**
     * Rows per page on the admin index
     */
    public function perPage(int $per_page): static
    {
        $this->per_page = $per_page;

        return $this;
    }

    /**
     * Authors can only edit and delete their own posts unless they have
     * edit_others_{x} / delete_others_{x}
     */
    public function ownable(bool $ownable = true): static
    {
        $this->ownable = $ownable;

        return $this;
    }

    /**
     * The resource of the permissions: view_{permission}, edit_{permission}, …
     * Defaults to the key in snake case, plural.
     */
    public function permission(?string $permission): static
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * The model of this post type's posts: Post or a subclass of it
     *
     * @param  class-string<Post>|null  $model
     */
    public function model(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Blade views for the public pages: archive, single and term
     *
     * @param  array<string, string>  $views
     */
    public function views(array $views): static
    {
        $this->views = [...$this->views, ...$views];

        return $this;
    }

    /**
     * Inertia pages for the public pages: archive, single and term
     *
     * @param  array<string, string>  $pages
     */
    public function pages(array $pages): static
    {
        $this->pages = [...$this->pages, ...$pages];

        return $this;
    }

    /**
     * Where the post type comes in admin menus; lower first
     */
    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Reading
    |--------------------------------------------------------------------------
    */

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSingularLabel(): string
    {
        return $this->resolveLabel($this->singular, Str::headline(Str::singular($this->key)));
    }

    public function getPluralLabel(): string
    {
        return $this->resolveLabel($this->plural, Str::headline(Str::plural($this->key)));
    }

    public function getDescription(): ?string
    {
        if ($this->description === null) {
            return null;
        }

        return $this->resolveLabel($this->description, '');
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function supportsFeature(string $feature): bool
    {
        return in_array($feature, $this->supports, true);
    }

    /**
     * @return array<int, string>
     */
    public function getFeatures(): array
    {
        return $this->supports;
    }

    /**
     * @return array<int, string>
     */
    public function getTaxonomyKeys(): array
    {
        return $this->taxonomies;
    }

    /**
     * @return array<string, Taxonomy>
     */
    public function getTaxonomies(): array
    {
        $taxonomies = [];

        foreach ($this->taxonomies as $key) {
            $taxonomies[$key] = PostTypes::getTaxonomy($key);
        }

        return $taxonomies;
    }

    public function hasTaxonomy(string $key): bool
    {
        return in_array($key, $this->taxonomies, true);
    }

    /**
     * @return array<string, Status>
     */
    public function getStatuses(): array
    {
        $statuses = [];

        foreach ($this->statuses as $status) {
            if ($status instanceof Status) {
                PostTypes::status($status);

                $status = $status->getKey();
            }

            $statuses[$status] = PostTypes::getStatus($status);
        }

        return $statuses;
    }

    public function getStatus(?string $key): ?Status
    {
        return $this->getStatuses()[(string) $key] ?? null;
    }

    public function hasStatus(?string $key): bool
    {
        return $this->getStatus($key) !== null;
    }

    public function getDefaultStatus(): string
    {
        return $this->default_status;
    }

    /**
     * The statuses that show posts on the site
     *
     * @return array<int, string>
     */
    public function getPublicStatuses(): array
    {
        return array_keys(array_filter($this->getStatuses(), fn (Status $status) => $status->isPublic()));
    }

    /**
     * @return array<int|string, mixed>|Closure|null
     */
    public function getMetaFields(): array|Closure|null
    {
        return $this->meta_fields;
    }

    /**
     * @return array<int, Component>
     */
    public function getMediaFields(): array
    {
        $fields = $this->media_fields instanceof Closure
            ? app()->call($this->media_fields, ['post_type' => $this])
            : $this->media_fields;

        return array_values($fields ?? []);
    }

    public function getBlockProfile(): string
    {
        return $this->block_profile;
    }

    /**
     * The URL base, after config('post-types.permalinks')
     */
    public function getSlug(): string
    {
        $configured = config("post-types.permalinks.{$this->key}.base");

        if (is_string($configured)) {
            return trim($configured, '/');
        }

        return $this->slug ?? Str::kebab($this->key);
    }

    /**
     * The permalink structure, after config('post-types.permalinks')
     */
    public function getPermalinkStructure(): string
    {
        $configured = config("post-types.permalinks.{$this->key}.structure");

        if (is_string($configured) && $configured !== '') {
            return trim($configured, '/');
        }

        return $this->permalink;
    }

    public function getPermalink(): Permalink
    {
        return new Permalink($this, $this->getPermalinkStructure());
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function hasArchive(): bool
    {
        return $this->public && $this->has_archive && $this->getSlug() !== '';
    }

    public function getArchivePerPage(): int
    {
        return $this->archive_per_page ?? (int) config('post-types.archive_per_page', 12);
    }

    public function getPerPage(): int
    {
        return $this->per_page ?? (int) config('post-types.per_page', 20);
    }

    public function isOwnable(): bool
    {
        return $this->ownable;
    }

    public function getPermissionResource(): string
    {
        return $this->permission ?? Str::of($this->key)->replace('-', '_')->snake()->plural()->toString();
    }

    /**
     * A permission of the post type: view, edit, delete, force_delete, publish,
     * edit_others or delete_others
     */
    public function permissionFor(string $action): string
    {
        return "{$action}_{$this->getPermissionResource()}";
    }

    /**
     * The permissions of the post type, name => English description
     *
     * @return array<string, string>
     */
    public function permissions(): array
    {
        $plural = Str::lower($this->plural instanceof Closure || $this->plural === null
            ? Str::headline(Str::plural($this->key))
            : $this->plural);

        $permissions = [
            $this->permissionFor('view') => "View {$plural}",
            $this->permissionFor('edit') => "Edit {$plural}",
            $this->permissionFor('delete') => "Delete {$plural}",
            $this->permissionFor('force_delete') => "Delete {$plural} for good",
            $this->permissionFor('publish') => "Publish {$plural}",
        ];

        if ($this->ownable) {
            $permissions[$this->permissionFor('edit_others')] = "Edit others' {$plural}";
            $permissions[$this->permissionFor('delete_others')] = "Delete others' {$plural}";
        }

        return $permissions;
    }

    /**
     * @return class-string<Post>
     */
    public function getModel(): string
    {
        return $this->model ?? PostTypes::postModel();
    }

    /**
     * A query for this post type's posts
     *
     * @return Builder<Post>
     */
    public function query(): Builder
    {
        return $this->getModel()::query()->ofType($this->key);
    }

    /**
     * A new, unsaved post of this type
     */
    public function newPost(): Post
    {
        $model = $this->getModel();

        $post = new $model;
        $post->type = $this->key;
        $post->status = $this->default_status;

        return $post;
    }

    public function getView(string $page): ?string
    {
        return $this->views[$page] ?? null;
    }

    public function getPage(string $page): ?string
    {
        return $this->pages[$page] ?? null;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'singular' => $this->getSingularLabel(),
            'plural' => $this->getPluralLabel(),
            'description' => $this->getDescription(),
            'icon' => $this->icon,
            'supports' => $this->supports,
            'taxonomies' => array_values(array_map(fn (Taxonomy $taxonomy) => $taxonomy->toArray(), $this->getTaxonomies())),
            'statuses' => array_values(array_map(fn (Status $status) => $status->toArray(), $this->getStatuses())),
            'default_status' => $this->default_status,
            'slug' => $this->getSlug(),
            'permalink' => $this->getPermalinkStructure(),
            'public' => $this->public,
            'has_archive' => $this->hasArchive(),
        ];
    }

    protected function resolveLabel(Closure|string|null $label, string $default): string
    {
        if ($label instanceof Closure) {
            return (string) app()->call($label);
        }

        return __($label ?? $default);
    }
}
