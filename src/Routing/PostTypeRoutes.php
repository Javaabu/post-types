<?php

namespace Javaabu\PostTypes\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Javaabu\PostTypes\Facades\PostTypes;
use Javaabu\PostTypes\Http\Controllers\PostsController;
use Javaabu\PostTypes\Http\Controllers\PublicPostsController;
use Javaabu\PostTypes\Http\Controllers\TermsController;
use Javaabu\PostTypes\Models\Post;
use Javaabu\PostTypes\Models\Term;
use Javaabu\PostTypes\PostType;
use Javaabu\PostTypes\Taxonomy;

/**
 * Registers the routes of `Route::postTypesAdmin()` and `Route::postTypes()`,
 * and builds post and term URLs from them
 *
 * Admin, per post type (`{type}` is the key, `news`):
 *
 * | Route                                   | Name                      | Action      |
 * | --------------------------------------- | ------------------------- | ----------- |
 * | GET {prefix}/{type}                     | {name}{type}.index        | index       |
 * | GET {prefix}/{type}/create              | {name}{type}.create       | create      |
 * | POST {prefix}/{type}                    | {name}{type}.store        | store       |
 * | GET {prefix}/{type}/{post}/edit         | {name}{type}.edit         | edit        |
 * | PATCH {prefix}/{type}/{post}            | {name}{type}.update       | update      |
 * | DELETE {prefix}/{type}/{post}           | {name}{type}.destroy      | destroy (trash) |
 * | POST {prefix}/{type}/{post}/restore     | {name}{type}.restore      | restore     |
 * | DELETE {prefix}/{type}/{post}/force     | {name}{type}.force-delete | forceDelete |
 * | POST {prefix}/{type}/bulk               | {name}{type}.bulk         | bulk        |
 *
 * and per taxonomy, `{prefix}/taxonomies/{taxonomy}` with index, create, store,
 * edit, update and destroy, named `{name}taxonomies.{taxonomy}.*`.
 *
 * Public, per post type with public pages (`{base}` is the post type's slug):
 *
 * | Route                                   | Name               | Action  |
 * | --------------------------------------- | ------------------ | ------- |
 * | GET {prefix}/{base}                     | {name}{type}.index | archive |
 * | GET {prefix}/{base}/{taxonomy}/{term}   | {name}{type}.term  | term    |
 * | GET {prefix}/{base}/{permalink}         | {name}{type}.show  | show    |
 */
class PostTypeRoutes
{
    /** The route action key that holds the post type's key */
    public const string TYPE_ACTION = 'post_type';

    /** The route action key that holds the taxonomy's key */
    public const string TAXONOMY_ACTION = 'post_type_taxonomy';

    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'post_types_guard';

    /** The route action key that holds the name prefix the routes were registered with */
    public const string NAME_ACTION = 'post_types_name';

