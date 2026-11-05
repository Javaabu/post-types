<?php

namespace Javaabu\PostTypes;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Meta\MetaFields;
use Javaabu\PostTypes\Exceptions\PostTypesException;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\Support\Translations;

/**
 * The registry of post types, taxonomies and statuses (facade: PostTypes)
 */
class PostTypesManager
{
    /** @var array<string, PostType> */
    protected array $types = [];

    /** @var array<string, Taxonomy> */
    protected array $taxonomies = [];

    /** @var array<string, Status> */
    protected array $statuses = [];

    /** @var array<int, callable(PostType): void> */
    protected array $registered_callbacks = [];

    public function __construct()
    {
        $this->statuses = Status::defaults();
    }

    /*
    |--------------------------------------------------------------------------
    | Post types
    |--------------------------------------------------------------------------
    */

    /**
     * Register post types: PostType instances or the class names of subclasses.
     * A post type with the same key replaces the earlier one.
     *
     * @param  PostType|class-string<PostType>|array<int, PostType|class-string<PostType>>  $types
     */
    public function register(PostType|string|array $types): static
    {
        foreach (is_array($types) ? $types : [$types] as $type) {
            $type = is_string($type) ? new $type : $type;

            if (! $type instanceof PostType) {
                throw new PostTypesException('Post types must be PostType instances or class names of PostType subclasses.');
            }

            $this->types[$type->getKey()] = $type;

            $this->registerMetaFields($type);

            foreach ($this->registered_callbacks as $callback) {
                $callback($type);
            }
        }

        return $this;
    }

    /**
     * Called for every post type registered from now on, and the ones already registered
     *
     * @param  callable(PostType): void  $callback
     */
    public function registered(callable $callback): static
    {
        $this->registered_callbacks[] = $callback;

        foreach ($this->types as $type) {
            $callback($type);
        }

        return $this;
    }

    public function forget(string $key): static
    {
        unset($this->types[$key]);

        app(MetaFields::class)->forget(Post::class, $key);

        return $this;
    }

    /**
     * The registered post types, in their order
     *
     * @return array<string, PostType>
     */
    public function types(): array
    {
        $types = $this->types;

        uasort($types, fn (PostType $a, PostType $b) => $a->getOrder() <=> $b->getOrder());

        return $types;
    }

    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    public function find(?string $key): ?PostType
    {
        return $this->types[(string) $key] ?? null;
    }

    public function get(string $key): PostType
    {
        return $this->find($key) ?? throw PostTypesException::unknownType($key);
    }

    /**
     * The post type whose URL base is the given slug
     */
    public function findBySlug(string $slug): ?PostType
    {
        foreach ($this->types as $type) {
            if ($type->getSlug() === $slug) {
                return $type;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Taxonomies
    |--------------------------------------------------------------------------
    */

    /**
     * Register taxonomies: Taxonomy instances or the class names of subclasses
     *
     * @param  Taxonomy|class-string<Taxonomy>|array<int, Taxonomy|class-string<Taxonomy>>  $taxonomies
     */
    public function taxonomy(Taxonomy|string|array $taxonomies): static
    {
        foreach (is_array($taxonomies) ? $taxonomies : [$taxonomies] as $taxonomy) {
            $taxonomy = is_string($taxonomy) ? new $taxonomy : $taxonomy;

            if (! $taxonomy instanceof Taxonomy) {
                throw new PostTypesException('Taxonomies must be Taxonomy instances or class names of Taxonomy subclasses.');
            }

            $this->taxonomies[$taxonomy->getKey()] = $taxonomy;
        }

        return $this;
    }

    /**
     * @return array<string, Taxonomy>
     */
    public function taxonomies(): array
    {
        return $this->taxonomies;
    }

    public function hasTaxonomy(string $key): bool
    {
        return isset($this->taxonomies[$key]);
    }

    public function findTaxonomy(?string $key): ?Taxonomy
    {
        return $this->taxonomies[(string) $key] ?? null;
    }

    public function getTaxonomy(string $key): Taxonomy
    {
        return $this->findTaxonomy($key) ?? throw PostTypesException::unknownTaxonomy($key);
    }

    /**
     * The post types that use a taxonomy
     *
     * @return array<string, PostType>
     */
    public function typesWithTaxonomy(string $taxonomy): array
    {
        return array_filter($this->types(), fn (PostType $type) => $type->hasTaxonomy($taxonomy));
    }

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    */

    /**
     * Register statuses post types can list: archived, private, …
     *
     * @param  Status|array<int, Status>  $statuses
     */
    public function status(Status|array $statuses): static
    {
        foreach (is_array($statuses) ? $statuses : [$statuses] as $status) {
            $this->statuses[$status->getKey()] = $status;
        }

        return $this;
    }

    /**
     * @return array<string, Status>
     */
    public function statuses(): array
    {
        return $this->statuses;
    }

    public function getStatus(string $key): Status
    {
        return $this->statuses[$key] ?? throw PostTypesException::unknownStatus($key);
    }

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    */

    /**
     * The post model: config('post-types.models.post'), else the package's own
     * (translatable when javaabu/translatable is enabled)
     *
     * @return class-string<Post>
     */
    public function postModel(): string
    {
        return config('post-types.models.post')
            ?? (Translations::enabled() ? Models\Translatable\Post::class : Post::class);
    }

    /**
     * @return class-string<Term>
     */
    public function termModel(): string
    {
        return config('post-types.models.term')
            ?? (Translations::enabled() ? Models\Translatable\Term::class : Term::class);
    }

    /**
     * The model classes posts and terms can be: the configured ones and the
     * package's (for policies)
     *
     * @return array<int, class-string<Model>>
     */
    public function modelClasses(string $kind): array
    {
        $classes = $kind === 'term'
            ? [Term::class, Models\Translatable\Term::class, config('post-types.models.term')]
            : [Post::class, Models\Translatable\Post::class, config('post-types.models.post')];

        if ($kind === 'post') {
            foreach ($this->types as $type) {
                $classes[] = $type->getModel();
            }
        }

        return array_values(array_unique(array_filter($classes)));
    }

    /**
     * The meta fields of a post type, registered with javaabu/meta under the
     * post type's key as the group
     */
    protected function registerMetaFields(PostType $type): void
    {
        $fields = $type->getMetaFields();

        // A post type registered again replaces its fields
        app(MetaFields::class)->forget(Post::class, $type->getKey());

        if ($fields === null) {
            return;
        }

        // Subclasses (the translatable post, the app's own) inherit the definitions
        app(MetaFields::class)->for(Post::class, $fields, group: $type->getKey());
    }
}
