<?php

namespace Javaabu\PostTypes\Facades;

use Illuminate\Support\Facades\Facade;
use Javaabu\PostTypes\PostTypesManager;

/**
 * @method static PostTypesManager register(\Javaabu\PostTypes\PostType|string|array $types)
 * @method static PostTypesManager registered(callable $callback)
 * @method static PostTypesManager forget(string $key)
 * @method static array<string, \Javaabu\PostTypes\PostType> types()
 * @method static bool has(string $key)
 * @method static \Javaabu\PostTypes\PostType|null find(?string $key)
 * @method static \Javaabu\PostTypes\PostType get(string $key)
 * @method static \Javaabu\PostTypes\PostType|null findBySlug(string $slug)
 * @method static PostTypesManager taxonomy(\Javaabu\PostTypes\Taxonomy|string|array $taxonomies)
 * @method static array<string, \Javaabu\PostTypes\Taxonomy> taxonomies()
 * @method static bool hasTaxonomy(string $key)
 * @method static \Javaabu\PostTypes\Taxonomy|null findTaxonomy(?string $key)
 * @method static \Javaabu\PostTypes\Taxonomy getTaxonomy(string $key)
 * @method static array<string, \Javaabu\PostTypes\PostType> typesWithTaxonomy(string $taxonomy)
 * @method static PostTypesManager status(\Javaabu\PostTypes\Status|array $statuses)
 * @method static array<string, \Javaabu\PostTypes\Status> statuses()
 * @method static \Javaabu\PostTypes\Status getStatus(string $key)
 * @method static class-string<\Javaabu\PostTypes\Models\Post> postModel()
 * @method static class-string<\Javaabu\PostTypes\Models\Term> termModel()
 *
 * @see PostTypesManager
 */
class PostTypes extends Facade
{
    /**
     * Whether the package runs its own migrations; turn off after publishing them
     */
    public static bool $runs_migrations = true;

    protected static function getFacadeAccessor(): string
    {
        return PostTypesManager::class;
    }
}