    /**
     * The public route name prefix of each post type, for permalinks: the
     * first Route::postTypes() call that registered it, unless a later one
     * says `'default' => true`
     *
     * @var array<string, string>
     */
    protected static array $public_names = [];

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     only?: array<int, string>|null,
     *     except?: array<int, string>,
     *     taxonomies?: bool,
     *     controllers?: array{posts?: class-string, terms?: class-string}
     * }  $options
     */
    public static function registerAdmin(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'admin', '/');
        $name = $options['name'] ?? 'admin.';
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $posts = $options['controllers']['posts'] ?? config('post-types.controllers.posts', PostsController::class);
        $terms = $options['controllers']['terms'] ?? config('post-types.controllers.terms', TermsController::class);

        $types = static::selectTypes($options);

        $router->group([
            'prefix' => $prefix,
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
            self::NAME_ACTION => $name,
        ], function (Router $router) use ($types, $name, $posts, $terms, $options) {
            $taxonomies = [];

            foreach ($types as $type) {
                static::registerAdminType($router, $type, $name, $posts);

                foreach ($type->getTaxonomyKeys() as $taxonomy) {
                    $taxonomies[$taxonomy] = PostTypes::getTaxonomy($taxonomy);
                }
            }

            if (! ($options['taxonomies'] ?? true)) {
                return;
            }

            foreach ($taxonomies as $taxonomy) {
                static::registerAdminTaxonomy($router, $taxonomy, $name, $terms);
            }
        });
    }

    protected static function registerAdminType(Router $router, PostType $type, string $name, string $controller): void
    {
        $key = $type->getKey();
        $uri = static::adminSegment($key);

        $router->group([
            'prefix' => $uri,
            'as' => "{$name}{$key}.",
            self::TYPE_ACTION => $key,
        ], function (Router $router) use ($controller) {
            $router->get('/', [$controller, 'index'])->name('index');
            $router->get('/create', [$controller, 'create'])->name('create');
            $router->post('/', [$controller, 'store'])->name('store');
            $router->post('/bulk', [$controller, 'bulk'])->name('bulk');
            $router->get('/{post}/edit', [$controller, 'edit'])->name('edit')->whereNumber('post');
            $router->match(['PUT', 'PATCH'], '/{post}', [$controller, 'update'])->name('update')->whereNumber('post');
            $router->delete('/{post}', [$controller, 'destroy'])->name('destroy')->whereNumber('post');
            $router->post('/{post}/restore', [$controller, 'restore'])->name('restore')->whereNumber('post');
            $router->delete('/{post}/force', [$controller, 'forceDelete'])->name('force-delete')->whereNumber('post');
        });
    }

    protected static function registerAdminTaxonomy(Router $router, Taxonomy $taxonomy, string $name, string $controller): void
    {
        $key = $taxonomy->getKey();

        $router->group([
            'prefix' => 'taxonomies/' . static::adminSegment($key),
            'as' => "{$name}taxonomies.{$key}.",
            self::TAXONOMY_ACTION => $key,
        ], function (Router $router) use ($controller) {
            $router->get('/', [$controller, 'index'])->name('index');
            $router->get('/create', [$controller, 'create'])->name('create');
            $router->post('/', [$controller, 'store'])->name('store');
            $router->get('/{term}/edit', [$controller, 'edit'])->name('edit')->whereNumber('term');
            $router->match(['PUT', 'PATCH'], '/{term}', [$controller, 'update'])->name('update')->whereNumber('term');
            $router->delete('/{term}', [$controller, 'destroy'])->name('destroy')->whereNumber('term');
        });
    }

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     only?: array<int, string>|null,
     *     except?: array<int, string>,
     *     controller?: class-string|null,
     *     default?: bool
     * }  $options
     */
    public static function registerPublic(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? '', '/');
        $name = $options['name'] ?? '';
        $controller = $options['controller'] ?? config('post-types.controllers.public', PublicPostsController::class);

        $group = [
            'prefix' => $prefix,
            self::GUARD_ACTION => $guard,
            self::NAME_ACTION => $name,
        ];

        if (array_key_exists('middleware', $options) && $options['middleware'] !== null) {
            $group['middleware'] = $options['middleware'];
        }

        $types = array_filter(static::selectTypes($options), fn (PostType $type) => $type->isPublic());

        $router->group($group, function (Router $router) use ($types, $name, $controller, $options) {
            foreach ($types as $type) {
                static::registerPublicType($router, $type, $name, $controller);

                if (! isset(static::$public_names[$type->getKey()]) || ($options['default'] ?? false)) {
                    static::$public_names[$type->getKey()] = $name;
                }
            }
        });
    }

    protected static function registerPublicType(Router $router, PostType $type, string $name, string $controller): void
    {
        $key = $type->getKey();
        $base = $type->getSlug();
        $permalink = $type->getPermalink();

        $router->group([
            'prefix' => $base,
            'as' => "{$name}{$key}.",
            self::TYPE_ACTION => $key,
        ], function (Router $router) use ($type, $controller, $permalink) {
            if ($type->hasArchive()) {
                $router->get('/', [$controller, 'archive'])->name('index');
            }

            $taxonomies = array_filter($type->getTaxonomies(), fn (Taxonomy $taxonomy) => $taxonomy->isPublic());

            if ($taxonomies !== []) {
                $slugs = array_map(fn (Taxonomy $taxonomy) => preg_quote($taxonomy->getSlug(), '/'), $taxonomies);

                $router->get('/{taxonomy}/{term}', [$controller, 'term'])
                    ->name('term')
                    ->where('taxonomy', implode('|', $slugs))
                    ->where('term', '[^/]+');
            }

            $router->get('/' . $permalink->routeUri(), [$controller, 'show'])
                ->name('show')
                ->where($permalink->wheres());
        });
    }

    /**
     * The post types a call registers: all of them, `only` these, minus `except`
     *
     * @param  array<string, mixed>  $options
     * @return array<string, PostType>
     */
    protected static function selectTypes(array $options): array
    {
        $types = PostTypes::types();

        if (! empty($options['only'])) {
            $types = array_intersect_key($types, array_flip($options['only']));
        }

        if (! empty($options['except'])) {
            $types = array_diff_key($types, array_flip($options['except']));
        }

        return $types;
    }

    /**
     * The admin URL segment of a post type or taxonomy key
     */
    public static function adminSegment(string $key): string
    {
        return str_replace('_', '-', $key);
    }

    /*
    |--------------------------------------------------------------------------
    | The current request
    |--------------------------------------------------------------------------
    */

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(Request $request): ?string
    {
        return $request->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix the current route was registered with, e.g. `admin.`
     */
    public static function namePrefix(Request $request): string
    {
        return (string) $request->route()?->getAction(self::NAME_ACTION);
    }

    /**
     * The post type of the current route
     */
    public static function postType(Request $request): ?PostType
    {
        return PostTypes::find($request->route()?->getAction(self::TYPE_ACTION));
    }

    /**
     * The taxonomy of the current admin route
     */
    public static function taxonomy(Request $request): ?Taxonomy
    {
        return PostTypes::findTaxonomy($request->route()?->getAction(self::TAXONOMY_ACTION));
    }

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    /**
     * The public route name of a post type's page (index, term or show), or
     * null when public routes aren't registered for it
     */
    public static function publicRouteName(PostType|string $type, string $page): ?string
    {
        $key = $type instanceof PostType ? $type->getKey() : $type;

        if (! isset(static::$public_names[$key])) {
            return null;
        }

        $name = static::$public_names[$key] . "{$key}.{$page}";

        return Route::has($name) ? $name : null;
    }

    public static function postUrl(Post $post): ?string
    {
        $type = $post->postType();

        if (! $type || ! $type->isPublic()) {
            return null;
        }

        $route = static::publicRouteName($type, 'show');
        $parameters = $type->getPermalink()->parametersFor($post);

        if (! $route || $parameters === null) {
            return null;
        }

        try {
            return route($route, $parameters);
        } catch (UrlGenerationException) {
            // The post type's permalink changed after its routes were registered
            return null;
        }
    }

    public static function archiveUrl(PostType|string $type): ?string
    {
        $type = $type instanceof PostType ? $type : PostTypes::find($type);

        if (! $type || ! $type->hasArchive()) {
            return null;
        }

        $route = static::publicRouteName($type, 'index');

        return $route ? route($route) : null;
    }

    public static function termUrl(Term $term, PostType|string $type): ?string
    {
        $type = $type instanceof PostType ? $type : PostTypes::find($type);
        $taxonomy = $term->taxonomyDefinition();

        if (! $type || ! $taxonomy || ! $taxonomy->isPublic() || ! $type->hasTaxonomy($taxonomy->getKey())) {
            return null;
        }

        $route = static::publicRouteName($type, 'term');

        return $route ? route($route, ['taxonomy' => $taxonomy->getSlug(), 'term' => $term->slug]) : null;
    }

    /**
     * Forget which public routes were registered (tests)
     */
    public static function flush(): void
    {
        static::$public_names = [];
    }
}
