<?php

namespace Javaabu\PostTypes\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Javaabu\BlockEditor\Casts\AsBlockDocument;
use Javaabu\BlockEditor\Document;
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Meta\Concerns\HasMeta;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Routing\PostTypeRoutes;
use Javaabu\PostTypes\Status;
use Javaabu\PostTypes\Support\Slug;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Fields\Field;

/**
 * A post of any post type. `type` is the post type's key.
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property Document $content
 * @property string $status
 * @property Carbon|null $published_at
 * @property string|null $author_type
 * @property int|string|null $author_id
 * @property Model|null $author
 * @property Collection<int, Term> $terms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Post extends Model implements HasAttachments
{
    use HasMeta;
    use InteractsWithAttachments;
    use SoftDeletes;

    /** The attachment collection of the featured image */
    public const string FEATURED_IMAGE = 'featured_image';

    protected $fillable = [
        'type',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'published_at',
        'meta',
    ];

    protected $attributes = [
        'status' => Status::DRAFT,
    ];

    public function getTable(): string
    {
        return config('post-types.tables.posts', 'posts');
    }

    protected function casts(): array
    {
        return [
            'content' => AsBlockDocument::class,
            'published_at' => 'datetime',
        ];
    }

    public static function bootPost(): void
    {
        static::saving(function (Post $post) {
            $post->fillDefaults();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Post type
    |--------------------------------------------------------------------------
    */

    /**
     * The post's post type definition, or null when it's no longer registered
     */
    public function postType(): ?PostType
    {
        return PostTypes::find($this->type);
    }

    /**
     * Meta fields come from the post type (javaabu/meta groups)
     */
    public function metaGroup(): ?string
    {
        return $this->type;
    }

    public function statusDefinition(): ?Status
    {
        $type = $this->postType();

        if ($type && $type->getStatus($this->status)) {
            return $type->getStatus($this->status);
        }

        return PostTypes::statuses()[$this->status] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Whoever wrote the post: any user model, any guard
     */
    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The taxonomy terms of the post, of every taxonomy
     *
     * @return BelongsToMany<Term, $this>
     */
    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(PostTypes::termModel(), config('post-types.tables.post_term', 'post_term'), 'post_id', 'term_id')
            ->orderBy('order')
            ->orderBy('id');
    }

    /**
     * The post's terms of one taxonomy
     *
     * @return Collection<int, Term>
     */
    public function termsOf(string $taxonomy): Collection
    {
        return $this->terms->where('taxonomy', $taxonomy)->values();
    }

    /**
     * Make the post's terms of a taxonomy exactly these (ids or terms); terms
     * of other taxonomies are kept, and ids of other taxonomies are ignored
     *
     * @param  iterable<int, int|string|Term>  $terms
     */
    public function syncTerms(string $taxonomy, iterable $terms): void
    {
        $ids = [];

        foreach ($terms as $term) {
            $ids[] = $term instanceof Term ? $term->getKey() : $term;
        }

        $model = PostTypes::termModel();

        $valid = $model::query()->ofTaxonomy($taxonomy)->whereKey($ids)->pluck('id')->all();
        $others = $this->terms()->where('taxonomy', '!=', $taxonomy)->pluck($this->terms()->getRelated()->qualifyColumn('id'))->all();

        $this->terms()->sync([...$others, ...$valid]);

        $this->unsetRelation('terms');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * @param  Builder<static>  $query
     * @param  string|array<int, string>|PostType  $type
     */
    public function scopeOfType(Builder $query, string|array|PostType $type): void
    {
        if ($type instanceof PostType) {
            $type = $type->getKey();
        }

        $query->whereIn($this->qualifyColumn('type'), (array) $type);
    }

    /**
     * Posts the public sees: a public status and a publish date that has passed
     *
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereIn($this->qualifyColumn('status'), $this->publicStatuses())
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', Carbon::now());
    }

    /**
     * Posts with a public status and a publish date in the future
     *
     * @param  Builder<static>  $query
     */
    public function scopeScheduled(Builder $query): void
    {
        $query->whereIn($this->qualifyColumn('status'), $this->publicStatuses())
            ->where($this->qualifyColumn('published_at'), '>', Carbon::now());
    }

    /**
     * Posts that aren't shown yet: not a public status, or scheduled
     *
     * @param  Builder<static>  $query
     */
    public function scopeUnpublished(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->whereNotIn($this->qualifyColumn('status'), $this->publicStatuses())
                ->orWhereNull($this->qualifyColumn('published_at'))
                ->orWhere($this->qualifyColumn('published_at'), '>', Carbon::now());
        });
    }

    /**
     * @param  Builder<static>  $query
     * @param  string|array<int, string>  $status
     */
    public function scopeWithStatus(Builder $query, string|array $status): void
    {
        $query->whereIn($this->qualifyColumn('status'), (array) $status);
    }

    /**
     * Posts with any of these terms (terms, ids or slugs) of a taxonomy
     *
     * @param  Builder<static>  $query
     * @param  Term|int|string|iterable<int, Term|int|string>  $terms
     */
    public function scopeWithTerms(Builder $query, string $taxonomy, mixed $terms): void
    {
        $values = is_iterable($terms) ? $terms : [$terms];
        $ids = [];
        $slugs = [];

        foreach ($values as $term) {
            match (true) {
                $term instanceof Term => $ids[] = $term->getKey(),
                is_int($term) || ctype_digit((string) $term) => $ids[] = (int) $term,
                default => $slugs[] = (string) $term,
            };
        }

        $query->whereHas('terms', function (Builder $query) use ($taxonomy, $ids, $slugs) {
            $query->where('taxonomy', $taxonomy)->where(function (Builder $query) use ($ids, $slugs) {
                $query->whereIn($query->getModel()->qualifyColumn('id'), $ids)
                    ->orWhereIn($query->getModel()->qualifyColumn('slug'), $slugs);
            });
        });
    }

    /**
     * Posts whose author is this user
     *
     * @param  Builder<static>  $query
     */
    public function scopeByAuthor(Builder $query, Model $author): void
    {
        $query->where($this->qualifyColumn('author_type'), $author->getMorphClass())
            ->where($this->qualifyColumn('author_id'), $author->getKey());
    }

    /**
     * Newest first, by publish date, then by id
     *
     * @param  Builder<static>  $query
     */
    public function scopeLatestPublished(Builder $query): void
    {
        $query->orderByDesc($this->qualifyColumn('published_at'))->orderByDesc($this->qualifyColumn('id'));
    }

    /**
     * Search titles and excerpts
     *
     * @param  Builder<static>  $query
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $like = '%' . addcslashes($search, '%_\\') . '%';

        $query->where(function (Builder $query) use ($like) {
            foreach ($this->searchColumns() as $column) {
                $query->orWhere($this->qualifyColumn($column), 'like', $like);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    /**
     * Shown on the site now
     */
    public function isPublished(): bool
    {
        return $this->hasPublicStatus()
            && $this->published_at !== null
            && $this->published_at->lessThanOrEqualTo(Carbon::now());
    }

    /**
     * Published with a publish date in the future
     */
    public function isScheduled(): bool
    {
        return $this->hasPublicStatus()
            && $this->published_at !== null
            && $this->published_at->greaterThan(Carbon::now());
    }

    public function hasPublicStatus(): bool
    {
        return in_array($this->status, $this->publicStatuses(), true);
    }

    /**
     * The status to show admins: "Scheduled" for scheduled posts, else the status's label
     */
    public function displayStatus(): string
    {
        if ($this->trashed()) {
            return __('Trashed');
        }

        if ($this->isScheduled()) {
            return __('Scheduled');
        }

        return $this->statusDefinition()?->getLabel() ?? $this->status;
    }

    public function displayStatusColor(): string
    {
        if ($this->trashed()) {
            return 'danger';
        }

        if ($this->isScheduled()) {
            return 'info';
        }

        return $this->statusDefinition()?->getColor() ?? 'secondary';
    }

    /**
     * Whether the user wrote the post
     */
    public function isOwnedBy(?Model $user): bool
    {
        return $user !== null
            && $this->author_id !== null
            && $this->author_type === $user->getMorphClass()
            && (string) $this->author_id === (string) $user->getKey();
    }

    /*
    |--------------------------------------------------------------------------
    | URLs and content
    |--------------------------------------------------------------------------
    */

    /**
     * The permalink, or null when the post type has no public pages or public
     * routes aren't registered
     */
    public function url(): ?string
    {
        return PostTypeRoutes::postUrl($this);
    }

    /**
     * The path of the permalink under the post type's base: `2026/11/ސުރުހީ`
     */
    public function permalinkPath(): ?string
    {
        return $this->postType()?->getPermalink()->pathFor($this);
    }

    /**
     * The content in a locale (translatable posts) or as it is
     */
    public function contentDocument(?string $locale = null): Document
    {
        return $this->content instanceof Document ? $this->content : Document::make($this->content);
    }

    /**
     * The excerpt, or the start of the content when there's none
     */
    public function summary(int $limit = 200): string
    {
        if (filled($this->excerpt)) {
            return (string) $this->excerpt;
        }

        return $this->contentDocument()->excerpt($limit);
    }

    /**
     * The featured image's URL (a conversion when given), or null
     */
    public function featuredImageUrl(string $conversion = ''): ?string
    {
        $attachment = $this->getFirstAttachment(self::FEATURED_IMAGE);

        if (! $attachment) {
            return null;
        }

        return $conversion === '' ? $attachment->getUrl() : $attachment->getAvailableUrl([$conversion]);
    }

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    /**
     * The featured image (when the post type supports media) and the post
     * type's other media collections
     */
    public function registerAttachmentCollections(): void
    {
        $type = $this->postType();

        if (! $type) {
            return;
        }

        if ($type->supportsFeature(PostType::MEDIA)) {
            $this->addAttachmentCollection(self::FEATURED_IMAGE)->singleFile();
        }

        foreach ($type->getMediaFields() as $field) {
            if (! $field instanceof Field) {
                continue;
            }

            $collection = method_exists($field, 'getCollection') ? $field->getCollection() : $field->getName();
            $adder = $this->addAttachmentCollection($collection);

            if (method_exists($field, 'isMultiple') && ! $field->isMultiple()) {
                $adder->singleFile();
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    /**
     * A slug from the title, unique in the post type, and a publish date for
     * published posts without one
     */
    public function fillDefaults(): void
    {
        if (blank($this->slug)) {
            $this->slug = Slug::make((string) $this->getRawTitle());
        } else {
            $this->slug = Slug::make($this->slug);
        }

        if ($this->slug === '') {
            $this->slug = (string) str()->lower(str()->random(8));
        }

        $this->slug = $this->uniqueSlug($this->slug);

        if ($this->hasPublicStatus() && $this->published_at === null) {
            $this->published_at = Carbon::now();
        }
    }

    protected function uniqueSlug(string $slug): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (static::query()
            ->withTrashed()
            ->where('type', $this->type)
            ->where('slug', $candidate)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * The title as stored, without translation
     */
    protected function getRawTitle(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    /**
     * The public statuses of the post type (every post type's, for queries
     * across types)
     *
     * @return array<int, string>
     */
    protected function publicStatuses(): array
    {
        $type = $this->type ? PostTypes::find($this->type) : null;

        if ($type) {
            return $type->getPublicStatuses();
        }

        $statuses = [];

        foreach (PostTypes::statuses() as $status) {
            if ($status->isPublic()) {
                $statuses[] = $status->getKey();
            }
        }

        return $statuses;
    }

    /**
     * The columns search() looks in
     *
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return ['title', 'excerpt'];
    }

    /**
     * Stencil components for the post type's media fields
     *
     * @return array<int, Component>
     */
    public function mediaFields(): array
    {
        return $this->postType()?->getMediaFields() ?? [];
    }
}
