<?php

namespace Javaabu\PostTypes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Support\Slug;
use Javaabu\PostTypes\Taxonomy;

/**
 * A term of a taxonomy: a category, a tag. `taxonomy` is the taxonomy's key.
 *
 * @property int $id
 * @property string $taxonomy
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $parent_id
 * @property int $order
 * @property Term|null $parent
 * @property Collection<int, Term> $children
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Term extends Model
{
    protected $fillable = [
        'taxonomy',
        'name',
        'slug',
        'description',
        'parent_id',
        'order',
    ];

    protected $attributes = [
        'order' => 0,
    ];

    public function getTable(): string
    {
        return config('post-types.tables.terms', 'post_terms');
    }

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Term $term) {
            $term->fillDefaults();
        });

        static::deleting(function (Term $term) {
            // Children move up to the term's parent; posts lose the term
            $term->children()->update(['parent_id' => $term->parent_id]);
            $term->posts()->detach();
        });
    }

    public function taxonomyDefinition(): ?Taxonomy
    {
        return PostTypes::findTaxonomy($this->taxonomy);
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * @return HasMany<Term, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(PostTypes::postModel(), config('post-types.tables.post_term', 'post_term'), 'term_id', 'post_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOfTaxonomy(Builder $query, string|Taxonomy $taxonomy): void
    {
        $query->where($this->qualifyColumn('taxonomy'), $taxonomy instanceof Taxonomy ? $taxonomy->getKey() : $taxonomy);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy($this->qualifyColumn('order'))->orderBy($this->qualifyColumn('name'));
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        // '!' escapes the wildcards, the same in MySQL and SQLite
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';

        $query->where(function (Builder $query) use ($like) {
            foreach ($this->searchColumns() as $column) {
                $query->orWhereRaw($query->getGrammar()->wrap($this->qualifyColumn($column)) . " like ? escape '!'", [$like]);
            }
        });
    }

    /**
     * The term's archive for a post type, or null when it has none
     */
    public function url(PostType|string $post_type): ?string
    {
        return PostTypeRoutes::termUrl($this, $post_type);
    }

    /**
     * The term's name with its parents': "News › Local"
     */
    public function path(string $separator = ' › '): string
    {
        $names = [$this->name];
        $parent = $this->parent;
        $seen = [$this->getKey()];

        while ($parent && ! in_array($parent->getKey(), $seen, true)) {
            array_unshift($names, $parent->name);
            $seen[] = $parent->getKey();
            $parent = $parent->parent;
        }

        return implode($separator, $names);
    }

    /**
     * The terms of a taxonomy as a tree in display order: each term with its depth
     *
     * @return array<int, array{term: Term, depth: int}>
     */
    public static function tree(string $taxonomy): array
    {
        $terms = static::query()->ofTaxonomy($taxonomy)->ordered()->get();
        $by_parent = $terms->groupBy(fn (Term $term) => (int) $term->parent_id);
        $ids = $terms->modelKeys();
        $tree = [];

        $walk = function (int $parent, int $depth) use (&$walk, &$tree, $by_parent) {
            foreach ($by_parent->get($parent, []) as $term) {
                $tree[] = ['term' => $term, 'depth' => $depth];
                $walk((int) $term->getKey(), $depth + 1);
            }
        };

        $walk(0, 0);

        // Terms whose parent is gone or in another taxonomy go at the top level
        foreach ($terms as $term) {
            if ($term->parent_id && ! in_array($term->parent_id, $ids, true)) {
                $tree[] = ['term' => $term, 'depth' => 0];
            }
        }

        return $tree;
    }

    /**
     * Whether a term is this term or one of its descendants (a parent can't be any of them)
     */
    public function isSelfOrDescendant(int|string|null $id): bool
    {
        if ($id === null) {
            return false;
        }

        $check = static::query()->find($id);
        $seen = [];

        while ($check && ! in_array($check->getKey(), $seen, true)) {
            if ((string) $check->getKey() === (string) $this->getKey()) {
                return true;
            }

            $seen[] = $check->getKey();
            $check = $check->parent;
        }

        return false;
    }

    /**
     * A slug from the name, unique in the taxonomy
     */
    public function fillDefaults(): void
    {
        $this->order ??= 0;

        $slug = Slug::make(blank($this->slug) ? (string) ($this->attributes['name'] ?? '') : $this->slug);

        if ($slug === '') {
            $slug = (string) str()->lower(str()->random(8));
        }

        $candidate = $slug;
        $suffix = 2;

        while (static::query()
            ->where('taxonomy', $this->taxonomy)
            ->where('slug', $candidate)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        $this->slug = $candidate;
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return ['name', 'slug'];
    }
}
