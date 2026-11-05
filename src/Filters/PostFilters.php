<?php

namespace Javaabu\PostTypes\Filters;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\TextFilter;
use Javaabu\Filters\TrashedFilter;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;

/**
 * The filters and sorts of a post type's admin index (javaabu/filters): search,
 * status (with "scheduled"), each taxonomy, the publish date and the trash
 *
 * ```php
 * $filters = PostFilters::for($post_type);
 * $posts = $filters->apply($post_type->query())->paginate();
 * ```
 */
class PostFilters extends Filters
{
    /** The status filter's value for scheduled posts */
    public const string SCHEDULED = 'scheduled';

    protected ?PostType $post_type = null;

    protected ?string $default_sort = 'published_at';

    protected string $default_direction = 'desc';

    public static function for(PostType $post_type): static
    {
        return static::make()->postType($post_type);
    }

    public function postType(PostType $post_type): static
    {
        $this->post_type = $post_type;
        $this->model = $post_type->getModel();

        return $this;
    }

    public function getPostType(): ?PostType
    {
        return $this->post_type;
    }

    protected function filters(): array
    {
        $type = $this->post_type;

        if (! $type) {
            return [];
        }

        return [
            TextFilter::make('search')->thaana(),
            SelectFilter::make('status')
                ->label(fn () => __('Status'))
                ->options(fn () => $this->statusOptions())
                ->query(fn (Builder $query, string $value) => $this->applyStatus($query, $value)),
            ...array_values(array_map(fn (Taxonomy $taxonomy) => $this->taxonomyFilter($taxonomy), $type->getTaxonomies())),
            DateRangeFilter::make('published_at')->label(fn () => __('Publish date')),
            TrashedFilter::make('trashed'),
        ];
    }

    protected function sorts(): array
    {
        return [
            Sort::make('published_at')->label(fn () => __('Publish date'))->defaultDirection('desc'),
            Sort::make('title')->label(fn () => __('Title')),
            Sort::make('created_at')->label(fn () => __('Created'))->defaultDirection('desc'),
            Sort::make('updated_at')->label(fn () => __('Updated'))->defaultDirection('desc'),
        ];
    }

    /**
     * The post type's statuses, and "Scheduled"
     *
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        $options = [];

        foreach ($this->post_type?->getStatuses() ?? [] as $key => $status) {
            $options[$key] = $status->getLabel();
        }

        $options[self::SCHEDULED] = __('Scheduled');

        return $options;
    }

    protected function applyStatus(Builder $query, string $status): void
    {
        if ($status === self::SCHEDULED) {
            $query->scheduled();

            return;
        }

        if (in_array($status, $this->post_type?->getPublicStatuses() ?? [], true)) {
            $query->published();

            return;
        }

        $query->withStatus($status);
    }

    protected function taxonomyFilter(Taxonomy $taxonomy): SelectFilter
    {
        return SelectFilter::make($taxonomy->getKey())
            ->label(fn () => $taxonomy->getPluralLabel())
            ->options(function () use ($taxonomy) {
                $model = PostTypes::termModel();
                $options = [];

                foreach ($model::tree($taxonomy->getKey()) as $node) {
                    /** @var Term $term */
                    $term = $node['term'];
                    $options[$term->getKey()] = str_repeat('— ', $node['depth']) . $term->name;
                }

                return $options;
            })
            ->multiple()
            ->searchable()
            ->query(fn (Builder $query, array $value) => $query->withTerms($taxonomy->getKey(), $value));
    }
}
